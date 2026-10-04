<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthProfessional extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'professional_type',
        'registration_number',
        'registration_state',
        'registration_authority',
        'specialty',
        'status',
    ];

    protected $hidden = ['tenant_id', 'user_id'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
