<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipality extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = ['name', 'state', 'ibge_code', 'status'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function healthUnits(): HasMany
    {
        return $this->hasMany(HealthUnit::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function pharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class);
    }
}
