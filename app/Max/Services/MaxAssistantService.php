<?php

namespace App\Max\Services;

use App\AI\Contracts\AssistantIntentProvider;
use App\AI\Contracts\AssistantReplyProvider;
use App\AI\DTO\AssistantInput;
use App\AI\Exceptions\AiProviderException;
use App\Max\Rules\MaxRuleEngine;
use App\Max\Support\MaxActionCatalog;
use App\Models\User;
use App\Services\Healthcare\TenantPlanService;
use App\Triage\Rules\PatientMessageGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Official MAX assistant.
 *
 * 1. Deterministic rules first (emergency, privacy, medication, symptoms):
 *    fixed, reviewed answers that never depend on an AI provider.
 * 2. Otherwise Jev classifies the business intent (it can escalate to one of
 *    the fixed answers) and OpenAI writes a short navigation reply, whose
 *    links are restricted to MaxActionCatalog.
 * 3. Any provider failure falls back to the deterministic answer.
 *
 * Conversations are not persisted and message content is never logged.
 * Only the redacted message, profile and a sanitised page path leave the
 * server; no name, CPF, tenant or patient identifier is sent.
 */
class MaxAssistantService
{
    public function __construct(
        private MaxRuleEngine $rules,
        private MaxActionCatalog $catalog,
        private TenantPlanService $plans,
        private AssistantIntentProvider $intentProvider,
        private AssistantReplyProvider $replyProvider,
        private PatientMessageGuard $guard,
    ) {}

    /**
     * @return array{text: string, tone: string, actions: list<array{label: string, path: string}>}
     */
    public function respond(User $user, string $message, ?string $page): array
    {
        $user->loadMissing('tenant');
        abort_unless(($user->isPatient() || $user->isManager()) && $user->tenant, 403, 'Usuário sem vínculo com um cliente.');

        $profile = $user->isManager() ? 'manager' : 'patient';
        $modules = $profile === 'patient' ? $this->plans->effectivePlan($user->tenant)['modules'] : [];
        $intent = $this->rules->intentFor($message);

        if ($intent === 'navigation' && config('ai.max.enabled')) {
            $input = new AssistantInput(
                message: $this->redact($message),
                profile: $profile,
                page: in_array($page, $this->catalog->knownPaths($profile), true) ? $page : null,
                correlationId: (string) Str::uuid(),
            );

            $intent = $this->classify($input);

            if ($intent === 'navigation' && ($reply = $this->generate($input, $profile, $modules))) {
                return $reply;
            }
        }

        return $this->fixed($intent, $profile, $modules);
    }

    private function classify(AssistantInput $input): string
    {
        try {
            $decision = $this->intentProvider->classifyIntent($input);
        } catch (Throwable $exception) {
            $this->logFallback($exception);

            return 'navigation';
        }

        if ($decision->emergencyProbability >= (float) config('ai.max.emergency_probability')) {
            return 'emergency';
        }

        return $decision->confidence >= (float) config('ai.max.min_confidence') ? $decision->intent : 'navigation';
    }

    /**
     * @param  array<string, bool>  $modules
     * @return array{text: string, tone: string, actions: list<array{label: string, path: string}>}|null
     */
    private function generate(AssistantInput $input, string $profile, array $modules): ?array
    {
        try {
            $reply = $this->replyProvider->reply($input, $this->catalog->allowedKeys($profile, $modules));
        } catch (Throwable $exception) {
            $this->logFallback($exception);

            return null;
        }

        if (! $this->guard->isSafe($reply->message)) {
            Log::warning('max.reply_rejected', ['reason' => 'unsafe_content']);

            return null;
        }

        return [
            'text' => $reply->message,
            'tone' => 'info',
            'actions' => $this->catalog->resolve($reply->actions, $profile, $modules),
        ];
    }

    /**
     * Same answers as the demo assistant (DemoMaxService).
     *
     * @param  array<string, bool>  $modules
     * @return array{text: string, tone: string, actions: list<array{label: string, path: string}>}
     */
    private function fixed(string $intent, string $profile, array $modules): array
    {
        [$text, $keys] = match ($intent) {
            'emergency' => ['Ligue 192, SAMU, ou procure o pronto atendimento mais perto. Eu não avalio sinais de gravidade. Não espere atendimento por vídeo.', []],
            'privacy' => ['A trilha de saúde mental ainda não está disponível. O gestor poderá consultar informações agregadas do grupo, nunca dados individuais de saúde mental.', []],
            'medication' => ['Eu não sugiro troca de medicamento. Essa decisão é do seu médico. Você pode conferir sua receita; a cobertura real ainda depende da lista oficial.', $profile === 'patient' ? ['farmacia'] : []],
            'referral' => ['Quem avalia o que você está sentindo é um profissional de saúde. Posso ajudar você a encontrar atendimento.', $profile === 'patient' ? ['ajuda'] : []],
            default => $profile === 'manager'
                ? ['Posso ajudar a consultar pacientes, acompanhar consultas, configurar os módulos dos planos e revisar a identidade visual.', ['pacientes', 'consultas', 'planos']]
                : ['Posso ajudar com suas consultas, seu plano e seu cadastro. A marcação com especialista segue as regras do seu contrato.', ['consultas', 'conta']],
        };

        return [
            'text' => $text,
            'tone' => $intent === 'emergency' ? 'danger' : 'info',
            'actions' => $intent === 'emergency'
                ? [['label' => 'Ligar 192, SAMU', 'path' => 'tel:192']]
                : $this->catalog->resolve($keys, $profile, $modules),
        ];
    }

    /**
     * Data minimisation before anything leaves the server.
     */
    private function redact(string $message): string
    {
        return (string) preg_replace([
            '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/',
            '/[^\s@]+@[^\s@]+\.[^\s@]+/',
            '/\(?\b\d{2}\)?\s?9?\d{4}-?\d{4}\b/',
        ], '[dado removido]', $message);
    }

    private function logFallback(Throwable $exception): void
    {
        Log::warning('max.ai_fallback', $exception instanceof AiProviderException
            ? ['provider' => $exception->provider, 'code' => $exception->errorCode, 'duration_ms' => $exception->durationMs]
            : ['exception' => $exception::class]);
    }
}
