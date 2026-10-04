<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'social_name',
        'birth_date',
        'sex',
        'cpf',
        'cns',
        'email',
        'phone',
        'status',
    ];

    protected $hidden = [
        'tenant_id',
        'municipality_id',
        'user_id',
        'plan_id',
        'holder_patient_id',
        'cpf',
        'cpf_hash',
        'cns',
        'cns_hash',
        'email',
        'email_hash',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
            'cpf' => 'encrypted',
            'cns' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'status' => RecordStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(self::class, 'holder_patient_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(self::class, 'holder_patient_id');
    }

    public function healthUnits(): BelongsToMany
    {
        return $this->belongsToMany(HealthUnit::class)
            ->withPivot(['tenant_id', 'status', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function externalIdentities(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'external_identifiable');
    }
}
