<?php

namespace App\AI\Providers;

use App\AI\Contracts\DecisionAIProvider;
use App\AI\DTO\DecisionInput;
use App\AI\DTO\DecisionResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class JevProvider implements DecisionAIProvider
{
    public function classify(DecisionInput $input): DecisionResult
    {
        $model = (string) config('ai.jev.model');
        $started = hrtime(true);

        if (! config('ai.jev.enabled') || blank(config('ai.jev.api_key')) || blank($model)) {
            throw new AiProviderException('jev', $model, 'not_configured');
        }

        try {
            $response = Http::withToken(config('ai.jev.api_key'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(config('ai.jev.connect_timeout'))
                ->timeout(config('ai.jev.timeout'))
                ->withHeaders(['X-Request-ID' => $input->correlationId])
                ->post(config('ai.jev.api_url'), [
                    'state' => $input->state,
                    'model' => $model,
                    'questions' => $this->questions(),
                ]);
        } catch (ConnectionException $exception) {
            throw new AiProviderException('jev', $model, 'connection_error', $this->elapsed($started), $exception);
        } catch (Throwable $exception) {
            throw new AiProviderException('jev', $model, 'request_error', $this->elapsed($started), $exception);
        }

        if (! $response->successful()) {
            throw new AiProviderException('jev', $model, $this->errorCode($response), $this->elapsed($started));
        }

        return DecisionResult::fromProvider($response->json() ?? [], $model, $this->elapsed($started));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            'triage_route' => [
                'type' => 'choice',
                'instructions' => 'Classifique somente a rota de encaminhamento, sem concluir doenças ou substituir avaliação profissional.',
                'criteria' => [
                    'emergency' => 'Há contexto que pode exigir serviço de emergência imediato.',
                    'priority' => 'Há necessidade de avaliação profissional prioritária, sem indicação inequívoca de emergência.',
                    'standard' => 'Há informações suficientes para atendimento convencional.',
                    'administrative' => 'A necessidade é administrativa e não clínica.',
                    'human_review' => 'As informações são insuficientes, conflitantes ou incertas para outra rota.',
                ],
            ],
            'priority' => [
                'type' => 'score',
                'instructions' => 'Estime apenas a prioridade relativa para revisão humana.',
                'criteria' => [
                    'Atendimento convencional',
                    'Revisão em prazo breve',
                    'Revisão prioritária',
                    'Possível encaminhamento imediato',
                ],
            ],
            'requires_human_review' => [
                'type' => 'noul',
                'instructions' => 'As informações exigem avaliação humana por dúvida, inconsistência ou limitação da automação.',
            ],
            'possible_emergency' => [
                'type' => 'noul',
                'instructions' => 'O contexto pode exigir serviço de emergência imediato.',
            ],
        ];
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function errorCode(Response $response): string
    {
        return match (true) {
            in_array($response->status(), [401, 403], true) => 'unauthorized',
            $response->status() === 429 => 'rate_limited',
            $response->serverError() => 'unavailable',
            default => 'rejected',
        };
    }
}
