<?php

namespace App\Models;

use App\Triage\Enums\TriageClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageAssessment extends Model
{
    protected $fillable = [
        'tenant_id',
        'triage_session_id',
        'triage_message_id',
        'classification',
        'confidence',
        'structured_state',
        'requires_human_review',
        'source',
        'provider_model',
        'safety_rule_version',
    ];

    protected function casts(): array
    {
        return [
            'classification' => TriageClassification::class,
            'confidence' => 'float',
            'structured_state' => 'encrypted:array',
            'requires_human_review' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TriageSession::class, 'triage_session_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TriageMessage::class, 'triage_message_id');
    }
}
