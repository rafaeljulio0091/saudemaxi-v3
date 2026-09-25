<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'subdominio',
        'saudacao',
        'cor',
        'regulacao',
    ];

    protected function casts(): array
    {
        return [
            'regulacao' => 'boolean',
        ];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
