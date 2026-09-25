<?php

return [
    'max_message_length' => (int) env('TRIAGE_MAX_MESSAGE_LENGTH', 2000),
    'max_messages_per_session' => (int) env('TRIAGE_MAX_MESSAGES_PER_SESSION', 40),
    'context_message_limit' => (int) env('TRIAGE_CONTEXT_MESSAGE_LIMIT', 20),
    'min_confidence' => (float) env('TRIAGE_AI_MIN_CONFIDENCE', 0.85),
    'human_review_probability' => (float) env('TRIAGE_HUMAN_REVIEW_PROBABILITY', 0.50),
    'emergency_probability' => (float) env('TRIAGE_EMERGENCY_PROBABILITY', 0.50),
    'lock_seconds' => (int) env('TRIAGE_LOCK_SECONDS', 45),

    'safety' => [
        'version' => env('TRIAGE_SAFETY_RULE_VERSION', 'pending-clinical-approval'),
        /*
         * Deliberately empty until a responsible clinician approves and versions
         * each rule. Tests inject rules to verify the deterministic mechanism.
         *
         * Shape:
         * ['id' => 'approved-id', 'terms_any' => ['approved term']]
         */
        'rules' => [],
    ],

    'emergency_contacts' => [
        ['label' => 'SAMU', 'phone' => '192'],
    ],
];
