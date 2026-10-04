<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() && $this->user()->tenant_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'specialty_id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'doctor_id' => ['required', 'integer', 'min:0'],
            'is_real_doctor' => ['required', 'boolean'],
            'request_id' => ['required', 'uuid'],
            'tenant_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'patient_cpf' => ['prohibited'],
            'cpf' => ['prohibited'],
            'specialty_name' => ['prohibited'],
            'doctor_name' => ['prohibited'],
            'price' => ['prohibited'],
            'is_paid' => ['prohibited'],
        ];
    }
}
