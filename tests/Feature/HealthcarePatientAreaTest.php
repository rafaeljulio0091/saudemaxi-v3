<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    private function makePatient(array $attributes = []): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->create(array_merge(['tenant_id' => $tenant->id], $attributes));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (array_keys(self::READY_ROUTES) as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_managers_cannot_access_the_patient_area(): void
    {
        $manager = User::factory()->manager()->create();
        foreach (array_keys(self::READY_ROUTES) as $path) {
            $this->actingAs($manager)->get($path)->assertForbidden();
        }
    }

    public function test_patient_without_a_tenant_is_denied_instead_of_crashing(): void
    {
        $patient = User::factory()->create(['tenant_id' => null]);
        foreach (array_keys(self::READY_ROUTES) as $path) {
            $this->actingAs($patient)->get($path)->assertForbidden();
        }
    }

    public function test_authenticated_patient_can_reach_every_ready_screen(): void
    {
        $patient = $this->makePatient();
        foreach (self::READY_ROUTES as $path => $component) {
            $this->actingAs($patient)->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component)
                    ->where('healthcare.basePath', '')
                    ->where('healthcare.apiBase', '/triagem')
                    ->where('healthcare.demo', false));
        }
    }

    public function test_dados_endpoints_require_authentication_and_the_patient_role(): void
    {
        $this->getJson('/triagem/prescriptions')->assertUnauthorized();
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager)->getJson('/triagem/prescriptions')->assertForbidden();
    }

    public function test_prescriptions_are_empty_until_a_photo_is_uploaded_and_scoped_per_patient(): void
    {
        Storage::fake('local');
        $patient = $this->makePatient();
        $this->actingAs($patient)->getJson('/triagem/prescriptions')->assertOk()->assertExactJson([]);

        $file = UploadedFile::fake()->image('receita.jpg')->size(500);
        $this->actingAs($patient)->postJson('/triagem/photo', ['file' => $file])
            ->assertOk()->assertJsonPath('status', 'AGUARDANDO_ANALISE')->assertJsonPath('itens', []);

        $this->actingAs($patient)->getJson('/triagem/prescriptions')->assertOk()->assertJsonCount(1);

        $other = $this->makePatient();
        $this->actingAs($other)->getJson('/triagem/prescriptions')->assertOk()->assertExactJson([]);
    }

    public function test_photo_upload_rejects_invalid_files(): void
    {
        Storage::fake('local');
        $patient = $this->makePatient();
        $file = UploadedFile::fake()->create('receita.pdf', 100, 'application/pdf');
        $this->actingAs($patient)->postJson('/triagem/photo', ['file' => $file])->assertUnprocessable();
    }

    public function test_consultations_search_calls_the_real_provider_and_maps_its_fields(): void
    {
        $patient = $this->makePatient(['cpf' => '12345678901']);

        Http::fake([
            '*/api/clinic/consultation-history/*' => Http::response([
                'count' => 1,
                'results' => [[
                    'code' => 'CN-9001', 'status' => 'FINISHED', 'specialty' => 'Clínica médica',
                    'doctor_name' => 'Dra. Ana', 'start_date' => '2026-01-10T09:00:00-03:00',
                ]],
            ], 200),
        ]);

        $this->actingAs($patient)->postJson('/triagem/consultations-search', [])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.codigo', 'CN-9001')
            ->assertJsonPath('results.0.especialidade', 'Clínica médica')
            ->assertJsonPath('results.0.medico', 'Dra. Ana');

        Http::assertSent(fn ($request) => $request['cpf'] === '12345678901');
    }

    public function test_consultations_search_is_empty_without_a_stored_cpf_and_never_calls_the_provider(): void
    {
        $patient = $this->makePatient(['cpf' => null]);

        Http::fake();

        $this->actingAs($patient)->postJson('/triagem/consultations-search', [])
            ->assertOk()->assertJsonPath('count', 0)->assertJsonPath('results', []);

        Http::assertNothingSent();
    }

    public function test_consultations_search_reports_provider_failures_honestly(): void
    {
        $patient = $this->makePatient(['cpf' => '12345678901']);

        Http::fake(['*/api/clinic/consultation-history/*' => Http::response(null, 500)]);

        $this->actingAs($patient)->postJson('/triagem/consultations-search', [])->assertStatus(503);
    }

    public function test_actions_without_a_telemedicine_client_fail_honestly_instead_of_faking_success(): void
    {
        $patient = $this->makePatient();
        foreach (['emergency', 'max'] as $operation) {
            $this->actingAs($patient)->postJson("/triagem/{$operation}", [])->assertStatus(503);
        }
        $this->actingAs($patient)->getJson('/triagem/specialties')->assertStatus(503);
        foreach (['days', 'times', 'doctors', 'schedule'] as $operation) {
            $this->actingAs($patient)->postJson("/triagem/{$operation}", [])->assertStatus(503);
        }
    }

    public function test_triage_session_routes_are_not_shadowed_by_the_new_generic_data_routes(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($patient)->postJson('/triagem/sessoes', ['consent' => true])->assertStatus(201);
    }

    public function test_unrelated_real_routes_are_unaffected(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($patient)->get('/inicio')->assertStatus(503);
        $this->actingAs($patient)->get('/gestor/pacientes')->assertForbidden();
    }
}
