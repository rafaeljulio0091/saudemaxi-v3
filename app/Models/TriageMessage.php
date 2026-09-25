<?php

namespace App\Models;

use App\Triage\Enums\TriageSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriageMessage extends Model
{
    protected $fillable = [
        'tenant_id',
        'triage_session_id',
        'sender',
        'sequence',
        'request_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'sender' => TriageSender::class,
            'content' => 'encrypted',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TriageSession::class, 'triage_session_id');
    }
}
