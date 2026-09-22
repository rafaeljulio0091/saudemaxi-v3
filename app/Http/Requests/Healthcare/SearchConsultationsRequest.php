<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;

class SearchConsultationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
