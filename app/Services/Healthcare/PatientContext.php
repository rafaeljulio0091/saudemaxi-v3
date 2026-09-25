<?php

namespace App\Services\Healthcare;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientContext
{
    private ?Patient $patient = null;

    public function __construct(private Request $request) {}

    public function hasPatient(): bool
    {
        return $this->request->user()?->patient !== null;
    }

    public function current(): Patient
    {
        return $this->patient ??= $this->request->user()->loadMissing('patient.tenant', 'patient.plan')->patient
            ?? abort(403, 'Sua conta ainda não está vinculada a um perfil de paciente. Fale com o administrador da sua unidade.');
    }

    public function moduleEnabled(string $module): bool
    {
        return $this->current()->plan->moduleEnabled($module);
    }

    public function props(): array
    {
        $patient = $this->current();
        $tenant = $patient->tenant;
        $plan = $patient->plan;

        return [
            'demo' => false,
            'profile' => 'patient',
            'tenant' => [
                'id' => $tenant->id,
                'nome' => $tenant->nome,
                'subdominio' => $tenant->subdominio,
                'saudacao' => $tenant->saudacao,
                'cor' => $tenant->cor,
                'regulacao' => $tenant->regulacao,
            ],
            'patient' => [
                'id' => $patient->id,
                'nome' => $patient->nome,
                'cpf' => $patient->cpf,
                'email' => $patient->email,
                'titular' => $patient->titular,
                'status' => $patient->status,
            ],
            'plan' => [
                'id' => $plan->id,
                'nome' => $plan->nome,
                'maxDependentes' => $plan->max_dependentes,
            ],
            'modules' => $plan->modules,
            'network' => 'normal',
            'key' => 'real:'.$tenant->id.':patient:'.$patient->id.':'.$plan->id,
            'basePath' => '',
            'apiBase' => '/dados',
        ];
    }
}
