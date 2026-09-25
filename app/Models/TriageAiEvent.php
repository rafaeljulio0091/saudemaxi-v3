<?php

namespace App\Models;

use App\Triage\Enums\TriageClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageAiEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'triage_session_id',
        'correlation_id',
        'provider',
        'model',
        'operation',
        'duration_ms',
        'successful',
        'input_tokens',
        'output_tokens',
        'classification',
        'confidence',
        'fallback_used',
        'safety_rule_version',
        'error_code',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'fallback_used' => 'boolean',
            'classification' => TriageClassification::class,
            'confidence' => 'float',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TriageSession::class, 'triage_session_id');
    }
}
