<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthcareDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['healthcare.demo_enabled' => true]);
    }

    private function scenario(string $scenario = 'queimados', string $profile = 'patient', string $network = 'normal'): void
    {
        $this->post('/demonstracao/cenario', compact('scenario', 'profile', 'network'))->assertRedirect();
    }

    public function test_demo_requires_explicit_enablement_and_is_never_available_in_production(): void
    {
        config(['healthcare.demo_enabled' => false]);
        $this->get('/demonstracao')->assertNotFound();
        config(['healthcare.demo_enabled' => true]);
        $this->app->instance('env', 'production');
        $this->get('/demonstracao')->assertNotFound();
        $this->withSession(['_token' => 'demo-test-token'])
            ->postJson('/demonstracao/cenario', ['_token' => 'demo-test-token'])->assertNotFound();
        $this->getJson('/demonstracao/dados/patients')->assertNotFound();
    }

    public function test_real_routes_require_authentication_and_cannot_use_demo_context(): void
    {
        $this->scenario();
        $this->get('/inicio')->assertRedirect('/login');
        $this->assertGuest();
        // /gestor/pacientes is a real, manager-only route: an authenticated
        // patient gets 403, not access via the demo's session context.
        $this->actingAs(User::factory()->create())->get('/gestor/pacientes')->assertForbidden();
    }

    public function test_context_and_profile_are_checked_on_pages_and_data(): void
    {
        $this->getJson('/demonstracao/dados/patients')->assertForbidden();
        $this->scenario();
        $this->get('/demonstracao/gestor/painel')->assertForbidden();
        $this->getJson('/demonstracao/dados/patients')->assertForbidden();
        $this->getJson('/demonstracao/dados/plans')->assertForbidden();
        $this->postJson('/demonstracao/dados/branding', ['nome' => 'Outro', 'cor' => '#123456', 'saudacao' => 'Olá'])->assertForbidden();
        $this->postJson('/demonstracao/dados/payment', ['code' => 'CN-4903', 'paid' => true])->assertForbidden();
    }

    public function test_patient_and_manager_data_are_scoped_to_the_context(): void
    {
        $this->scenario();
        $this->getJson('/demonstracao/dados/consultations')->assertOk()->assertJsonCount(1)->assertJsonPath('0.pacienteId', 101);
        $this->getJson('/demonstracao/dados/account?tenant_id=cetid')->assertJsonPath('cliente', 'queimados');
        $this->scenario('cetid');
        $this->getJson('/demonstracao/dados/prescription/RC-1')->assertNotFound();
        $this->getJson('/demonstracao/dados/prescriptions')->assertExactJson([]);
        $this->scenario('queimados', 'manager');
        $this->getJson('/demonstracao/dados/patients')->assertJsonCount(4);
        $this->getJson('/demonstracao/dados/patient/107')->assertNotFound();
        $this->postJson('/demonstracao/dados/patient', ['id' => 107, 'nome' => 'Outro'])->assertNotFound();
        $this->postJson('/demonstracao/dados/payment', ['code' => 'CN-4950', 'paid' => false])->assertNotFound();
        $this->postJson('/demonstracao/dados/plan', ['id' => 5, 'module' => 'farmacia', 'enabled' => false])->assertUnprocessable();
    }

    public function test_plan_modules_are_enforced_for_nested_records_and_mutations(): void
    {
        $this->scenario('queimados', 'manager');
        $this->postJson('/demonstracao/dados/plan', ['id' => 1, 'module' => 'farmacia', 'enabled' => false])->assertOk();
        $this->scenario();
        $this->get('/demonstracao/receita/RC-1')->assertInertia(fn (Assert $page) => $page->component('Healthcare/Unavailable'));
        $this->getJson('/demonstracao/dados/prescription/RC-1')->assertForbidden();
        $this->postJson('/demonstracao/dados/photo')->assertForbidden();
        $this->postJson('/demonstracao/dados/confirm-item', ['id' => 'RC-1', 'index' => 0, 'name' => 'Exemplo'])->assertForbidden();
    }

    public function test_scheduling_validates_sequence_regulation_and_duplicate_submission(): void
    {
        $this->scenario('queimados', 'manager');
        $this->postJson('/demonstracao/dados/plan', ['id' => 1, 'module' => 'agendamento', 'enabled' => true])->assertOk();
        $this->scenario();
        $this->getJson('/demonstracao/dados/specialties')->assertForbidden();
        $this->scenario('cetid');
        $this->postJson('/demonstracao/dados/days', ['specialty_id' => 999])->assertUnprocessable();
        $day = $this->postJson('/demonstracao/dados/days', ['specialty_id' => 6])->assertOk()->json('0');
        $this->postJson('/demonstracao/dados/times', ['specialty_id' => 6, 'date' => '2000-01-01'])->assertUnprocessable();
        $input = ['specialty_id' => 6, 'date' => $day, 'time' => '09:30', 'doctor_id' => 0, 'request_id' => (string) Str::uuid()];
        $this->postJson('/demonstracao/dados/schedule', [...$input, 'doctor_id' => 999])->assertUnprocessable();
        $first = $this->postJson('/demonstracao/dados/schedule', $input)->assertOk()->assertJsonPath('pago', false)->json();
        $this->postJson('/demonstracao/dados/schedule', $input)->assertOk()->assertJsonPath('codigo', $first['codigo']);
        $this->getJson('/demonstracao/dados/consultations')->assertJsonCount(2);
    }

    public function test_confirming_prescription_text_does_not_grant_coverage(): void
    {
        $this->scenario();
        $this->postJson('/demonstracao/dados/confirm-item', ['id' => 'RC-2', 'index' => 1, 'name' => 'Texto conferido'])
            ->assertOk()->assertJsonPath('itens.1.confirmed', true)->assertJsonPath('itens.1.cobertura', 'confirmar')->assertJsonPath('itens.1.confianca', 0.61);
    }

    public function test_max_prioritizes_emergency_medication_and_mental_health_privacy(): void
    {
        $this->scenario();
        $response = $this->postJson('/demonstracao/dados/max', ['message' => 'Minha receita, mas estou com dor no peito', 'page' => '/farmacia']);
        $response->assertOk()->assertJsonPath('trace.rule', 'emergency')->assertJsonPath('actions.0.path', 'tel:192');
        $this->postJson('/demonstracao/dados/max', ['message' => 'Quero trocar um medicamento', 'page' => '/farmacia'])->assertJsonPath('trace.rule', 'medication');
        $this->scenario('metalurgica', 'manager');
        $response = $this->postJson('/demonstracao/dados/max', ['message' => 'Qual paciente está com problema de saúde mental?', 'page' => '/gestor/pacientes']);
        $response->assertOk()->assertJsonPath('trace.rule', 'privacy')->assertJsonPath('actions', []);
        $this->assertStringContainsString('nunca dados individuais', $response->json('text'));
        $this->assertStringNotContainsString('diagn', $response->json('text'));
        $this->getJson('/demonstracao/dados/patients')->assertDontSee('NR-1');
    }

    public function test_demo_error_and_empty_modes_are_explicit(): void
    {
        $this->scenario('cetid', 'patient', 'error');
        $this->getJson('/demonstracao/dados/consultations')->assertStatus(503);
        $this->scenario('cetid', 'patient', 'empty');
        $this->getJson('/demonstracao/dados/consultations')->assertExactJson([]);
        $this->postJson('/demonstracao/dados/days', ['specialty_id' => 6])->assertExactJson([]);
    }

    public function test_external_identity_fields_and_invalid_branding_are_rejected(): void
    {
        $this->scenario();
        $this->postJson('/demonstracao/dados/emergency', ['tenant_id' => 'cetid'])->assertUnprocessable();
        $this->scenario('queimados', 'manager');
        $this->postJson('/demonstracao/dados/branding', ['nome' => 'Teste', 'cor' => 'url(javascript:bad)', 'saudacao' => 'Olá'])->assertUnprocessable();
        $this->postJson('/demonstracao/dados/plan', ['id' => 1, 'module' => 'nr1', 'enabled' => true])->assertUnprocessable();
    }

    public function test_patient_search_is_paginated_and_scoped_on_the_server(): void
    {
        $this->scenario('queimados', 'manager');
        $this->postJson('/demonstracao/dados/patients-search', ['page' => 2, 'per_page' => 2])
            ->assertOk()->assertJsonPath('count', 4)->assertJsonCount(2, 'results')->assertJsonPath('results.0.id', 103);
        $this->postJson('/demonstracao/dados/patients-search', ['page' => 1, 'per_page' => 10, 'search' => 'antonio'])
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('results.0.id', 101);
        $this->postJson('/demonstracao/dados/patients-search', ['page' => 1, 'per_page' => 51])->assertUnprocessable();
        $this->scenario();
        $this->postJson('/demonstracao/dados/patients-search', ['page' => 1, 'per_page' => 10])->assertForbidden();
        $this->postJson('/demonstracao/dados/consultations-search', ['page' => 1, 'per_page' => 10])
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('results.0.pacienteId', 101);
    }

    public function test_validated_form_values_update_records_without_weakening_tenant_checks(): void
    {
        $this->post('/demonstracao/cenario', ['scenario' => 'queimados', 'profile' => 'manager', 'network' => 'normal', 'plan_id' => '1'])->assertRedirect();
        $this->postJson('/demonstracao/dados/patient', ['id' => '101', 'nome' => 'Nome fictício atualizado', 'planoId' => '1'])
            ->assertOk()->assertJsonPath('nome', 'Nome fictício atualizado')->assertJsonPath('planoId', 1);
        $this->postJson('/demonstracao/dados/plan', ['id' => '1', 'module' => 'farmacia', 'enabled' => '0'])
            ->assertOk()->assertJsonPath('modulos.farmacia', false);
        $this->post('/demonstracao/cenario', ['scenario' => 'cetid', 'profile' => 'patient', 'network' => 'normal', 'plan_id' => '1'])->assertUnprocessable();
        $this->getJson('/demonstracao/dados/patients')->assertJsonCount(4);
    }

    public function test_browsing_does_not_consume_the_scenario_switch_rate_limit(): void
    {
        $this->scenario();
        for ($attempt = 0; $attempt < 31; $attempt++) {
            $this->getJson('/demonstracao/dados/consultations')->assertOk();
        }
        $this->scenario('cetid');
        for ($attempt = 0; $attempt < 28; $attempt++) {
            $this->scenario('cetid');
        }
        $this->post('/demonstracao/cenario', ['scenario' => 'cetid', 'profile' => 'patient', 'network' => 'normal'])->assertStatus(429);
    }
}
