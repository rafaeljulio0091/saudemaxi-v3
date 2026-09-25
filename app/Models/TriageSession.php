<?php

namespace App\Models;

use App\Triage\Enums\TriageStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TriageSession extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'assigned_to',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TriageStatus::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TriageMessage::class)->orderBy('sequence');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(TriageAssessment::class)->latest('id');
    }

    public function aiEvents(): HasMany
    {
        return $this->hasMany(TriageAiEvent::class);
    }
}
