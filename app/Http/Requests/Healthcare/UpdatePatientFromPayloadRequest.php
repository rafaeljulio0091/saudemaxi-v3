<?php

namespace App\Http\Requests\Healthcare;

class UpdatePatientFromPayloadRequest extends UpdatePatientRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid'],
            ...parent::rules(),
        ];
    }
}
