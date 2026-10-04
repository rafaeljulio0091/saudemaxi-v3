<?php

namespace App\Http\Requests\Native;

use Illuminate\Foundation\Http\FormRequest;

abstract class NativeStoreRequest extends FormRequest
{
    abstract protected function modelClass(): string;

    public function authorize(): bool
    {
        return $this->user()?->can('create', $this->modelClass()) ?? false;
    }

    /** @return array<string, mixed> */
    protected function addressRules(): array
    {
        return [
            'address' => ['nullable', 'array'],
            'address.street' => ['required_with:address.city,address.state', 'nullable', 'string', 'max:255'],
            'address.number' => ['nullable', 'string', 'max:30'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.district' => ['nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address.street,address.state', 'nullable', 'string', 'max:255'],
            'address.state' => ['required_with:address.street,address.city', 'nullable', 'string', 'size:2'],
            'address.zip_code' => ['nullable', 'string', 'max:12'],
        ];
    }

    /** @return array<string, mixed> */
    protected function protectedRules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'is_admin' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }
}
