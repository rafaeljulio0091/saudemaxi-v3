<?php

namespace App\Services\Healthcare;

use App\Models\Tenant;
use App\Models\User;

/**
 * Produces the same "healthcare" prop shape DemoContext::props() builds,
 * but backed by a real tenant and authenticated user, so the existing
 * Vue pages work unchanged whether they are in demo or real mode.
 */
class TenantHealthcareContext
{
    public function __construct(private Tenant $tenant, private User $user) {}

    public function authorize(string $profile, ?string $module = null): void
    {
        abort_unless($this->user->role === $profile, 403, 'Esta área pertence a outro perfil.');
        if ($module) {
            abort_unless($this->tenant->moduleEnabled($module), 403, 'Este serviço não está incluído no seu plano.');
        }
    }

    public function props(): array
    {
        return [
            'demo' => false,
            'profile' => $this->user->role,
            'tenant' => [
                'id' => $this->tenant->id,
                'nome' => $this->tenant->name,
                'tipo' => $this->tenant->type,
                'subdominio' => $this->tenant->subdomain,
                'saudacao' => 'Bem-vindo à telemedicina da '.$this->tenant->name,
                'cor' => $this->tenant->brand_color ?: '#5E5212',
                'modulos' => $this->tenant->modules,
                'regulacao' => $this->tenant->regulacao,
            ],
            'patient' => $this->user->isPatient() ? $this->patient() : null,
            'plan' => [
                'id' => null,
                'nome' => 'Contrato '.$this->tenant->name,
                'cliente' => $this->tenant->subdomain,
                'maxDependentes' => 0,
                'modulos' => $this->tenant->modules,
            ],
            'modules' => $this->tenant->modules,
            'key' => $this->tenant->subdomain.':'.$this->user->role.':'.$this->user->id,
            'basePath' => '',
            'apiBase' => '/api/healthcare',
        ];
    }

    private function patient(): array
    {
        return [
            'id' => $this->user->id,
            'nome' => $this->user->name,
            'cpf' => $this->user->cpf,
            'nascimento' => $this->user->birth_date?->toDateString(),
            'telefone' => $this->user->phone,
            'email' => $this->user->email,
            'titular' => true,
            'cliente' => $this->tenant->subdomain,
            'status' => 'ACTIVE',
            'dependentes' => 0,
        ];
    }
}
