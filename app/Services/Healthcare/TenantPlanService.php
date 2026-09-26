<?php

namespace App\Services\Healthcare;

use App\Models\Plan;
use App\Models\Tenant;

/**
 * Tenant-scoped plan/module configuration (Saúde Maxi layer). Until a
 * per-patient plan assignment exists, every patient of a tenant uses the
 * tenant's default plan. Without a stored plan the defaults below apply,
 * which is exactly the behaviour HealthcareContext had before plans existed.
 */
class TenantPlanService
{
    public const DEFAULT_NAME = 'Saúde Maxi';

    public const DEFAULT_MODULES = [
        'orientacao' => true,
        'atendimento' => true,
        'agendamento' => true,
        'farmacia' => true,
        'nr1' => true,
    ];

    /**
     * Modules a manager may toggle. "nr1" stays locked until consent and
     * privacy rules are defined (same rule as the demo screen).
     */
    public const TOGGLEABLE_MODULES = ['orientacao', 'atendimento', 'agendamento', 'farmacia'];

    /**
     * Read-only resolution used by the patient context: never writes.
     *
     * @return array{nome: string, maxDependentes: ?int, modules: array<string, bool>}
     */
    public function effectivePlan(Tenant $tenant): array
    {
        $plan = $this->storedDefault($tenant);

        return [
            'nome' => $plan?->name ?? self::DEFAULT_NAME,
            'maxDependentes' => $plan?->max_dependents,
            'modules' => [...self::DEFAULT_MODULES, ...($plan?->modules ?? [])],
        ];
    }

    /**
     * Plans of the tenant, materialising the default plan on first use so
     * the manager has something to configure.
     *
     * @return list<array<string, mixed>>
     */
    public function list(Tenant $tenant): array
    {
        if (! $this->storedDefault($tenant)) {
            $tenant->plans()->create([
                'name' => self::DEFAULT_NAME,
                'modules' => self::DEFAULT_MODULES,
                'is_default' => true,
            ]);
        }

        return $tenant->plans()->orderByDesc('is_default')->orderBy('id')->get()
            ->map(fn (Plan $plan) => $this->present($plan))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function toggleModule(Tenant $tenant, int $planId, string $module, bool $enabled): array
    {
        // Scoped to the manager's own tenant: another tenant's plan is a 404.
        $plan = $tenant->plans()->findOrFail($planId);
        $plan->modules = [...self::DEFAULT_MODULES, ...$plan->modules, $module => $enabled];
        $plan->save();

        return $this->present($plan);
    }

    private function storedDefault(Tenant $tenant): ?Plan
    {
        return $tenant->plans()->where('is_default', true)->orderBy('id')->first();
    }

    /**
     * Same shape as the demo fixture PLANOS rows used by Manager/Plans.vue.
     *
     * @return array<string, mixed>
     */
    private function present(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'nome' => $plan->name,
            'maxDependentes' => $plan->max_dependents,
            'modulos' => [...self::DEFAULT_MODULES, ...$plan->modules],
        ];
    }
}
