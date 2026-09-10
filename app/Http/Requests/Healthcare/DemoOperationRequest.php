<?php

namespace App\Http\Requests\Healthcare;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DemoOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('healthcare_demo.context');
    }

    public function rules(): array
    {
        $rules = match ($this->route('operation')) {
            'patients-search', 'consultations-search' => [
                'search' => ['nullable', 'string', 'max:150'],
                'status' => ['nullable', 'string', 'max:40'],
                'holder' => ['nullable', Rule::in(['titular', 'dependente'])],
                'plan_id' => ['nullable', 'integer'],
                'page' => ['required', 'integer', 'min:1', 'max:100000'],
                'per_page' => ['required', 'integer', 'min:1', 'max:50'],
            ],
            'days' => ['specialty_id' => ['required', 'integer']],
            'times' => ['specialty_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d']],
            'doctors' => ['specialty_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i']],
            'schedule' => [
                'specialty_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d'],
                'time' => ['required', 'date_format:H:i'], 'doctor_id' => ['required', 'integer'],
                'request_id' => ['required', 'uuid'],
            ],
            'payment' => ['code' => ['required', 'string', 'max:40'], 'paid' => ['required', 'boolean']],
            'patient' => [
                'id' => ['required', 'integer'], 'nome' => ['required', 'string', 'max:150'],
                'email' => ['nullable', 'email', 'max:150'], 'telefone' => ['nullable', 'string', 'max:30'],
                'planoId' => ['nullable', 'integer'],
            ],
            'create-patient' => [
                'nome' => ['required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:150'],
                'telefone' => ['nullable', 'string', 'max:30'], 'planoId' => ['required', 'integer'],
                'nascimento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            ],
            'plan' => ['id' => ['required', 'integer'], 'module' => ['required', Rule::in(['orientacao', 'atendimento', 'agendamento', 'farmacia'])], 'enabled' => ['required', 'boolean']],
            'branding' => ['nome' => ['required', 'string', 'max:100'], 'cor' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'], 'saudacao' => ['required', 'string', 'max:200']],
            'confirm-item' => ['id' => ['required', 'string', 'max:40'], 'index' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'max:200']],
            'max' => ['message' => ['required', 'string', 'max:2000'], 'page' => ['required', 'string', 'max:100']],
            'emergency', 'photo' => [],
            default => abort(404),
        };

        return [...$rules, 'tenant_id' => ['prohibited'], 'profile' => ['prohibited'], 'patient_id' => ['prohibited']];
    }

    public function messages(): array
    {
        return [
            'required' => 'Preencha este campo.', 'email' => 'Informe um e-mail válido.',
            'max' => 'O conteúdo excede o tamanho permitido.', 'regex' => 'Informe uma cor hexadecimal válida.',
            'prohibited' => 'Este campo não pode ser informado.',
        ];
    }
}
