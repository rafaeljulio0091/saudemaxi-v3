<?php

namespace App\AI\Providers;

use App\AI\Contracts\AssistantIntentProvider;
use App\AI\DTO\AssistantInput;
use App\AI\DTO\AssistantIntentResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Business-rule classification for the MAX assistant, using the same Jev
 * endpoint, credentials and request format as JevProvider (triage).
 */
class JevAssistantProvider implements AssistantIntentProvider
{
    public function classifyIntent(AssistantInput $input): AssistantIntentResult
    {
        $model = (string) config('ai.jev.model');
        $started = hrtime(true);

        if (! config('ai.max.enabled') || ! config('ai.jev.enabled') || blank(config('ai.jev.api_key')) || blank($model)) {
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
                    'state' => [
                        'assistant' => 'max',
                        'profile' => $input->profile,
                        'page' => $input->page,
                        'message' => $input->message,
                    ],
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

        return AssistantIntentResult::fromProvider($response->json() ?? [], $model, $this->elapsed($started));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function questions(): array
    {
        return [
            'max_intent' => [
                'type' => 'choice',
                'instructions' => 'Classifique somente o assunto da mensagem enviada ao assistente de navegação, sem avaliar a saúde da pessoa.',
                'criteria' => [
                    'emergency' => 'A mensagem descreve uma situação que pode exigir serviço de emergência imediato.',
                    'privacy' => 'A mensagem trata de saúde mental, NR-1 ou pede dados individuais de outra pessoa.',
                    'medication' => 'A mensagem trata de medicamentos, receitas, doses ou farmácia.',
                    'referral' => 'A mensagem descreve sintomas ou mal-estar e busca avaliação.',
                    'navigation' => 'A mensagem pede ajuda para usar a plataforma, serviços, conta, plano ou consultas.',
                ],
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
