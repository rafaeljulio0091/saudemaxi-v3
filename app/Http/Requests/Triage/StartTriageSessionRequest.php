<?php

namespace App\Http\Requests\Triage;

use App\Services\Healthcare\TenantPlanService;
use Illuminate\Foundation\Http\FormRequest;

class StartTriageSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isPatient() === true
            && $user->tenant !== null
            && app(TenantPlanService::class)->effectivePlan($user->tenant)['modules']['orientacao'];
    }

    public function rules(): array
    {
        return [
            'consent' => ['required', 'accepted'],
            'tenant_id' => ['prohibited'],
            'patient_id' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Confirme que leu as informações antes de iniciar.',
            'prohibited' => 'Este campo não pode ser informado.',
        ];
    }
}
