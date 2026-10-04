<?php

namespace App\Http\Requests\Native;

use App\Models\Pharmacy;
use App\Rules\Cnpj;

class StorePharmacyRequest extends NativeStoreRequest
{
    protected function modelClass(): string
    {
        return Pharmacy::class;
    }

    public function rules(): array
    {
        return [
            'municipality_uuid' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'corporate_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20', new Cnpj],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_public' => ['required', 'boolean'],
            ...$this->addressRules(),
            ...$this->protectedRules(),
            'data_source' => ['prohibited'],
            'external_provider' => ['prohibited'],
            'external_id' => ['prohibited'],
        ];
    }
}
