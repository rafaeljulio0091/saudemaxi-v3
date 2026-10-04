<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExternalIdentity extends Model
{
    protected $fillable = ['provider', 'external_id', 'last_synced_at'];

    protected $hidden = ['tenant_id', 'external_identifiable_type', 'external_identifiable_id', 'external_id'];

    protected function casts(): array
    {
        return ['last_synced_at' => 'datetime'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function externalIdentifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
