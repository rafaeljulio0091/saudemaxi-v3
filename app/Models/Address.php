<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $fillable = [
        'zip_code',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'tenant_id',
        'addressable_type',
        'addressable_id',
        'zip_code',
        'street',
        'number',
        'complement',
        'district',
    ];

    protected function casts(): array
    {
        return [
            'zip_code' => 'encrypted',
            'street' => 'encrypted',
            'number' => 'encrypted',
            'complement' => 'encrypted',
            'district' => 'encrypted',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geocoding_attempted_at' => 'immutable_datetime',
            'geocoded_at' => 'immutable_datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
