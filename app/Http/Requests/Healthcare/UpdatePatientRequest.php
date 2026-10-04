<?php

namespace App\Http\Requests\Healthcare;

use App\Enums\RecordStatus;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Patient::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'social_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'sex' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
            'tenant_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'cpf' => ['prohibited'],
            'cpf_hash' => ['prohibited'],
            'role' => ['prohibited'],
            'is_admin' => ['prohibited'],
        ];
    }
}
