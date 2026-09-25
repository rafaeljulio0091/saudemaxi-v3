<?php

namespace App\Triage\Services;

use App\AI\Contracts\ConversationalAIProvider;
use App\AI\Contracts\DecisionAIProvider;
use App\AI\DTO\ConversationInput;
use App\AI\DTO\ConversationResult;
use App\AI\DTO\DecisionInput;
use App\AI\DTO\DecisionResult;
use App\AI\Exceptions\AiProviderException;
use App\Models\TriageAiEvent;
use App\Models\TriageAssessment;
use App\Models\TriageMessage;
use App\Models\TriageSession;
use App\Triage\DTO\SafetyResult;
use App\Triage\DTO\TriageReply;
use App\Triage\Enums\TriageClassification;
use App\Triage\Enums\TriageSender;
use App\Triage\Enums\TriageStatus;
use App\Triage\Rules\PatientMessageGuard;
use App\Triage\Rules\SafetyRuleEngine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class TriageConversationService
{
    public function __construct(
        private ConversationalAIProvider $conversationProvider,
        private DecisionAIProvider $decisionProvider,
        private SafetyRuleEngine $safetyRules,
        private PatientMessageGuard $messageGuard,
        private TriageDecisionEngine $decisionEngine,
    ) {}

    public function respond(TriageSession $session, string $message, string $requestId): TriageReply
    {
        $lock = Cache::lock("triage:session:{$session->id}", config('triage.lock_seconds'));

        if (! $lock->get()) {
            throw new ConflictHttpException('A mensagem anterior ainda está sendo processada.');
        }

        try {
            $session->refresh();

            $existing = $session->messages()
                ->where('sender', TriageSender::Patient)
                ->where('request_id', $requestId)
                ->first();

            if ($existing) {
                return $this->replayedReply($session, $existing);
            }

            if ($session->status !== TriageStatus::Active) {
                throw new ConflictHttpException('Esta triagem já foi encaminhada ou encerrada.');
            }

            if ($session->messages()->where('sender', TriageSender::Patient)->count() >= config('triage.max_messages_per_session')) {
                throw ValidationException::withMessages([
                    'message' => 'O limite desta conversa foi atingido. Solicite atendimento humano.',
                ]);
            }

            $patientMessage = $this->storeMessage(
                $session,
                TriageSender::Patient,
                $message,
                $requestId,
            );
            $safety = $this->safetyRules->inspect($patientMessage->content);

            if ($safety->isCritical) {
                return $this->emergencyReply($session, $patientMessage, $safety);
            }

            return $this->providersReply($session, $patientMessage, $safety);
        } finally {
            $lock->release();
        }
    }

    private function providersReply(
        TriageSession $session,
        TriageMessage $patientMessage,
        SafetyResult $safety,
    ): TriageReply {
        $openAiCorrelation = (string) Str::uuid();

        try {
            $conversation = $this->conversationProvider->respond(new ConversationInput(
                messages: $this->conversationContext($session),
                correlationId: $openAiCorrelation,
            ));
            $this->recordSuccess($session, $openAiCorrelation, 'openai', $conversation);
        } catch (AiProviderException $exception) {
            $this->recordFailure($session, $openAiCorrelation, $exception, $safety->version);

            return $this->fallbackReply($session, $patientMessage, [], $safety->version);
        } catch (Throwable $exception) {
            $providerException = new AiProviderException('openai', '', 'unexpected_error', previous: $exception);
            $this->recordFailure($session, $openAiCorrelation, $providerException, $safety->version);

            return $this->fallbackReply($session, $patientMessage, [], $safety->version);
        }

        if (! $this->messageGuard->isSafe($conversation->message)) {
            return $this->fallbackReply($session, $patientMessage, $conversation->state, $safety->version, 'unsafe_output');
        }

        $jevCorrelation = (string) Str::uuid();

        try {
            $decision = $this->decisionProvider->classify(new DecisionInput(
                state: $conversation->state,
                correlationId: $jevCorrelation,
            ));
            $this->recordDecisionSuccess($session, $jevCorrelation, $decision, $safety->version);
        } catch (AiProviderException $exception) {
            $this->recordFailure($session, $jevCorrelation, $exception, $safety->version);

            return $this->fallbackReply($session, $patientMessage, $conversation->state, $safety->version);
        } catch (Throwable $exception) {
            $providerException = new AiProviderException('jev', '', 'unexpected_error', previous: $exception);
            $this->recordFailure($session, $jevCorrelation, $providerException, $safety->version);

            return $this->fallbackReply($session, $patientMessage, $conversation->state, $safety->version);
        }

        $routing = $this->decisionEngine->decide($conversation, $decision);

        if ($routing['classification'] === TriageClassification::Emergency) {
            return $this->persistReply(
                session: $session,
                patientMessage: $patientMessage,
                message: $this->emergencyMessage(),
                state: $conversation->state,
                classification: TriageClassification::Emergency,
                confidence: $decision->confidence,
                requiresHumanReview: true,
                status: TriageStatus::Emergency,
                source: 'jev',
                model: $decision->model,
                safetyVersion: $safety->version,
                actions: $this->emergencyActions(),
            );
        }

        $message = $routing['status'] === TriageStatus::HumanReview
            ? 'Registrei suas informações e encaminhei o caso para avaliação humana. Se houver piora ou risco imediato, procure um serviço de emergência.'
            : $conversation->message;

        return $this->persistReply(
            session: $session,
            patientMessage: $patientMessage,
            message: $message,
            state: $conversation->state,
            classification: $routing['classification'],
            confidence: $decision->confidence,
            requiresHumanReview: $routing['requires_human_review'],
            status: $routing['status'],
            source: 'jev',
            model: $decision->model,
            safetyVersion: $safety->version,
            actions: $routing['status'] === TriageStatus::HumanReview
                ? [['label' => 'Falar com um profissional', 'path' => '/atendimento']]
                : [],
        );
    }

    private function emergencyReply(
        TriageSession $session,
        TriageMessage $patientMessage,
        SafetyResult $safety,
    ): TriageReply {
        TriageAiEvent::create([
            'tenant_id' => $session->tenant_id,
            'triage_session_id' => $session->id,
            'correlation_id' => (string) Str::uuid(),
            'provider' => 'safety',
            'operation' => 'deterministic_screening',
            'successful' => true,
            'classification' => TriageClassification::Emergency,
            'fallback_used' => false,
            'safety_rule_version' => $safety->version,
            'error_code' => $safety->ruleId,
        ]);

        return $this->persistReply(
            session: $session,
            patientMessage: $patientMessage,
            message: $this->emergencyMessage(),
            state: ['safety_rule_id' => $safety->ruleId],
            classification: TriageClassification::Emergency,
            confidence: null,
            requiresHumanReview: true,
            status: TriageStatus::Emergency,
            source: 'safety',
            model: null,
            safetyVersion: $safety->version,
            actions: $this->emergencyActions(),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function fallbackReply(
        TriageSession $session,
        TriageMessage $patientMessage,
        array $state,
        string $safetyVersion,
        string $errorCode = 'provider_unavailable',
    ): TriageReply {
        $state['fallback_reason'] = $errorCode;

        return $this->persistReply(
            session: $session,
            patientMessage: $patientMessage,
            message: 'Não foi possível concluir a orientação automatizada agora. Suas informações foram registradas para avaliação humana.',
            state: $state,
            classification: TriageClassification::HumanReview,
            confidence: null,
            requiresHumanReview: true,
            status: TriageStatus::HumanReview,
            source: 'fallback',
            model: null,
            safetyVersion: $safetyVersion,
            actions: [['label' => 'Falar com um profissional', 'path' => '/atendimento']],
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  list<array{label: string, path: string}>  $actions
     */
    private function persistReply(
        TriageSession $session,
        TriageMessage $patientMessage,
        string $message,
        array $state,
        TriageClassification $classification,
        ?float $confidence,
        bool $requiresHumanReview,
        TriageStatus $status,
        string $source,
        ?string $model,
        string $safetyVersion,
        array $actions,
    ): TriageReply {
        return DB::transaction(function () use (
            $session,
            $patientMessage,
            $message,
            $state,
            $classification,
            $confidence,
            $requiresHumanReview,
            $status,
            $source,
            $model,
            $safetyVersion,
            $actions,
        ) {
            $this->storeMessage($session, TriageSender::Assistant, $message);

            TriageAssessment::create([
                'tenant_id' => $session->tenant_id,
                'triage_session_id' => $session->id,
                'triage_message_id' => $patientMessage->id,
                'classification' => $classification,
                'confidence' => $confidence,
                'structured_state' => $state,
                'requires_human_review' => $requiresHumanReview,
                'source' => $source,
                'provider_model' => $model,
                'safety_rule_version' => $safetyVersion,
            ]);

            $session->update([
                'status' => $status,
                'completed_at' => $status === TriageStatus::Active ? null : now(),
            ]);

            return new TriageReply(
                session: $session->fresh(),
                message: $message,
                classification: $classification,
                requiresHumanReview: $requiresHumanReview,
                actions: $actions,
            );
        });
    }

    private function storeMessage(
        TriageSession $session,
        TriageSender $sender,
        string $content,
        ?string $requestId = null,
    ): TriageMessage {
        return TriageMessage::create([
            'tenant_id' => $session->tenant_id,
            'triage_session_id' => $session->id,
            'sender' => $sender,
            'sequence' => ((int) $session->messages()->max('sequence')) + 1,
            'request_id' => $requestId,
            'content' => $content,
        ]);
    }

    private function replayedReply(
        TriageSession $session,
        TriageMessage $patientMessage,
    ): TriageReply {
        $assessment = $session->assessments()
            ->where('triage_message_id', $patientMessage->id)
            ->first();
        $assistantMessage = $session->messages()
            ->where('sender', TriageSender::Assistant)
            ->where('sequence', '>', $patientMessage->sequence)
            ->oldest('sequence')
            ->first();

        if (! $assessment || ! $assistantMessage) {
            throw new ConflictHttpException('A mensagem anterior ainda está sendo processada.');
        }

        $actions = match ($assessment->classification) {
            TriageClassification::Emergency => $this->emergencyActions(),
            TriageClassification::Priority,
            TriageClassification::HumanReview => [['label' => 'Falar com um profissional', 'path' => '/atendimento']],
            default => [],
        };

        return new TriageReply(
            $session,
            $assistantMessage->content,
            $assessment->classification,
            $assessment->requires_human_review,
            $actions,
        );
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function conversationContext(TriageSession $session): array
    {
        return $session->messages()
            ->latest('sequence')
            ->limit(config('triage.context_message_limit'))
            ->get()
            ->reverse()
            ->map(fn (TriageMessage $message) => [
                'role' => $message->sender === TriageSender::Patient ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    private function recordSuccess(
        TriageSession $session,
        string $correlationId,
        string $provider,
        ConversationResult $result,
    ): void {
        TriageAiEvent::create([
            'tenant_id' => $session->tenant_id,
            'triage_session_id' => $session->id,
            'correlation_id' => $correlationId,
            'provider' => $provider,
            'model' => $result->model,
            'operation' => 'conversation',
            'duration_ms' => $result->durationMs,
            'successful' => true,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'fallback_used' => false,
            'safety_rule_version' => config('triage.safety.version'),
        ]);
    }

    private function recordDecisionSuccess(
        TriageSession $session,
        string $correlationId,
        DecisionResult $result,
        string $safetyVersion,
    ): void {
        TriageAiEvent::create([
            'tenant_id' => $session->tenant_id,
            'triage_session_id' => $session->id,
            'correlation_id' => $correlationId,
            'provider' => 'jev',
            'model' => $result->model,
            'operation' => 'classification',
            'duration_ms' => $result->durationMs,
            'successful' => true,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'classification' => $result->classification,
            'confidence' => $result->confidence,
            'fallback_used' => false,
            'safety_rule_version' => $safetyVersion,
        ]);
    }

    private function recordFailure(
        TriageSession $session,
        string $correlationId,
        AiProviderException $exception,
        string $safetyVersion,
    ): void {
        TriageAiEvent::create([
            'tenant_id' => $session->tenant_id,
            'triage_session_id' => $session->id,
            'correlation_id' => $correlationId,
            'provider' => $exception->provider,
            'model' => $exception->model ?: null,
            'operation' => $exception->provider === 'jev' ? 'classification' : 'conversation',
            'duration_ms' => $exception->durationMs,
            'successful' => false,
            'fallback_used' => true,
            'safety_rule_version' => $safetyVersion,
            'error_code' => $exception->errorCode,
        ]);
    }

    private function emergencyMessage(): string
    {
        return 'Este chat não substitui atendimento emergencial. Procure imediatamente um serviço de emergência. Se estiver no Brasil, ligue para o SAMU pelo número 192. O caso também ficará disponível para avaliação humana.';
    }

    /**
     * @return list<array{label: string, path: string}>
     */
    private function emergencyActions(): array
    {
        return collect(config('triage.emergency_contacts'))
            ->map(fn (array $contact) => [
                'label' => "Ligar para {$contact['label']} ({$contact['phone']})",
                'path' => "tel:{$contact['phone']}",
            ])
            ->values()
            ->all();
    }
}
