<?php

namespace App\Http\Requests\Healthcare;

use App\Models\Patient;
use App\Rules\Cns;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Patient::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'social_name' => ['nullable', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:20', new Cpf],
            'cns' => ['nullable', 'string', 'max:20', new Cns],

            'email' => ['nullable', 'email', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'sex' => ['nullable', 'string', 'max:30'],
            'municipality_uuid' => [
                'nullable',
                'uuid',
                Rule::exists('municipalities', 'uuid')->where('tenant_id', $this->user()?->tenant_id),
            ],

            'holder_cpf' => ['nullable', 'string', 'max:20', new Cpf],
            'health_unit_uuids' => ['nullable', 'array', 'max:50'],
            'health_unit_uuids.*' => [
                'uuid',
                'distinct',
                Rule::exists('health_units', 'uuid')->where('tenant_id', $this->user()?->tenant_id),
            ],

            'address' => ['nullable', 'array'],
            'address.street' => ['required_with:address.city,address.state', 'nullable', 'string', 'max:255'],
            'address.number' => ['nullable', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['nullable', 'string', 'max:255'],
            'address.district' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address.street,address.state', 'nullable', 'string', 'max:255'],
            'address.state' => ['required_with:address.street,address.city', 'nullable', 'string', 'size:2'],
            'address.zip_code' => ['nullable', 'string', 'max:12'],

            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'role' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'status' => ['prohibited'],
            'data_source' => ['prohibited'],
            'external_provider' => ['prohibited'],
            'external_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $address = $this->input('address');
        if (is_array($address) && collect($address)->filter(fn ($value) => filled($value))->isEmpty()) {
            $this->merge(['address' => null]);
        }
    }
}
