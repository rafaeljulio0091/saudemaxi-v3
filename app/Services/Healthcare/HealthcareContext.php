<?php

namespace App\Services\Healthcare;

use App\Models\Tenant;
use App\Models\User;

class HealthcareContext
{
    public const DEFAULT_GREETING = 'Cuidado e orientação para você.';

    public function __construct(private TenantPlanService $plans) {}

    /**
     * @return array<string, mixed>
     */
    public function forPatient(User $patient): array
    {
        $patient->loadMissing('tenant');
        abort_unless($patient->isPatient() && $patient->tenant, 403, 'Paciente sem vínculo com um cliente.');

        $tenant = $patient->tenant;
        $plan = $this->plans->effectivePlan($tenant);

        return [
            'demo' => false,
            'profile' => 'patient',
            'tenant' => $this->presentTenant($tenant),
            'patient' => [
                'id' => $patient->id,
                'nome' => $patient->name,
                'cpf' => $patient->cpf,
            ],
            'plan' => ['nome' => $plan['nome'], 'maxDependentes' => $plan['maxDependentes']],
            'modules' => $plan['modules'],
            'network' => 'normal',
            'key' => "{$tenant->id}:patient:{$patient->id}",
            'basePath' => '',
            'apiBase' => '/triagem',
        ];
    }

    /**
     * Context for the real manager screens (Healthcare/Manager/*). The tenant
     * always comes from the authenticated manager, never from the request.
     *
     * @return array<string, mixed>
     */
    public function forManager(User $manager): array
    {
        $manager->loadMissing('tenant');
        abort_unless($manager->isManager() && $manager->tenant, 403, 'Gestor sem vínculo com um cliente.');

        $tenant = $manager->tenant;
        $plan = $this->plans->effectivePlan($tenant);

        return [
            'demo' => false,
            'profile' => 'manager',
            'tenant' => $this->presentTenant($tenant),
            'patient' => null,
            'manager' => ['nome' => $manager->name],
            'plan' => ['nome' => $plan['nome'], 'maxDependentes' => $plan['maxDependentes']],
            'modules' => $plan['modules'],
            'network' => 'normal',
            'key' => "{$tenant->id}:manager:{$manager->id}",
            'basePath' => '',
            'apiBase' => '/gestor/dados',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTenant(Tenant $tenant): array
    {
        return [
            'nome' => $tenant->name,
            'subdominio' => $tenant->slug.'.saudemaxi.com.br',
            'cor' => $tenant->brand_color ?: '#5E5212',
            'saudacao' => $tenant->greeting ?: self::DEFAULT_GREETING,
            'regulacao' => $tenant->regulacao,
        ];
    }
}
