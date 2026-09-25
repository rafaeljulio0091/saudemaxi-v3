<?php

namespace App\AI\Support;

final class TriageStructuredOutputSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        $text = ['type' => 'string'];
        $list = ['type' => 'array', 'items' => $text];

        return [
            'type' => 'object',
            'properties' => [
                'message_to_patient' => $text,
                'summary' => $text,
                'chief_complaint' => $text,
                'symptoms' => $list,
                'onset' => $text,
                'duration' => $text,
                'intensity' => $text,
                'evolution' => $text,
                'additional_information' => $text,
                'missing_information' => $list,
                'conversation_complete' => ['type' => 'boolean'],
                'requires_human_review' => ['type' => 'boolean'],
            ],
            'required' => [
                'message_to_patient',
                'summary',
                'chief_complaint',
                'symptoms',
                'onset',
                'duration',
                'intensity',
                'evolution',
                'additional_information',
                'missing_information',
                'conversation_complete',
                'requires_human_review',
            ],
            'additionalProperties' => false,
        ];
    }
}
