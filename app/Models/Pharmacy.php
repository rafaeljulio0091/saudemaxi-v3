<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pharmacy extends Model
{
    use HasPublicUuid, SoftDeletes;

    protected $fillable = [
        'name',
        'corporate_name',
        'cnpj',
        'phone',
        'email',
        'is_public',
        'is_active',
        'data_source',
    ];

    protected $hidden = ['tenant_id', 'municipality_id', 'cnpj', 'cnpj_hash', 'phone', 'email'];

    protected function casts(): array
    {
        return [
            'cnpj' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
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

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function externalIdentities(): MorphMany
    {
        return $this->morphMany(ExternalIdentity::class, 'external_identifiable');
    }
}
