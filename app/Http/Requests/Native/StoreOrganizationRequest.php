<?php

namespace App\Http\Requests\Native;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Rules\Cnpj;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends NativeStoreRequest
{
    protected function modelClass(): string
    {
        return Organization::class;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20', new Cnpj],
            'municipality_uuid' => ['nullable', 'uuid'],
            ...$this->addressRules(),
            ...$this->protectedRules(),
        ];
    }
}
