<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemoScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app()->environment(['local', 'testing']) && config('healthcare.demo_enabled');
    }

    public function rules(): array
    {
        $fixtures = json_decode(file_get_contents(database_path('fixtures/healthcare-demo.json')), true);

        return [
            'scenario' => ['required', Rule::in(array_keys($fixtures['CLIENTES']))],
            'profile' => ['required', Rule::in(['patient', 'manager'])],
            'plan_id' => ['nullable', 'integer'],
            'network' => ['required', Rule::in(['normal', 'slow', 'error', 'empty'])],
        ];
    }
}
