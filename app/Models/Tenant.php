<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'type',
        'regulacao',
        'brand_color',
        'modules',
    ];

    protected function casts(): array
    {
        return [
            'regulacao' => 'boolean',
            'modules' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function moduleEnabled(string $key): bool
    {
        return (bool) ($this->modules[$key] ?? false);
    }
}
