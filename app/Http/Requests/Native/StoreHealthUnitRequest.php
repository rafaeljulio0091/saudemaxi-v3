<?php

namespace App\Http\Requests\Native;

use App\Models\HealthUnit;

class StoreHealthUnitRequest extends NativeStoreRequest
{
    protected function modelClass(): string
    {
        return HealthUnit::class;
    }

    public function rules(): array
    {
        return [
            'organization_uuid' => ['required', 'uuid'],
            'municipality_uuid' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:80'],
            'type' => ['required', 'string', 'max:60'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            ...$this->addressRules(),
            ...$this->protectedRules(),
        ];
    }
}
