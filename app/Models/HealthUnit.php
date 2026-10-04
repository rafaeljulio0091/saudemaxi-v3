<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthUnit extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = ['name', 'code', 'type', 'phone', 'email', 'status'];

    protected $hidden = ['tenant_id', 'organization_id', 'municipality_id', 'phone', 'email'];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'status' => RecordStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(HealthProfessional::class)
            ->withPivot(['tenant_id', 'status', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class)
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
