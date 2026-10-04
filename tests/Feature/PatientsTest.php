<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Native\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PatientsTest extends TestCase
{
    use RefreshDatabase;

    private function manager(?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();

        return User::factory()->manager()->create(['tenant_id' => $tenant->id]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/gestor/pacientes')->assertRedirect(route('login'));
    }

    public function test_patient_and_manager_without_tenant_cannot_access_patients(): void
    {
        $patient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $orphanManager = User::factory()->manager()->create(['tenant_id' => null]);

        $this->actingAs($patient)->get('/gestor/pacientes')->assertForbidden();
        $this->actingAs($orphanManager)->get('/gestor/pacientes')->assertForbidden();
    }

    public function test_manager_creates_and_lists_only_native_patients_from_own_tenant(): void
    {
        $manager = $this->manager();
        $otherManager = $this->manager();
        Http::fake();

        $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'João da Silva',
            'cpf' => '529.982.247-25',
            'email' => 'joao@example.com',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
        ])->assertRedirect(route('healthcare.manager.patients'));

        $this->actingAs($otherManager)->post('/gestor/pacientes', [
            'name' => 'Outro tenant',
            'cpf' => '529.982.247-25',
        ])->assertSessionHasNoErrors();

        $this->actingAs($manager)->get('/gestor/pacientes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Healthcare/Manager/Patients')
                ->where('healthcare.profile', 'manager')
                ->where('healthcare.apiBase', '/gestor/dados'));

        $this->actingAs($manager)->postJson('/gestor/dados/patients-search', ['search' => '52998224725'])
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.name', 'João da Silva')
            ->assertJsonPath('results.0.cpf', '***.982.247-**');

        Http::assertNothingSent();
    }

    public function test_local_patient_creation_succeeds_while_lsx_is_unavailable(): void
    {
        $manager = $this->manager();
        Http::fake(['*' => Http::response(null, 500)]);

        $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'Maria Local',
            'cpf' => '52998224725',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('patients', [
            'tenant_id' => $manager->tenant_id,
            'name' => 'Maria Local',
        ]);
        Http::assertNothingSent();
    }

    public function test_patient_creation_validates_cpf_and_required_fields(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post('/gestor/pacientes', [])
            ->assertSessionHasErrors(['name', 'cpf']);

        $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'CPF inválido',
            'cpf' => '123.456.789-00',
        ])->assertSessionHasErrors('cpf');
    }

    public function test_duplicate_cpf_is_blocked_inside_the_same_tenant(): void
    {
        $manager = $this->manager();
        $payload = ['name' => 'Primeiro', 'cpf' => '52998224725'];

        $this->actingAs($manager)->post('/gestor/pacientes', $payload)->assertSessionHasNoErrors();
        $this->actingAs($manager)->post('/gestor/pacientes', [...$payload, 'name' => 'Duplicado'])
            ->assertSessionHasErrors('cpf');

        $this->assertSame(1, Patient::where('tenant_id', $manager->tenant_id)->count());
    }

    public function test_authoritative_fields_cannot_be_mass_assigned(): void
    {
        $manager = $this->manager();
        $foreignTenant = Tenant::factory()->create();

        $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'Tentativa',
            'cpf' => '52998224725',
            'tenant_id' => $foreignTenant->id,
            'user_id' => User::factory()->create(['tenant_id' => $foreignTenant->id])->id,
            'role' => 'manager',
            'is_admin' => true,
        ])->assertSessionHasErrors(['tenant_id', 'user_id', 'role', 'is_admin']);

        $this->assertDatabaseCount('patients', 0);
    }

    public function test_cross_tenant_patient_uuid_returns_not_found(): void
    {
        $owner = $this->manager();
        $attacker = $this->manager();
        $this->actingAs($owner)->post('/gestor/pacientes', [
            'name' => 'Paciente protegido',
            'cpf' => '52998224725',
        ]);
        $patient = Patient::where('tenant_id', $owner->tenant_id)->firstOrFail();

        $this->actingAs($attacker)
            ->getJson("/gestor/dados/patient/{$patient->uuid}")
            ->assertNotFound();
    }

    public function test_json_flow_uses_uuid_and_audits_masked_detail_update_delete_and_restore(): void
    {
        $manager = $this->manager();
        $uuid = $this->actingAs($manager)->postJson('/gestor/dados/create-patient', [
            'name' => 'Paciente JSON',
            'social_name' => 'Nome social',
            'cpf' => '52998224725',
            'email' => 'json@example.com',
        ])->assertCreated()->json('data.uuid');

        $this->actingAs($manager)->get("/gestor/pacientes/{$uuid}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Healthcare/Manager/Patient')
                ->where('recordId', $uuid));

        $this->actingAs($manager)->getJson("/gestor/dados/patient/{$uuid}")
            ->assertOk()
            ->assertJsonPath('data.cpf', '***.982.247-**')
            ->assertJsonPath('data.email', 'j***@example.com');

        $this->actingAs($manager)->postJson('/gestor/dados/patient', [
            'id' => $uuid,
            'name' => 'Paciente atualizado',
            'social_name' => null,
            'tenant_id' => Tenant::factory()->create()->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('tenant_id');

        $this->actingAs($manager)->postJson('/gestor/dados/patient', [
            'id' => $uuid,
            'name' => 'Paciente atualizado',
            'social_name' => null,
        ])->assertOk()->assertJsonPath('data.name', 'Paciente atualizado');

        $this->actingAs($manager)->deleteJson("/gestor/dados/patient/{$uuid}")->assertNoContent();
        $this->assertSoftDeleted('patients', ['uuid' => $uuid]);

        $this->actingAs($manager)->postJson("/gestor/dados/patient/{$uuid}/restore")
            ->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->assertNotSoftDeleted('patients', ['uuid' => $uuid]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.viewed_sensitive_data']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.deleted']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.restored']);
    }

    public function test_sensitive_identifiers_are_encrypted_and_audit_has_no_payload(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'Paciente protegido',
            'cpf' => '52998224725',
            'email' => 'sensivel@example.com',
            'phone' => '11999998888',
        ]);

        $patient = Patient::firstOrFail();
        $raw = DB::table('patients')->where('id', $patient->id)->first();
        $this->assertNotSame('52998224725', $raw->cpf);
        $this->assertNotSame('sensivel@example.com', $raw->email);
        $this->assertSame('52998224725', $patient->cpf);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $manager->tenant_id,
            'action' => 'patient.created',
            'resource_id' => $patient->id,
        ]);
        $this->assertFalse(in_array('payload', (new AuditLog)->getFillable(), true));
    }

    public function test_composite_creation_rolls_back_when_audit_fails(): void
    {
        $manager = $this->manager();
        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('record')->once()->andThrow(new RuntimeException('audit unavailable'));
        $this->app->instance(AuditLogger::class, $audit);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($manager)->post('/gestor/pacientes', [
                'name' => 'Rollback',
                'cpf' => '52998224725',
                'address' => [
                    'street' => 'Rua Teste',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                ],
            ]);
            $this->fail('A falha simulada de auditoria deveria interromper a transação.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit unavailable', $exception->getMessage());
        }

        $this->assertDatabaseCount('patients', 0);
        $this->assertDatabaseCount('addresses', 0);
    }
}
