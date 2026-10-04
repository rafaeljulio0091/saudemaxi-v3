<?php

namespace App\Http\Requests\Native;

use App\Models\HealthProfessional;

class StoreHealthProfessionalRequest extends NativeStoreRequest
{
    protected function modelClass(): string
    {
        return HealthProfessional::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'professional_type' => ['required', 'string', 'max:60'],
            'registration_number' => ['required', 'string', 'max:80'],
            'registration_state' => ['nullable', 'string', 'size:2'],
            'registration_authority' => ['required', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'user_uuid' => ['nullable', 'uuid'],
            'health_unit_uuids' => ['nullable', 'array', 'max:50'],
            'health_unit_uuids.*' => ['uuid', 'distinct'],
            ...$this->addressRules(),
            ...$this->protectedRules(),
        ];
    }
}
