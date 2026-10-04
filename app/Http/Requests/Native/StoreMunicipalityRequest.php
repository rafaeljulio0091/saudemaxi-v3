<?php

namespace App\Http\Requests\Native;

use App\Models\Municipality;

class StoreMunicipalityRequest extends NativeStoreRequest
{
    protected function modelClass(): string
    {
        return Municipality::class;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'ibge_code' => ['nullable', 'digits:7'],
            ...$this->protectedRules(),
        ];
    }
}
