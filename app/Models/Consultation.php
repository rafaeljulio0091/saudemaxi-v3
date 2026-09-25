<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    public const STATUSES = [
        'SCHEDULED',
        'PENDING',
        'WAITING_HELPDESK',
        'ONGOING_HELPDESK',
        'WAITING_DOCTOR',
        'ONGOING_DOCTOR',
        'FINISHED',
        'CANCELED',
    ];

    protected $fillable = [
        'patient_id',
        'codigo',
        'especialidade',
        'medico',
        'status',
        'agendada_para',
        'pago',
        'duracao',
        'request_id',
    ];

    protected function casts(): array
    {
        return [
            'agendada_para' => 'datetime',
            'pago' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
