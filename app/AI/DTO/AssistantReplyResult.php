<?php

namespace App\AI\DTO;

use App\AI\Exceptions\InvalidProviderResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class AssistantReplyResult
{
    /**
     * @param  list<string>  $actions  keys from MaxActionCatalog
     */
    public function __construct(
        public string $message,
        public array $actions,
        public string $model,
        public int $durationMs,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $allowedActions
     */
    public static function fromProvider(array $payload, array $allowedActions, string $model, int $durationMs): self
    {
        try {
            $validated = Validator::make($payload, [
                'message_to_user' => ['required', 'string', 'max:1200'],
                'actions' => ['present', 'array', 'max:3'],
                'actions.*' => ['string', Rule::in($allowedActions)],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new InvalidProviderResponse('openai', $model, 'invalid_schema', $durationMs, $exception);
        }

        return new self(
            message: $validated['message_to_user'],
            actions: array_values(array_unique($validated['actions'])),
            model: $model,
            durationMs: $durationMs,
        );
    }
}
