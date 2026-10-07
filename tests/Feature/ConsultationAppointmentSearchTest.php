<?php

namespace Tests\Feature;

use App\Enums\AppointmentSyncStatus;
use App\Models\ConsultationAppointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultationAppointmentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_lists_only_confirmed_appointments_from_own_tenant_and_user(): void
    {
        $tenant = Tenant::factory()->create();
        $patient = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherPatient = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherTenant = Tenant::factory()->create();

        $older = $this->appointment($patient, $tenant, [
            'consultation_code' => 'LOCAL-OLDER',
            'scheduled_for' => now()->addDay(),
        ]);
        $newer = $this->appointment($patient, $tenant, [
            'consultation_code' => 'LOCAL-NEWER',
            'scheduled_for' => now()->addDays(2),
        ]);
        $this->appointment($otherPatient, $tenant, ['consultation_code' => 'OTHER-PATIENT']);
        $this->appointment($patient, $otherTenant, ['consultation_code' => 'OTHER-TENANT']);
        $this->appointment($patient, $tenant, [
            'consultation_code' => 'REJECTED',
            'sync_status' => AppointmentSyncStatus::Rejected,
            'provider_status' => null,
        ]);

        Http::fake();

        $response = $this->actingAs($patient)->postJson('/triagem/appointments-search', [])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('results.0.id', $newer->uuid)
            ->assertJsonPath('results.0.codigo', 'LOCAL-NEWER')
            ->assertJsonPath('results.1.id', $older->uuid)
            ->assertJsonMissing(['codigo' => 'OTHER-PATIENT'])
            ->assertJsonMissing(['codigo' => 'OTHER-TENANT'])
            ->assertJsonMissing(['codigo' => 'REJECTED']);

        $this->assertStringNotContainsString('tenant_id', $response->getContent());
        $this->assertStringNotContainsString('request_id', $response->getContent());
        Http::assertNothingSent();
    }

    public function test_patient_searches_encrypted_fields_filters_status_and_paginates(): void
    {
        $tenant = Tenant::factory()->create();
        $patient = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->appointment($patient, $tenant, [
            'consultation_code' => 'CARDIO-1',
            'specialty_name' => 'Cardiologia',
            'doctor_name' => 'Dra. Ângela',
            'provider_status' => 'FINISHED',
            'scheduled_for' => now()->addDays(3),
        ]);
        $second = $this->appointment($patient, $tenant, [
            'consultation_code' => 'CARDIO-2',
            'specialty_name' => 'Cardiologia',
            'doctor_name' => 'Dr. Bruno',
            'scheduled_for' => now()->addDays(2),
        ]);
        $this->appointment($patient, $tenant, [
            'consultation_code' => 'DERMATO-1',
            'specialty_name' => 'Dermatologia',
            'doctor_name' => 'Dra. Carla',
            'scheduled_for' => now()->addDay(),
        ]);

        $this->actingAs($patient)->postJson('/triagem/appointments-search', [
            'search' => 'cardio',
            'page' => 2,
            'per_page' => 1,
        ])->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('page', 2)
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('results.0.id', $second->uuid);

        $this->actingAs($patient)->postJson('/triagem/appointments-search', [
            'search' => 'angela',
        ])->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.codigo', 'CARDIO-1');

        $this->actingAs($patient)->postJson('/triagem/appointments-search', [
            'status' => 'FINISHED',
        ])->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.codigo', 'CARDIO-1');
    }

    public function test_local_search_rejects_untrusted_context_and_unauthorized_profiles(): void
    {
        $tenant = Tenant::factory()->create();
        $patient = User::factory()->create(['tenant_id' => $tenant->id]);
        $otherTenant = Tenant::factory()->create();

        $this->actingAs($patient)->postJson('/triagem/appointments-search', [
            'tenant_id' => $otherTenant->id,
            'user_id' => User::factory()->create()->id,
            'cpf' => '52998224725',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id', 'user_id', 'cpf']);

        $manager = User::factory()->manager()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($manager)->postJson('/triagem/appointments-search')->assertForbidden();

        $orphan = User::factory()->create(['tenant_id' => null]);
        $this->actingAs($orphan)->postJson('/triagem/appointments-search')->assertForbidden();
    }

    /** @param array<string, mixed> $overrides */
    private function appointment(User $patient, Tenant $tenant, array $overrides = []): ConsultationAppointment
    {
        $appointment = new ConsultationAppointment(array_merge([
            'request_id' => (string) Str::uuid(),
            'provider' => 'lsxmedical',
            'consultation_code' => 'LOCAL-'.Str::upper(Str::random(8)),
            'specialty_id' => 6,
            'specialty_name' => 'Clínica médica',
            'doctor_id' => 42,
            'doctor_name' => 'Dra. Ana',
            'is_real_doctor' => true,
            'scheduled_for' => now()->addDay(),
            'provider_status' => 'SCHEDULED',
            'sync_status' => AppointmentSyncStatus::Confirmed,
            'is_paid' => false,
            'price' => 120,
        ], $overrides));
        $appointment->tenant_id = $tenant->id;
        $appointment->user_id = $patient->id;
        $appointment->save();

        return $appointment;
    }
}
