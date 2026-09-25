<?php

namespace App\AI\DTO;

use App\AI\Exceptions\InvalidProviderResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class ConversationResult
{
    /**
     * @param  list<string>  $symptoms
     * @param  list<string>  $missingInformation
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public string $message,
        public string $summary,
        public array $symptoms,
        public array $missingInformation,
        public bool $conversationComplete,
        public bool $requiresHumanReview,
        public array $state,
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
        string $model,
        int $durationMs,
        ?int $inputTokens,
        ?int $outputTokens,
    ): self {
        try {
            $validated = Validator::make($payload, [
                'message_to_patient' => ['required', 'string', 'max:3000'],
                'summary' => ['present', 'string', 'max:4000'],
                'chief_complaint' => ['present', 'string', 'max:1000'],
                'symptoms' => ['present', 'array', 'max:30'],
                'symptoms.*' => ['string', 'max:300'],
                'onset' => ['present', 'string', 'max:500'],
                'duration' => ['present', 'string', 'max:500'],
                'intensity' => ['present', 'string', 'max:500'],
                'evolution' => ['present', 'string', 'max:500'],
                'additional_information' => ['present', 'string', 'max:2000'],
                'missing_information' => ['present', 'array', 'max:20'],
                'missing_information.*' => ['string', 'max:300'],
                'conversation_complete' => ['required', 'boolean'],
                'requires_human_review' => ['required', 'boolean'],
            ])->validate();
        } catch (ValidationException $exception) {
            throw new InvalidProviderResponse('openai', $model, 'invalid_schema', $durationMs, $exception);
        }

        $state = [
            'chief_complaint' => $validated['chief_complaint'],
            'symptoms' => array_values($validated['symptoms']),
            'onset' => $validated['onset'],
            'duration' => $validated['duration'],
            'intensity' => $validated['intensity'],
            'evolution' => $validated['evolution'],
            'additional_information' => $validated['additional_information'],
            'summary' => $validated['summary'],
            'missing_information' => array_values($validated['missing_information']),
            'conversation_complete' => $validated['conversation_complete'],
        ];

        return new self(
            message: $validated['message_to_patient'],
            summary: $validated['summary'],
            symptoms: array_values($validated['symptoms']),
            missingInformation: array_values($validated['missing_information']),
            conversationComplete: $validated['conversation_complete'],
            requiresHumanReview: $validated['requires_human_review'],
            state: $state,
            model: $model,
            durationMs: $durationMs,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
        );
    }
}
