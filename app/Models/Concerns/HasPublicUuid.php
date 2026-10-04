<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicUuid
{
    protected static function bootHasPublicUuid(): void
    {
        static::creating(function ($model): void {
            if (blank($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
