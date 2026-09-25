<?php

namespace App\AI\DTO;

use App\AI\Exceptions\InvalidProviderResponse;
use App\Triage\Enums\TriageClassification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class DecisionResult
{
    /**
     * @param  array<string, float|int>  $probabilities
     */
    public function __construct(
        public TriageClassification $classification,
        public float $confidence,
        public array $probabilities,
        public float $priorityScore,
        public float $humanReviewProbability,
        public float $emergencyProbability,
        public string $model,
        public int $durationMs,
        public ?int $inputTokens,
        public ?int $outputTokens,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromProvider(
        array $payload,
        string $configuredModel,
        int $durationMs,
    ): self {
        try {
            $validated = Validator::make($payload, [
                'model' => ['nullable', 'string', 'max:100'],
                'answers.triage_route.choice' => ['required', 'string', Rule::enum(TriageClassification::class)],
                'answers.triage_route.confidence' => ['required', 'numeric', 'between:0,1'],
                'answers.triage_route.probabilities' => ['required', 'array'],
                'answers.triage_route.probabilities.*' => ['numeric', 'between:0,1'],
                'answers.priority.score' => ['required', 'numeric', 'between:0,3'],
                'answers.requires_human_review.noul' => ['required', 'numeric', 'between:0,1'],
                'answers.possible_emergency.noul' => ['required', 'numeric', 'between:0,1'],
                'usage.input_tokens' => ['nullable', 'integer', 'min:0'],
                'usage.output_tokens' => ['nullable', 'integer', 'min:0'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new InvalidProviderResponse('jev', $configuredModel, 'invalid_schema', $durationMs, $exception);
        }

        $route = $validated['answers']['triage_route'];

        return new self(
            classification: TriageClassification::from($route['choice']),
            confidence: (float) $route['confidence'],
            probabilities: array_map('floatval', $route['probabilities']),
            priorityScore: (float) $validated['answers']['priority']['score'],
            humanReviewProbability: (float) $validated['answers']['requires_human_review']['noul'],
            emergencyProbability: (float) $validated['answers']['possible_emergency']['noul'],
            model: $validated['model'] ?? $configuredModel,
            durationMs: $durationMs,
            inputTokens: $validated['usage']['input_tokens'] ?? null,
            outputTokens: $validated['usage']['output_tokens'] ?? null,
        );
    }
}
