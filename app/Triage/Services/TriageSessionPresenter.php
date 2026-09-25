<?php

namespace App\Triage\Services;

use App\Models\TriageSession;

class TriageSessionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(TriageSession $session): array
    {
        $session->loadMissing(['messages', 'assessments' => fn ($query) => $query->limit(1)]);
        $assessment = $session->assessments->first();

        return [
            'id' => $session->id,
            'status' => $session->status->value,
            'started_at' => $session->started_at->toIso8601String(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'messages' => $session->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->sender->value === 'patient' ? 'user' : 'assistant',
                'text' => $message->content,
                'created_at' => $message->created_at->toIso8601String(),
            ])->values(),
            'routing' => $assessment ? [
                'classification' => $assessment->classification->value,
                'requires_human_review' => $assessment->requires_human_review,
            ] : null,
        ];
    }
}
