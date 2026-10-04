<?php

namespace App\Http\Requests\Healthcare;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManagerConsultationSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManager() === true && $this->user()?->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:20', new Cpf],
            'status' => ['nullable', Rule::in(ConsultationHistoryRequest::STATUSES)],
            'doctor_cpf' => ['nullable', 'string', 'max:20', new Cpf],
            'start_date_min' => ['nullable', 'date'],
            'start_date_max' => ['nullable', 'date', 'after_or_equal:start_date_min'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'tenant_id' => ['prohibited'],
            'cpf' => ['prohibited'],
        ];
    }
}
