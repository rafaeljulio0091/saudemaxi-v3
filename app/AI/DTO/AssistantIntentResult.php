<?php

namespace App\AI\DTO;

use App\AI\Exceptions\InvalidProviderResponse;
use App\Max\Rules\MaxRuleEngine;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class AssistantIntentResult
{
    public function __construct(
        public string $intent,
        public float $confidence,
        public float $emergencyProbability,
        public string $model,
        public int $durationMs,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromProvider(array $payload, string $configuredModel, int $durationMs): self
    {
        try {
            $validated = Validator::make($payload, [
                'model' => ['nullable', 'string', 'max:100'],
                'answers.max_intent.choice' => ['required', 'string', Rule::in(MaxRuleEngine::INTENTS)],
                'answers.max_intent.confidence' => ['required', 'numeric', 'between:0,1'],
                'answers.possible_emergency.noul' => ['required', 'numeric', 'between:0,1'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new InvalidProviderResponse('jev', $configuredModel, 'invalid_schema', $durationMs, $exception);
        }

        return new self(
            intent: $validated['answers']['max_intent']['choice'],
            confidence: (float) $validated['answers']['max_intent']['confidence'],
            emergencyProbability: (float) $validated['answers']['possible_emergency']['noul'],
            model: $validated['model'] ?? $configuredModel,
            durationMs: $durationMs,
        );
    }
}
