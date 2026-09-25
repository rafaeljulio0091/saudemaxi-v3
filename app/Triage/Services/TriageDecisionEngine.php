<?php

namespace App\Triage\Services;

use App\AI\DTO\ConversationResult;
use App\AI\DTO\DecisionResult;
use App\Triage\Enums\TriageClassification;
use App\Triage\Enums\TriageStatus;

class TriageDecisionEngine
{
    /**
     * @return array{classification: TriageClassification, status: TriageStatus, requires_human_review: bool}
     */
    public function decide(ConversationResult $conversation, DecisionResult $decision): array
    {
        $classification = $decision->classification;
        $requiresHumanReview = $conversation->requiresHumanReview
            || $decision->confidence < config('triage.min_confidence')
            || $decision->humanReviewProbability >= config('triage.human_review_probability');

        if ($decision->emergencyProbability >= config('triage.emergency_probability')) {
            $classification = TriageClassification::Emergency;
        } elseif ($requiresHumanReview) {
            $classification = TriageClassification::HumanReview;
        }

        $status = match (true) {
            $classification === TriageClassification::Emergency => TriageStatus::Emergency,
            $classification === TriageClassification::Priority,
            $classification === TriageClassification::HumanReview => TriageStatus::HumanReview,
            $conversation->conversationComplete => TriageStatus::Completed,
            default => TriageStatus::Active,
        };

        return [
            'classification' => $classification,
            'status' => $status,
            'requires_human_review' => $requiresHumanReview
                || in_array($classification, [
                    TriageClassification::Emergency,
                    TriageClassification::Priority,
                    TriageClassification::HumanReview,
                ], true),
        ];
    }
}
