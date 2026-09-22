<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HealthcareTenantPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'healthcare.tenant_base_domain' => 'saudemaxi.test',
            'telemedicine.base_url' => 'https://provider.test/api/clinic',
            'telemedicine.token' => 'test-token',
        ]);
    }

    private function tenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Prefeitura de Queimados',
            'subdomain' => 'queimados',
            'type' => 'municipio',
            'regulacao' => true,
            'modules' => ['orientacao' => true, 'atendimento' => true, 'farmacia' => true],
        ], $overrides));
    }

    private function patient(Tenant $tenant, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'role' => 'patient',
            'cpf' => '11111111111',
        ], $overrides));
    }

    private function url(string $subdomain, string $path): string
    {
        return 'http://'.$subdomain.'.'.config('healthcare.tenant_base_domain').$path;
    }

    public function test_unknown_subdomain_is_not_found(): void
    {
        $this->get($this->url('nao-existe', '/inicio'))->assertNotFound();
    }

    public function test_real_page_requires_authentication(): void
    {
        $this->tenant();
        $this->get($this->url('queimados', '/inicio'))->assertRedirect($this->url('queimados', '/login'));
    }

    public function test_user_from_another_tenant_is_forbidden(): void
    {
        $tenant = $this->tenant();
        $other = Tenant::create([
            'name' => 'Outro Município', 'subdomain' => 'outro', 'type' => 'municipio',
            'regulacao' => false, 'modules' => ['orientacao' => true],
        ]);
        $user = $this->patient($other);

        $this->actingAs($user)->get($this->url('queimados', '/inicio'))->assertForbidden();
    }

    public function test_home_renders_with_real_tenant_context(): void
    {
        $tenant = $this->tenant();
        $user = $this->patient($tenant, ['name' => 'Maria Teste']);

        $this->actingAs($user)->get($this->url('queimados', '/inicio'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Healthcare/Patient/Home')
                ->where('healthcare.demo', false)
                ->where('healthcare.profile', 'patient')
                ->where('healthcare.tenant.nome', 'Prefeitura de Queimados')
                ->where('healthcare.tenant.regulacao', true)
                ->where('healthcare.patient.nome', 'Maria Teste')
                ->where('healthcare.apiBase', '/api/healthcare'));
    }

    public function test_manager_is_blocked_from_patient_pages(): void
    {
        $tenant = $this->tenant();
        $manager = $this->patient($tenant, ['role' => 'manager']);

        $this->actingAs($manager)->get($this->url('queimados', '/inicio'))->assertForbidden();
    }

    public function test_module_gate_blocks_disabled_module(): void
    {
        $tenant = $this->tenant(['modules' => ['orientacao' => true]]);
        $user = $this->patient($tenant);

        $this->actingAs($user)->get($this->url('queimados', '/atendimento'))->assertForbidden();
    }

    public function test_not_yet_implemented_pages_answer_honestly(): void
    {
        $tenant = $this->tenant();
        $user = $this->patient($tenant);

        $this->actingAs($user)->get($this->url('queimados', '/agendamento'))
            ->assertInertia(fn (Assert $page) => $page->component('Healthcare/NotReady'));
    }

    public function test_emergency_consultation_returns_magic_link_from_provider(): void
    {
        Http::fake([
            'provider.test/*' => Http::response([
                'magic_link' => 'https://provider.test/patient-login/tk_abc/',
                'consultation_code' => 'CN-5123',
                'message' => 'Consulta de pronto atendimento criada',
            ], 201),
        ]);
        $tenant = $this->tenant();
        $user = $this->patient($tenant);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/emergency'))
            ->assertOk()
            ->assertJsonPath('magic_link', 'https://provider.test/patient-login/tk_abc/')
            ->assertJsonPath('consultation_code', 'CN-5123');

        Http::assertSent(fn ($request) => $request->url() === 'https://provider.test/api/clinic/create-emergency-consultation/'
            && $request['cpf'] === '11111111111'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_emergency_requires_cpf_on_record(): void
    {
        $tenant = $this->tenant();
        $user = $this->patient($tenant, ['cpf' => null]);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/emergency'))
            ->assertStatus(422);
    }

    public function test_provider_failure_maps_to_a_safe_message(): void
    {
        Http::fake(['provider.test/*' => Http::response(null, 500)]);
        $tenant = $this->tenant();
        $user = $this->patient($tenant);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/emergency'))
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_provider_unauthorized_does_not_look_like_a_session_expiry(): void
    {
        // A 401 from the provider means the configured TELEMEDICINE_API_TOKEN
        // is wrong, not that the patient's own SaudeMaxi session expired;
        // reusing HTTP 401 here would trigger the frontend's "log in again"
        // message for a problem only an administrator can fix.
        Http::fake(['provider.test/*' => Http::response(null, 401)]);
        $tenant = $this->tenant();
        $user = $this->patient($tenant);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/emergency'))
            ->assertStatus(503);
    }

    public function test_consultation_history_is_normalized_and_searchable(): void
    {
        Http::fake([
            'provider.test/*' => Http::response([
                ['consultation_code' => 'CN-1', 'status' => 'FINISHED', 'specialty' => 'Clínica médica', 'is_paid' => true],
                ['consultation_code' => 'CN-2', 'status' => 'SCHEDULED', 'specialty' => 'Endocrinologia', 'is_paid' => false],
            ]),
        ]);
        $tenant = $this->tenant();
        $user = $this->patient($tenant);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/consultations-search'), [
            'search' => 'endocrino', 'page' => 1, 'per_page' => 10,
        ])->assertOk()->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.codigo', 'CN-2')
            ->assertJsonPath('results.0.pago', false);
    }

    public function test_account_update_persists_locally_even_if_provider_sync_fails(): void
    {
        Http::fake(['provider.test/*' => Http::response(null, 503)]);
        $tenant = $this->tenant();
        $user = $this->patient($tenant, ['name' => 'Nome Antigo']);

        $this->actingAs($user)->postJson($this->url('queimados', '/api/healthcare/patient'), [
            'nome' => 'Nome Novo', 'email' => $user->email, 'telefone' => '11999999999',
        ])->assertOk()->assertJsonPath('nome', 'Nome Novo');

        $this->assertSame('Nome Novo', $user->fresh()->name);
    }

    public function test_seed_tenant_command_provisions_tenant_and_patient(): void
    {
        $this->artisan('healthcare:seed-tenant', [
            'subdomain' => 'queimados',
            'name' => 'Prefeitura de Queimados',
            '--modules' => 'orientacao,atendimento',
            '--regulacao' => true,
            '--patient-email' => 'paciente@example.com',
            '--patient-password' => 'password1234',
            '--patient-name' => 'Paciente de Teste',
            '--patient-cpf' => '22222222222',
        ])->assertSuccessful();

        $tenant = Tenant::where('subdomain', 'queimados')->firstOrFail();
        $this->assertTrue($tenant->regulacao);
        $this->assertTrue($tenant->moduleEnabled('orientacao'));

        $patient = User::where('email', 'paciente@example.com')->firstOrFail();
        $this->assertSame($tenant->id, $patient->tenant_id);
        $this->assertSame('patient', $patient->role);
        $this->assertSame('22222222222', $patient->cpf);
    }
}
