<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthcareManagerAreaTest extends TestCase
{
    use RefreshDatabase;

    private const SCREENS = [
        '/gestor/planos' => 'Healthcare/Manager/Plans',
        '/gestor/identidade' => 'Healthcare/Manager/Branding',
        '/gestor/integracao' => 'Healthcare/Manager/Integrations',
    ];

    private function makeManager(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        return User::factory()->manager()->create(['tenant_id' => $tenant->id]);
    }

    public function test_manager_reaches_every_screen_with_the_real_context(): void
    {
        $manager = $this->makeManager();
        foreach (self::SCREENS as $path => $component) {
            $this->actingAs($manager)->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component)
                    ->where('healthcare.demo', false)
                    ->where('healthcare.profile', 'manager')
                    ->where('healthcare.apiBase', '/gestor/dados')
                    ->where('healthcare.manager.nome', $manager->name));
        }
    }

    public function test_guests_patients_and_managers_without_tenant_are_blocked(): void
    {
        $patient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $orphan = User::factory()->manager()->create(['tenant_id' => null]);

        foreach (array_keys(self::SCREENS) as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $this->getJson('/gestor/dados/plans')->assertUnauthorized();

        foreach (array_keys(self::SCREENS) as $path) {
            $this->actingAs($patient)->get($path)->assertForbidden();
            $this->actingAs($orphan)->get($path)->assertForbidden();
        }

        $this->actingAs($patient)->getJson('/gestor/dados/plans')->assertForbidden();
        $this->actingAs($orphan)->getJson('/gestor/dados/plans')->assertForbidden();
        $this->actingAs($orphan)->getJson('/gestor/dados/dashboard')->assertForbidden();
    }

    public function test_dashboard_counts_only_the_managers_tenant_patients_and_omits_figures_without_a_source(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->makeManager($tenant);
        User::factory()->create(['tenant_id' => $tenant->id, 'birthdate' => now()->subYears(30)->toDateString()]);
        User::factory()->create(['tenant_id' => $tenant->id, 'birthdate' => now()->subYears(70)->toDateString()]);
        User::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'birthdate' => now()->subYears(30)->toDateString()]);
        User::factory()->manager()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($manager)->getJson('/gestor/dados/dashboard')
            ->assertOk()
            ->assertJsonPath('patients', 2)
            ->assertJsonPath('consultations', null)
            ->assertJsonPath('unpaid', null)
            ->assertJsonPath('indicators', null)
            ->assertJsonPath('ages.2', ['faixa' => '18 a 39', 'v' => 1])
            ->assertJsonPath('ages.4', ['faixa' => '60 ou mais', 'v' => 1]);
    }

    public function test_plans_start_with_a_default_that_preserves_the_previous_patient_behaviour(): void
    {
        $manager = $this->makeManager();

        $this->actingAs($manager)->getJson('/gestor/dados/plans')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nome', 'Saúde Maxi')
            ->assertJsonPath('0.modulos', [
                'orientacao' => true, 'atendimento' => true, 'agendamento' => true, 'farmacia' => true, 'nr1' => true,
            ]);

        $this->actingAs($manager)->getJson('/gestor/dados/plans')->assertJsonCount(1);
    }

    public function test_toggling_a_module_blocks_it_for_the_tenants_patients_only(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = $this->makeManager($tenant);
        $patient = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherPatient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

        $planId = $this->actingAs($manager)->getJson('/gestor/dados/plans')->json('0.id');

        $this->actingAs($manager)->postJson('/gestor/dados/plan', ['id' => $planId, 'module' => 'farmacia', 'enabled' => false])
            ->assertOk()->assertJsonPath('modulos.farmacia', false);

        $this->actingAs($patient)->get('/farmacia')
            ->assertInertia(fn (Assert $page) => $page->component('Healthcare/Unavailable'));
        $this->actingAs($patient)->getJson('/triagem/prescriptions')->assertForbidden();
        $this->actingAs($otherPatient)->get('/farmacia')
            ->assertInertia(fn (Assert $page) => $page->component('Healthcare/Patient/Pharmacy'));

        $this->actingAs($manager)->postJson('/gestor/dados/plan', ['id' => $planId, 'module' => 'orientacao', 'enabled' => false])->assertOk();
        $this->actingAs($patient)->get('/orientacao')
            ->assertInertia(fn (Assert $page) => $page->component('Healthcare/Unavailable'));
        $this->actingAs($patient)->postJson('/triagem/sessoes', ['consent' => true])->assertForbidden();
    }

    public function test_plan_toggle_rejects_other_tenants_plans_nr1_and_invalid_input(): void
    {
        $manager = $this->makeManager();
        $foreign = Tenant::factory()->create()->plans()->create([
            'name' => 'Outro', 'modules' => ['farmacia' => true], 'is_default' => true,
        ]);
        $ownId = $this->actingAs($manager)->getJson('/gestor/dados/plans')->json('0.id');

        $this->actingAs($manager)->postJson('/gestor/dados/plan', ['id' => $foreign->id, 'module' => 'farmacia', 'enabled' => false])
            ->assertNotFound();
        $this->assertTrue(Plan::find($foreign->id)->modules['farmacia']);

        $this->actingAs($manager)->postJson('/gestor/dados/plan', ['id' => $ownId, 'module' => 'nr1', 'enabled' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('module');
        $this->actingAs($manager)->postJson('/gestor/dados/plan', ['id' => $ownId, 'module' => 'farmacia'])
            ->assertUnprocessable()->assertJsonValidationErrors('enabled');
    }

    public function test_branding_updates_only_the_managers_tenant_and_reaches_its_patients(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Antigo']);
        $other = Tenant::factory()->create(['name' => 'Outro cliente']);
        $manager = $this->makeManager($tenant);
        $patient = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($manager)->postJson('/gestor/dados/branding', [
            'nome' => 'Prefeitura Nova', 'cor' => '#123ABC', 'saudacao' => 'Olá!', 'tenant_id' => $other->id,
        ])->assertOk()->assertJsonPath('nome', 'Prefeitura Nova');

        $this->assertSame('Prefeitura Nova', $tenant->refresh()->name);
        $this->assertSame('#123ABC', $tenant->brand_color);
        $this->assertSame('Outro cliente', $other->refresh()->name);

        $this->actingAs($patient)->get('/conta')
            ->assertInertia(fn (Assert $page) => $page
                ->where('healthcare.tenant.nome', 'Prefeitura Nova')
                ->where('healthcare.tenant.cor', '#123ABC')
                ->where('healthcare.tenant.saudacao', 'Olá!'));
    }

    public function test_branding_validates_input(): void
    {
        $manager = $this->makeManager();

        $this->actingAs($manager)->postJson('/gestor/dados/branding', ['nome' => '', 'cor' => 'red', 'saudacao' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['nome', 'cor', 'saudacao']);
    }

    public function test_unknown_resources_and_operations_are_not_found(): void
    {
        $manager = $this->makeManager();

        $this->actingAs($manager)->getJson('/gestor/dados/qualquer')->assertNotFound();
        $this->actingAs($manager)->postJson('/gestor/dados/qualquer')->assertNotFound();
    }

    public function test_patient_context_without_stored_plan_keeps_every_module_enabled(): void
    {
        $patient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

        $this->actingAs($patient)->get('/conta')
            ->assertInertia(fn (Assert $page) => $page
                ->where('healthcare.plan.nome', 'Saúde Maxi')
                ->where('healthcare.modules.farmacia', true)
                ->where('healthcare.modules.nr1', true)
                ->where('healthcare.tenant.saudacao', 'Cuidado e orientação para você.'));
    }
}
