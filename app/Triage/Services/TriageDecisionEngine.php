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
            || $decision->humanReviewProbability >= config('triage.human_review_probability')
            || $classification === TriageClassification::HumanReview;

        if ($decision->emergencyProbability >= config('triage.emergency_probability')) {
            $classification = TriageClassification::Emergency;
        }

        if ($classification === TriageClassification::Emergency) {
            return $this->routing(
                TriageClassification::Emergency,
                TriageStatus::Emergency,
                true,
            );
        }

        if ($classification === TriageClassification::Priority) {
            return $this->routing(
                TriageClassification::Priority,
                TriageStatus::HumanReview,
                true,
            );
        }

        if (
            ! $conversation->conversationComplete
            && ! $conversation->requiresHumanReview
            && $conversation->missingInformation !== []
        ) {
            return $this->routing(
                $classification === TriageClassification::HumanReview
                    ? TriageClassification::Standard
                    : $classification,
                TriageStatus::Active,
                false,
            );
        }

        if ($requiresHumanReview) {
            $classification = TriageClassification::HumanReview;
        }

        return $this->routing(
            $classification,
            $requiresHumanReview ? TriageStatus::HumanReview : TriageStatus::Completed,
            $requiresHumanReview,
        );
    }

    /**
     * @return array{classification: TriageClassification, status: TriageStatus, requires_human_review: bool}
     */
    private function routing(
        TriageClassification $classification,
        TriageStatus $status,
        bool $requiresHumanReview,
    ): array {
        return [
            'classification' => $classification,
            'status' => $status,
            'requires_human_review' => $requiresHumanReview,
        ];
    }
}
