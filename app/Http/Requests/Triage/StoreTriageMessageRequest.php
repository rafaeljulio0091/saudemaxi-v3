<?php

namespace App\Http\Requests\Triage;

use App\Models\TriageSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreTriageMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('triageSession');

        return $session instanceof TriageSession
            && $this->user()?->can('sendMessage', $session) === true;
    }

    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'min:2',
                'max:'.config('triage.max_message_length'),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (preg_match('/[^\P{C}\r\n\t]/u', (string) $value)) {
                        $fail('A mensagem contém caracteres não permitidos.');
                    }
                },
            ],
            'request_id' => ['nullable', 'uuid'],
            'tenant_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
            'session_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('message'))) {
            $this->merge(['message' => trim($this->input('message'))]);
        }
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Escreva uma mensagem.',
            'message.min' => 'Escreva uma mensagem um pouco mais detalhada.',
            'message.max' => 'A mensagem excede o tamanho permitido.',
            'prohibited' => 'Este campo não pode ser informado.',
        ];
    }
}
