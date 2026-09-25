<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsultationHistoryRequest extends FormRequest
{
    /**
     * Statuses accepted by /api/clinic/consultation-history/.
     */
    public const STATUSES = [
        'SCHEDULED', 'PENDING', 'WAITING_HELPDESK', 'ONGOING_HELPDESK',
        'WAITING_DOCTOR', 'ONGOING_DOCTOR', 'FINISHED', 'CANCELED',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Optional at the request level so the page still renders before
            // a CPF is entered; LsxMedicalConsultationClient never calls the
            // provider without one (every documented example requires it).
            'cpf' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'doctor_cpf' => ['nullable', 'string', 'max:20'],
            'start_date_min' => ['nullable', 'date'],
            'start_date_max' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
