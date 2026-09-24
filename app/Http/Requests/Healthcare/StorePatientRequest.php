<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mirrors the payload documented for POST /api/clinic/create-patient/.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:20'],

            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:30'],

            'insurance_card_number' => ['nullable', 'string', 'max:60'],
            'insurance_plan_code' => ['nullable', 'string', 'max:60'],
            'plan_adherence_date' => ['nullable', 'date'],
            'plan_expiry_date' => ['nullable', 'date'],

            'holder_cpf' => ['nullable', 'string', 'max:20'],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer'],
            'extra_fields' => ['nullable', 'array'],

            'no_email' => ['nullable', 'boolean'],

            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.number' => ['nullable', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:255'],
            'address.state' => ['nullable', 'string', 'max:2'],
            'address.zip_code' => ['nullable', 'string', 'max:12'],
        ];
    }
}
