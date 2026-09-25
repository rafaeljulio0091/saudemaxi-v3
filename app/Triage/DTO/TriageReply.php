<?php

namespace App\Triage\DTO;

use App\Models\TriageSession;
use App\Triage\Enums\TriageClassification;

final readonly class TriageReply
{
    /**
     * @param  list<array{label: string, path: string}>  $actions
     */
    public function __construct(
        public TriageSession $session,
        public string $message,
        public TriageClassification $classification,
        public bool $requiresHumanReview,
        public array $actions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session' => [
                'id' => $this->session->id,
                'status' => $this->session->status->value,
            ],
            'message' => [
                'role' => 'assistant',
                'text' => $this->message,
                'tone' => $this->classification === TriageClassification::Emergency ? 'danger' : 'info',
                'actions' => $this->actions,
            ],
            'state' => [
                'classification' => $this->classification->value,
                'requires_human_review' => $this->requiresHumanReview,
                'closed' => $this->session->status->value !== 'active',
            ],
        ];
    }
}
