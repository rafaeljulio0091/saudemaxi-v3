<?php

namespace App\Models;

use App\Enums\AppointmentSyncStatus;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationAppointment extends Model
{
    use HasPublicUuid;

    protected $fillable = [
        'request_id',
        'provider',
        'provider_consultation_id',
        'consultation_code',
        'specialty_id',
        'specialty_name',
        'doctor_id',
        'doctor_name',
        'is_real_doctor',
        'scheduled_for',
        'provider_status',
        'sync_status',
        'is_paid',
        'price',
        'last_error_reason',
    ];

    protected $hidden = [
        'tenant_id',
        'user_id',
        'provider_consultation_id',
        'specialty_name',
        'doctor_name',
        'last_error_reason',
    ];

    protected function casts(): array
    {
        return [
            'provider_consultation_id' => 'encrypted',
            'specialty_name' => 'encrypted',
            'doctor_name' => 'encrypted',
            'is_real_doctor' => 'boolean',
            'scheduled_for' => 'immutable_datetime',
            'sync_status' => AppointmentSyncStatus::class,
            'is_paid' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
