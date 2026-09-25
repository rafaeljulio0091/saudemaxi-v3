<?php

namespace App\Http\Requests\Triage;

use Illuminate\Foundation\Http\FormRequest;

class StartTriageSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() === true && $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'consent' => ['required', 'accepted'],
            'tenant_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Confirme que leu as informações antes de iniciar.',
            'prohibited' => 'Este campo não pode ser informado.',
        ];
    }
}
