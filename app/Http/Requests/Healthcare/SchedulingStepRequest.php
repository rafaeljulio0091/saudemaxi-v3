<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;

class SchedulingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPatient() && $this->user()->tenant_id !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = match ($this->route('scheduling_step')) {
            'days' => [
                'specialty_id' => ['required', 'integer', 'min:1'],
            ],
            'times' => [
                'specialty_id' => ['required', 'integer', 'min:1'],
                'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            ],
            'doctors' => [
                'specialty_id' => ['required', 'integer', 'min:1'],
                'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
                'time' => ['required', 'date_format:H:i'],
            ],
            default => abort(404),
        };

        return [
            ...$rules,
            ...$this->protectedContextRules(),
        ];
    }

    /** @return array<string, array<int, string>> */
    private function protectedContextRules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'patient_cpf' => ['prohibited'],
            'cpf' => ['prohibited'],
            'price' => ['prohibited'],
            'is_paid' => ['prohibited'],
        ];
    }
}
