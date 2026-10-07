<?php

namespace App\Http\Requests\Healthcare;

use App\Models\ConsultationAppointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchConsultationAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ConsultationAppointment::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(ConsultationHistoryRequest::STATUSES)],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
            'cpf' => ['prohibited'],
        ];
    }
}
