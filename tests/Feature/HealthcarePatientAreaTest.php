<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthcarePatientAreaTest extends TestCase
{
    use RefreshDatabase;

    private const READY_ROUTES = [
        '/atendimento' => 'Healthcare/Patient/Immediate',
        '/agendamento' => 'Healthcare/Patient/Scheduling',
        '/farmacia' => 'Healthcare/Patient/Pharmacy',
        '/consultas' => 'Healthcare/Shared/Consultations',
        '/nr1' => 'Healthcare/Patient/MentalHealth',
    ];

    private const ROUTE_MODULE = [
        '/atendimento' => 'atendimento',
        '/agendamento' => 'agendamento',
        '/farmacia' => 'farmacia',
        '/consultas' => null,
        '/nr1' => 'nr1',
    ];

    private function makePatient(array $modules = []): Patient
    {
        $tenant = Tenant::create([
            'nome' => 'Prefeitura de Teste',
            'subdominio' => 'teste-'.uniqid().'.saudemaxi.com.br',
            'saudacao' => 'Bem-vindo',
            'cor' => '#1D4E89',
            'regulacao' => false,
        ]);
        $plan = Plan::create([
            'tenant_id' => $tenant->id,
            'nome' => 'Plano de teste',
            'max_dependentes' => 0,
            'modules' => array_merge([
                'orientacao' => true, 'atendimento' => true, 'agendamento' => true,
                'farmacia' => true, 'nr1' => true,
            ], $modules),
        ]);
        $user = User::factory()->create();

        return Patient::create([
            'user_id' => $user->id, 'tenant_id' => $tenant->id, 'plan_id' => $plan->id,
            'nome' => $user->name, 'email' => $user->email, 'titular' => true, 'status' => 'ACTIVE',
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (array_keys(self::READY_ROUTES) as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_authenticated_user_without_a_patient_profile_sees_an_honest_pending_state(): void
    {
        $user = User::factory()->create();
        foreach (array_keys(self::READY_ROUTES) as $path) {
            $this->actingAs($user)->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Healthcare/PatientNotConfigured'));
        }
    }

    public function test_authenticated_patient_can_reach_every_ready_screen(): void
    {
        $patient = $this->makePatient();
        foreach (self::READY_ROUTES as $path => $component) {
            $this->actingAs($patient->user)->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component)
                    ->where('healthcare.tenant.subdominio', $patient->tenant->subdominio)
                    ->where('healthcare.basePath', ''));
        }
    }

    public function test_module_not_contracted_blocks_the_screen_server_side(): void
    {
        $patient = $this->makePatient(['atendimento' => false]);
        $this->actingAs($patient->user)->get('/atendimento')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Healthcare/Unavailable')
                ->where('reason', 'Este serviço não está incluído no seu plano.'));
    }

    public function test_dados_endpoints_require_authentication(): void
    {
        $this->getJson('/dados/prescriptions')->assertUnauthorized();
        $this->postJson('/dados/emergency', [])->assertUnauthorized();
    }

    public function test_dados_endpoints_reject_a_user_without_a_patient_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/dados/prescriptions')->assertForbidden();
    }

    public function test_prescriptions_are_empty_until_a_photo_is_uploaded_and_scoped_per_patient(): void
    {
        Storage::fake('local');
        $patient = $this->makePatient();
        $this->actingAs($patient->user)->getJson('/dados/prescriptions')->assertOk()->assertExactJson([]);

        $file = UploadedFile::fake()->image('receita.jpg')->size(500);
        $response = $this->actingAs($patient->user)->postJson('/dados/photo', ['file' => $file])->assertOk();
        $response->assertJsonPath('status', 'AGUARDANDO_ANALISE')->assertJsonPath('itens', []);

        $this->actingAs($patient->user)->getJson('/dados/prescriptions')->assertOk()->assertJsonCount(1);

        $other = $this->makePatient();
        $this->actingAs($other->user)->getJson('/dados/prescriptions')->assertOk()->assertExactJson([]);
    }

    public function test_photo_upload_rejects_invalid_files(): void
    {
        Storage::fake('local');
        $patient = $this->makePatient();
        $file = UploadedFile::fake()->create('receita.pdf', 100, 'application/pdf');
        $this->actingAs($patient->user)->postJson('/dados/photo', ['file' => $file])->assertUnprocessable();
    }

    public function test_consultations_search_is_paginated_and_scoped_to_the_patient(): void
    {
        $patient = $this->makePatient();
        Consultation::create([
            'patient_id' => $patient->id, 'codigo' => 'CN-0001', 'especialidade' => 'Clínico geral',
            'status' => 'SCHEDULED', 'agendada_para' => now()->addDay(),
        ]);
        $other = $this->makePatient();
        Consultation::create([
            'patient_id' => $other->id, 'codigo' => 'CN-0002', 'especialidade' => 'Pediatria',
            'status' => 'SCHEDULED', 'agendada_para' => now()->addDay(),
        ]);

        $this->actingAs($patient->user)
            ->postJson('/dados/consultations-search', ['page' => 1, 'per_page' => 10])
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('results.0.codigo', 'CN-0001');
    }

    public function test_actions_that_depend_on_the_telemedicine_integration_fail_honestly_instead_of_faking_success(): void
    {
        $patient = $this->makePatient();
        foreach (['emergency', 'max'] as $operation) {
            $this->actingAs($patient->user)->postJson("/dados/{$operation}", [])->assertStatus(503);
        }
        foreach (['specialties'] as $resource) {
            $this->actingAs($patient->user)->getJson("/dados/{$resource}")->assertStatus(503);
        }
        foreach (['days', 'times', 'doctors', 'schedule'] as $operation) {
            $this->actingAs($patient->user)->postJson("/dados/{$operation}", [])->assertStatus(503);
        }
    }

    public function test_demo_and_real_routes_stay_independent(): void
    {
        // A real, authenticated user must never be served the fictional demo
        // context, and the routes carved out for the real implementation
        // must not disturb the ones still pending (regression guard for the
        // stability rules in AGENTS.md).
        $patient = $this->makePatient();
        $this->actingAs($patient->user)->get('/inicio')->assertStatus(503);
        $this->actingAs($patient->user)->get('/orientacao')->assertStatus(503);
        $this->actingAs($patient->user)->get('/gestor/pacientes')->assertStatus(503);
    }
}
