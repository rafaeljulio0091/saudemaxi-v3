<?php

namespace App\AI\Providers;

use App\AI\Contracts\ConversationalAIProvider;
use App\AI\DTO\ConversationInput;
use App\AI\DTO\ConversationResult;
use App\AI\Exceptions\AiProviderException;
use App\AI\Exceptions\InvalidProviderResponse;
use App\AI\Support\TriagePrompt;
use App\AI\Support\TriageStructuredOutputSchema;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIProvider implements ConversationalAIProvider
{
    public function __construct(private TriagePrompt $prompt) {}

    public function respond(ConversationInput $input): ConversationResult
    {
        $model = (string) config('ai.openai.model');
        $started = hrtime(true);

        if (! config('ai.openai.enabled') || blank(config('ai.openai.api_key')) || blank($model)) {
            throw new AiProviderException('openai', $model, 'not_configured');
        }

        try {
            $response = Http::baseUrl(config('ai.openai.base_url'))
                ->withToken(config('ai.openai.api_key'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout(config('ai.openai.connect_timeout'))
                ->timeout(config('ai.openai.timeout'))
                ->withHeaders(['X-Client-Request-Id' => $input->correlationId])
                ->post('/responses', [
                    'model' => $model,
                    'instructions' => $this->prompt->instructions(),
                    'input' => $input->messages,
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'triage_conversation_state',
                            'strict' => true,
                            'schema' => TriageStructuredOutputSchema::schema(),
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

        $text = $this->outputText($response, $model, $started);
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new InvalidProviderResponse('openai', $model, 'invalid_json', $this->elapsed($started));
        }

        return ConversationResult::fromProvider(
            payload: $decoded,
            model: (string) ($response->json('model') ?: $model),
            durationMs: $this->elapsed($started),
            inputTokens: $response->json('usage.input_tokens'),
            outputTokens: $response->json('usage.output_tokens'),
        );
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
