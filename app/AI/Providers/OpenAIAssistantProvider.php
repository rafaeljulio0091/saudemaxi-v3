<?php

namespace App\AI\Providers;

use App\AI\Contracts\AssistantReplyProvider;
use App\AI\DTO\AssistantInput;
use App\AI\DTO\AssistantReplyResult;
use App\AI\Exceptions\AiProviderException;
use App\AI\Exceptions\InvalidProviderResponse;
use App\AI\Support\MaxPrompt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Navigation replies for the MAX assistant, using the same OpenAI Responses
 * API, credentials and structured-output approach as OpenAIProvider (triage).
 */
class OpenAIAssistantProvider implements AssistantReplyProvider
{
    public function __construct(private MaxPrompt $prompt) {}

    public function reply(AssistantInput $input, array $allowedActions): AssistantReplyResult
    {
        $model = (string) config('ai.max.openai_model');
        $started = hrtime(true);

        if (! config('ai.max.enabled') || blank(config('ai.openai.api_key')) || blank($model) || $allowedActions === []) {
            throw new AiProviderException('openai', $model, 'not_configured');
        }

        try {
            $response = Http::baseUrl(config('ai.openai.base_url'))
                ->withToken(config('ai.openai.api_key'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(config('ai.openai.connect_timeout'))
                ->timeout(config('ai.max.openai_timeout'))
                ->withHeaders(['X-Client-Request-Id' => $input->correlationId])
                ->post('/responses', [
                    'model' => $model,
                    'instructions' => $this->prompt->instructions(),
                    'input' => [[
                        'role' => 'user',
                        'content' => implode("\n", [
                            'Perfil: '.($input->profile === 'manager' ? 'gestor da clínica' : 'paciente'),
                            'Página atual: '.($input->page ?? 'não informada'),
                            'Ações permitidas: '.implode(', ', $allowedActions),
                            'Mensagem: '.$input->message,
                        ]),
                    ]],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'max_assistant_reply',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => ['message_to_user', 'actions'],
                                'properties' => [
                                    'message_to_user' => ['type' => 'string'],
                                    'actions' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'enum' => $allowedActions],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw new AiProviderException('openai', $model, 'connection_error', $this->elapsed($started), $exception);
        } catch (Throwable $exception) {
            throw new AiProviderException('openai', $model, 'request_error', $this->elapsed($started), $exception);
        }

        if (! $response->successful()) {
            throw new AiProviderException('openai', $model, $this->errorCode($response), $this->elapsed($started));
        }

        $decoded = json_decode($this->outputText($response, $model, $started), true);

        if (! is_array($decoded)) {
            throw new InvalidProviderResponse('openai', $model, 'invalid_json', $this->elapsed($started));
        }

        return AssistantReplyResult::fromProvider($decoded, $allowedActions, $model, $this->elapsed($started));
    }

    private function outputText(Response $response, string $model, int $started): string
    {
        if ($response->json('status') === 'incomplete') {
            throw new InvalidProviderResponse('openai', $model, 'incomplete_response', $this->elapsed($started));
        }

        foreach ((array) $response->json('output', []) as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'refusal') {
                    throw new AiProviderException('openai', $model, 'refusal', $this->elapsed($started));
                }

                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        throw new InvalidProviderResponse('openai', $model, 'missing_output', $this->elapsed($started));
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
