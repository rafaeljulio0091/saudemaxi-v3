<?php

namespace App\Http\Requests\Healthcare;

use App\Models\Pharmacy;
use Illuminate\Foundation\Http\FormRequest;

class NearbyPharmaciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewDirectory', Pharmacy::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'tenant_id' => ['prohibited'],
            'municipality_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
            'user_id' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'latitude.between' => 'A latitude informada é inválida.',
            'longitude.between' => 'A longitude informada é inválida.',
            'accuracy.max' => 'A precisão da localização informada é inválida.',
        ];
    }
}
