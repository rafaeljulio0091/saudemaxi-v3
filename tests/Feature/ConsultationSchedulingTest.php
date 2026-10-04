<?php

namespace Tests\Feature;

use App\Enums\AppointmentSyncStatus;
use App\Models\ConsultationAppointment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsultationSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private const CPF = '52998224725';

    public function test_patient_creates_a_provider_consultation_and_a_tenant_scoped_local_record(): void
    {
        config(['lsxmedical.service_token' => 'test-service-token']);
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();
        $requestId = (string) Str::uuid();

        $this->fakeSuccessfulScheduling($date);

        $response = $this->actingAs($patient)->postJson('/triagem/schedule', [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => $requestId,
        ])->assertCreated()
            ->assertJsonPath('codigo', 'CN-9001')
            ->assertJsonPath('especialidade', 'Cardiologia')
            ->assertJsonPath('medico', 'Dra. Ana')
            ->assertJsonPath('status', 'SCHEDULED')
            ->assertJsonPath('pago', false)
            ->assertJsonPath('request_id', $requestId);

        $this->assertStringNotContainsString(self::CPF, $response->getContent());
        $this->assertStringNotContainsString('patient-link-secret', $response->getContent());

        $appointment = ConsultationAppointment::query()->sole();
        $this->assertSame($patient->tenant_id, $appointment->tenant_id);
        $this->assertSame($patient->id, $appointment->user_id);
        $this->assertSame(AppointmentSyncStatus::Confirmed, $appointment->sync_status);
        $this->assertSame('Cardiologia', $appointment->specialty_name);
        $this->assertSame('Dra. Ana', $appointment->doctor_name);
        $this->assertNotSame(
            'Cardiologia',
            DB::table('consultation_appointments')->value('specialty_name'),
        );

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/clinic/scheduling/create-consultation/')
            && $request->hasHeader('Authorization', 'Bearer test-service-token')
            && $request->data() === [
                'patient_cpf' => self::CPF,
                'specialty_id' => 6,
                'date' => $date,
                'time' => '09:30',
                'doctor_id' => 42,
                'is_real_doctor' => true,
                'is_paid' => false,
            ]);
        Http::assertSentCount(5);
    }

    public function test_creation_is_idempotent_and_does_not_repeat_the_provider_write(): void
    {
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();
        $payload = [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => (string) Str::uuid(),
        ];

        $this->fakeSuccessfulScheduling($date);

        $first = $this->actingAs($patient)->postJson('/triagem/schedule', $payload)
            ->assertCreated()->json();
        $second = $this->actingAs($patient)->postJson('/triagem/schedule', $payload)
            ->assertCreated()->json();

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('consultation_appointments', 1);
        $this->assertCount(1, Http::recorded(
            fn (Request $request): bool => str_ends_with(
                $request->url(),
                '/api/clinic/scheduling/create-consultation/',
            ),
        ));
    }

    public function test_ambiguous_provider_failure_requires_reconciliation_and_blocks_a_blind_retry(): void
    {
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();
        $payload = [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => (string) Str::uuid(),
        ];

        $this->fakeSuccessfulScheduling($date, creationFailure: 'connection');

        $this->actingAs($patient)->postJson('/triagem/schedule', $payload)
            ->assertServiceUnavailable();

        $appointment = ConsultationAppointment::query()->sole();
        $this->assertSame(AppointmentSyncStatus::ReconciliationRequired, $appointment->sync_status);
        $this->assertSame('unavailable', $appointment->last_error_reason);
        $sentBeforeRetry = Http::recorded()->count();

        $this->actingAs($patient)->postJson('/triagem/schedule', $payload)
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'O resultado desta tentativa precisa ser reconciliado antes de um novo envio.',
            );

        $this->assertCount($sentBeforeRetry, Http::recorded());
    }

    public function test_provider_validation_rejection_is_safely_exposed_and_recorded(): void
    {
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();

        $this->fakeSuccessfulScheduling($date, creationFailure: 'rejected');

        $response = $this->actingAs($patient)->postJson('/triagem/schedule', [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => (string) Str::uuid(),
        ])->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'O provedor de telemedicina rejeitou os dados informados.',
            );

        $this->assertStringNotContainsString(self::CPF, $response->getContent());
        $this->assertSame(
            AppointmentSyncStatus::Rejected,
            ConsultationAppointment::query()->sole()->sync_status,
        );
    }

    public function test_invalid_success_response_requires_reconciliation(): void
    {
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();

        $this->fakeSuccessfulScheduling($date, creationFailure: 'invalid_response');

        $this->actingAs($patient)->postJson('/triagem/schedule', [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => (string) Str::uuid(),
        ])->assertServiceUnavailable();

        $appointment = ConsultationAppointment::query()->sole();
        $this->assertSame(AppointmentSyncStatus::ReconciliationRequired, $appointment->sync_status);
        $this->assertSame('invalid_response', $appointment->last_error_reason);
    }

    public function test_local_confirmation_failure_after_provider_success_requires_reconciliation(): void
    {
        $patient = $this->patient();
        $date = now()->addDays(2)->toDateString();
        $this->fakeSuccessfulScheduling($date);

        $basePayload = [
            'specialty_id' => 6,
            'date' => $date,
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
        ];

        $this->actingAs($patient)->postJson('/triagem/schedule', [
            ...$basePayload,
            'request_id' => (string) Str::uuid(),
        ])->assertCreated();

        $secondRequestId = (string) Str::uuid();
        $this->actingAs($patient)->postJson('/triagem/schedule', [
            ...$basePayload,
            'request_id' => $secondRequestId,
        ])->assertServiceUnavailable()
            ->assertJsonPath(
                'message',
                'A consulta foi enviada, mas a confirmação local precisa ser reconciliada.',
            );

        $second = ConsultationAppointment::query()
            ->where('request_id', $secondRequestId)
            ->sole();
        $this->assertSame(AppointmentSyncStatus::ReconciliationRequired, $second->sync_status);
        $this->assertSame('local_persistence', $second->last_error_reason);
    }

    public function test_browser_cannot_supply_protected_patient_payment_or_tenant_context(): void
    {
        $patient = $this->patient();
        $otherTenant = Tenant::factory()->create();

        Http::fake();

        $this->actingAs($patient)->postJson('/triagem/schedule', [
            'specialty_id' => 6,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '09:30',
            'doctor_id' => 42,
            'is_real_doctor' => true,
            'request_id' => (string) Str::uuid(),
            'tenant_id' => $otherTenant->id,
            'patient_cpf' => '11144477735',
            'price' => 0,
            'is_paid' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id', 'patient_cpf', 'price', 'is_paid']);

        Http::assertNothingSent();
        $this->assertDatabaseCount('consultation_appointments', 0);
    }

    public function test_scheduling_is_denied_for_managers_disabled_modules_and_regulated_tenants(): void
    {
        Http::fake();

        $manager = User::factory()->manager()->create([
            'tenant_id' => Tenant::factory()->create()->id,
        ]);
        $this->actingAs($manager)->getJson('/triagem/specialties')->assertForbidden();

        $disabled = $this->patient();
        $disabled->tenant->plans()->create([
            'name' => 'Sem agendamento',
            'modules' => ['agendamento' => false],
            'is_default' => true,
        ]);
        $this->actingAs($disabled)->getJson('/triagem/specialties')->assertForbidden();

        $regulated = $this->patient();
        $regulated->tenant->forceFill(['regulacao' => true])->save();
        $this->actingAs($regulated)->getJson('/triagem/specialties')->assertForbidden();

        Http::assertNothingSent();
    }

    private function patient(): User
    {
        return User::factory()->create([
            'tenant_id' => Tenant::factory()->create(['regulacao' => false])->id,
            'cpf' => self::CPF,
        ]);
    }

    private function fakeSuccessfulScheduling(string $date, ?string $creationFailure = null): void
    {
        Http::fake(function (Request $request) use ($date, $creationFailure) {
            return match (true) {
                str_ends_with($request->url(), '/api/clinic/scheduling/specialties/') => Http::response([
                    ['id' => 6, 'name' => 'Cardiologia', 'price' => 120],
                ]),
                str_ends_with($request->url(), '/api/clinic/scheduling/business-days/') => Http::response([$date]),
                str_ends_with($request->url(), '/api/clinic/scheduling/available-times/') => Http::response(['09:30']),
                str_ends_with($request->url(), '/api/clinic/scheduling/doctors/') => Http::response([
                    [
                        'id' => 42,
                        'name' => 'Dra. Ana',
                        'specialty' => 'Cardiologia',
                        'price' => 120,
                        'is_real' => true,
                    ],
                ]),
                str_ends_with($request->url(), '/api/clinic/scheduling/create-consultation/') && $creationFailure === 'connection' => throw new ConnectionException('Timeout sem payload sensível'),
                str_ends_with($request->url(), '/api/clinic/scheduling/create-consultation/') && $creationFailure === 'rejected' => Http::response([
                    'errors' => ['patient_cpf' => ['CPF rejeitado: '.self::CPF]],
                ], 422),
                str_ends_with($request->url(), '/api/clinic/scheduling/create-consultation/') && $creationFailure === 'invalid_response' => Http::response([
                    'success' => false,
                ], 201),
                str_ends_with($request->url(), '/api/clinic/scheduling/create-consultation/') => Http::response([
                    'success' => true,
                    'consultation_code' => 'CN-9001',
                    'consultation_id' => 9001,
                    'scheduled_for' => $date.'T09:30:00-03:00',
                    'patient_link' => 'https://provider.test/patient-link-secret',
                    'is_paid' => false,
                    'price' => 120,
                ], 201),
                default => Http::response([], 404),
            };
        });
    }
}
