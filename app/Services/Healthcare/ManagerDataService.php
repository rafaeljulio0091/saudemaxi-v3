<?php

namespace App\Services\Healthcare;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Data behind the real manager screens (painel, planos, identidade). Every
 * query is scoped to the authenticated manager's tenant.
 */
class ManagerDataService
{
    /**
     * Same age bands as the demo fixture FAIXA_ETARIA.
     */
    private const AGE_BANDS = [
        ['faixa' => '0 a 12', 'min' => 0, 'max' => 12],
        ['faixa' => '13 a 17', 'min' => 13, 'max' => 17],
        ['faixa' => '18 a 39', 'min' => 18, 'max' => 39],
        ['faixa' => '40 a 59', 'min' => 40, 'max' => 59],
        ['faixa' => '60 ou mais', 'min' => 60, 'max' => PHP_INT_MAX],
    ];

    public function __construct(private TenantPlanService $plans) {}

    public function read(User $manager, string $resource): mixed
    {
        $tenant = $this->tenantOf($manager);

        return match ($resource) {
            'dashboard' => $this->dashboard($tenant),
            'plans' => $this->plans->list($tenant),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $input  already validated by ManagerDataController
     */
    public function execute(User $manager, string $operation, array $input): mixed
    {
        $tenant = $this->tenantOf($manager);

        return match ($operation) {
            'plan' => $this->plans->toggleModule($tenant, (int) $input['id'], $input['module'], (bool) $input['enabled']),
            'branding' => $this->updateBranding($tenant, $input),
            default => abort(404),
        };
    }

    /**
     * Only figures that have a real local source are returned. Consultation
     * counts, payments and period indicators have no tenant-wide source
     * (lsxmedical history is queried per CPF), so they are null instead of
     * the demo's illustrative numbers.
     *
     * @return array<string, mixed>
     */
    private function dashboard(Tenant $tenant): array
    {
        $birthdates = $tenant->users()
            ->where('role', UserRole::Patient)
            ->pluck('birthdate');

        $ages = array_map(fn (array $band) => [
            'faixa' => $band['faixa'],
            'v' => $birthdates->filter(function ($birthdate) use ($band) {
                if (! $birthdate) {
                    return false;
                }
                $age = Carbon::parse($birthdate)->age;

                return $age >= $band['min'] && $age <= $band['max'];
            })->count(),
        ], self::AGE_BANDS);

        return [
            'patients' => $birthdates->count(),
            'consultations' => null,
            'scheduled' => null,
            'unpaid' => null,
            'indicators' => null,
            'days' => null,
            'hours' => null,
            'ages' => $ages,
        ];
    }

    /**
     * @param  array{nome: string, cor: string, saudacao: string}  $input
     * @return array<string, mixed>
     */
    private function updateBranding(Tenant $tenant, array $input): array
    {
        $tenant->update([
            'name' => $input['nome'],
            'brand_color' => $input['cor'],
            'greeting' => $input['saudacao'],
        ]);

        return [
            'nome' => $tenant->name,
            'cor' => $tenant->brand_color,
            'saudacao' => $tenant->greeting,
        ];
    }

    private function tenantOf(User $manager): Tenant
    {
        $manager->loadMissing('tenant');
        abort_unless($manager->isManager() && $manager->tenant, 403, 'Gestor sem vínculo com um cliente.');

        return $manager->tenant;
    }
}
