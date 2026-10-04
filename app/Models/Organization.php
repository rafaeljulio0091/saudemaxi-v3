<?php

namespace App\Models;

use App\Enums\OrganizationType;
use App\Enums\RecordStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = ['type', 'legal_name', 'trade_name', 'cnpj', 'status'];

    protected $hidden = ['tenant_id', 'municipality_id', 'cnpj', 'cnpj_hash'];

    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'status' => RecordStatus::class,
            'cnpj' => 'encrypted',
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

    public function healthUnits(): HasMany
    {
        return $this->hasMany(HealthUnit::class);
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
