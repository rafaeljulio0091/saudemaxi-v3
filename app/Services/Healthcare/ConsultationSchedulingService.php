<?php

namespace App\Services\Healthcare;

use App\Enums\AppointmentSyncStatus;
use App\Models\ConsultationAppointment;
use App\Models\User;
use App\Rules\Cpf;
use App\Services\Telemedicine\LsxMedicalSchedulingClient;
use App\Services\Telemedicine\TelemedicineApiException;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConsultationSchedulingService
{
    public function __construct(
        private readonly LsxMedicalSchedulingClient $scheduling,
        private readonly TenantPlanService $plans,
    ) {}

    /** @return list<array{id: int, name: string, price: ?float}> */
    public function specialties(User $user): array
    {
        $this->authorize($user);

        return $this->scheduling->specialties();
    }

    /** @return list<string> */
    public function days(User $user, int $specialtyId): array
    {
        $this->authorize($user);
        $this->specialty($specialtyId);

        return $this->scheduling->businessDays($specialtyId);
    }

    /** @return list<string> */
    public function times(User $user, int $specialtyId, string $date): array
    {
        $this->authorize($user);
        $this->specialty($specialtyId);
        $this->availableDay($specialtyId, $date);

        return $this->scheduling->availableTimes($specialtyId, $date);
    }

    /** @return list<array{id: int, name: string, specialty: ?string, price: ?float, is_real: bool}> */
    public function doctors(User $user, int $specialtyId, string $date, string $time): array
    {
        $this->authorize($user);
        $this->specialty($specialtyId);
        $this->availableDay($specialtyId, $date);
        $this->availableTime($specialtyId, $date, $time);

        return $this->scheduling->doctors($specialtyId, $date, $time);
    }

    /**
     * @param  array{specialty_id: int, date: string, time: string, doctor_id: int, is_real_doctor: bool, request_id: string}  $input
     * @return array<string, mixed>
     */
    public function create(User $user, array $input): array
    {
        $this->authorize($user);

        $existing = $this->existing($user, $input['request_id']);
        if ($existing) {
            return $this->existingResult($existing);
        }

        $cpf = $this->patientCpf($user);
        $specialty = $this->specialty($input['specialty_id']);
        $this->availableDay($input['specialty_id'], $input['date']);
        $this->availableTime($input['specialty_id'], $input['date'], $input['time']);
        $doctor = $this->doctor(
            $input['specialty_id'],
            $input['date'],
            $input['time'],
            $input['doctor_id'],
            $input['is_real_doctor'],
        );

        $appointment = new ConsultationAppointment([
            'request_id' => $input['request_id'],
            'provider' => 'lsxmedical',
            'specialty_id' => $specialty['id'],
            'specialty_name' => $specialty['name'],
            'doctor_id' => $doctor['id'],
            'doctor_name' => $doctor['name'],
            'is_real_doctor' => $doctor['is_real'],
            'scheduled_for' => CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $input['date'].' '.$input['time'],
                config('app.timezone'),
            ),
            'sync_status' => AppointmentSyncStatus::Creating,
            'is_paid' => false,
            'price' => $doctor['price'] ?? $specialty['price'],
        ]);
        $appointment->tenant_id = $user->tenant_id;
        $appointment->user_id = $user->id;

        try {
            $appointment->save();
        } catch (UniqueConstraintViolationException) {
            $existing = $this->existing($user, $input['request_id']);

            if ($existing) {
                return $this->existingResult($existing);
            }

            throw ValidationException::withMessages([
                'request_id' => 'Não foi possível reservar esta solicitação.',
            ]);
        }

        $providerPayload = [
            'patient_cpf' => $cpf,
            'specialty_id' => $specialty['id'],
            'date' => $input['date'],
            'time' => $input['time'],
        ];

        if ($doctor['is_real']) {
            $providerPayload['doctor_id'] = $doctor['id'];
        }

        $providerPayload['is_real_doctor'] = $doctor['is_real'];
        $providerPayload['is_paid'] = false;

        try {
            $result = $this->scheduling->createConsultation($providerPayload);
        } catch (TelemedicineApiException $exception) {
            $appointment->sync_status = in_array(
                $exception->reason,
                ['timeout', 'unavailable', 'invalid_response'],
                true,
            )
                ? AppointmentSyncStatus::ReconciliationRequired
                : AppointmentSyncStatus::Rejected;
            $appointment->last_error_reason = $exception->reason;
            $appointment->save();

            throw $exception;
        }

        try {
            $appointment->fill([
                'provider_consultation_id' => $result['consultation_id'],
                'consultation_code' => $result['consultation_code'],
                'scheduled_for' => CarbonImmutable::parse(
                    $result['scheduled_for'],
                    config('app.timezone'),
                ),
                'provider_status' => 'SCHEDULED',
                'sync_status' => AppointmentSyncStatus::Confirmed,
                'is_paid' => $result['is_paid'],
                'price' => $result['price'] ?? $appointment->price,
                'last_error_reason' => null,
            ]);
            $appointment->save();
        } catch (Throwable $exception) {
            try {
                ConsultationAppointment::query()->whereKey($appointment->getKey())->update([
                    'sync_status' => AppointmentSyncStatus::ReconciliationRequired->value,
                    'last_error_reason' => 'local_persistence',
                ]);
            } catch (Throwable) {
                // A repetição continua bloqueada pelo registro inicial em "creating".
            }

            Log::error('lsxmedical.scheduling.local_persistence_error', [
                'appointment_uuid' => $appointment->uuid,
                'exception' => $exception::class,
            ]);

            throw new TelemedicineApiException(
                'A consulta foi enviada, mas a confirmação local precisa ser reconciliada.',
                'local_persistence',
            );
        }

        return $this->present($appointment);
    }

    private function authorize(User $user): void
    {
        $user->loadMissing('tenant');
        abort_unless($user->isPatient() && $user->tenant, 403, 'Paciente sem vínculo com um cliente.');
        abort_unless(
            $this->plans->effectivePlan($user->tenant)['modules']['agendamento'] ?? false,
            403,
            'Este serviço não está incluído no seu plano.',
        );
    }

    /** @return array{id: int, name: string, price: ?float} */
    private function specialty(int $specialtyId): array
    {
        $specialty = collect($this->scheduling->specialties())
            ->first(fn (array $item): bool => $item['id'] === $specialtyId);

        if (! $specialty) {
            throw ValidationException::withMessages([
                'specialty_id' => 'A especialidade selecionada não está disponível.',
            ]);
        }

        return $specialty;
    }

    private function availableDay(int $specialtyId, string $date): void
    {
        if (! in_array($date, $this->scheduling->businessDays($specialtyId), true)) {
            throw ValidationException::withMessages([
                'date' => 'O dia selecionado não está mais disponível.',
            ]);
        }
    }

    private function availableTime(int $specialtyId, string $date, string $time): void
    {
        if (! in_array($time, $this->scheduling->availableTimes($specialtyId, $date), true)) {
            throw ValidationException::withMessages([
                'time' => 'O horário selecionado não está mais disponível.',
            ]);
        }
    }

    /** @return array{id: int, name: string, specialty: ?string, price: ?float, is_real: bool} */
    private function doctor(
        int $specialtyId,
        string $date,
        string $time,
        int $doctorId,
        bool $isReal,
    ): array {
        $doctor = collect($this->scheduling->doctors($specialtyId, $date, $time))
            ->first(fn (array $item): bool => $item['id'] === $doctorId && $item['is_real'] === $isReal);

        if (! $doctor) {
            throw ValidationException::withMessages([
                'doctor_id' => 'O profissional selecionado não está mais disponível.',
            ]);
        }

        return $doctor;
    }

    private function patientCpf(User $user): string
    {
        $user->loadMissing('patientProfile');
        $profile = $user->patientProfile;
        $value = $profile && (int) $profile->tenant_id === (int) $user->tenant_id
            ? $profile->cpf
            : $user->cpf;
        $cpf = preg_replace('/\D/', '', (string) $value);

        $validator = validator(['cpf' => $cpf], ['cpf' => ['required', new Cpf]]);
        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'appointment' => 'Cadastre um CPF válido antes de agendar uma consulta.',
            ]);
        }

        return $cpf;
    }

    private function existing(User $user, string $requestId): ?ConsultationAppointment
    {
        return ConsultationAppointment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('user_id', $user->id)
            ->where('request_id', $requestId)
            ->first();
    }

    /** @return array<string, mixed> */
    private function existingResult(ConsultationAppointment $appointment): array
    {
        if ($appointment->sync_status === AppointmentSyncStatus::Confirmed) {
            return $this->present($appointment);
        }

        abort(409, match ($appointment->sync_status) {
            AppointmentSyncStatus::ReconciliationRequired => 'O resultado desta tentativa precisa ser reconciliado antes de um novo envio.',
            AppointmentSyncStatus::Rejected => 'Esta tentativa foi rejeitada. Revise as opções antes de tentar novamente.',
            default => 'Esta solicitação ainda está sendo processada.',
        });
    }

    /** @return array<string, mixed> */
    private function present(ConsultationAppointment $appointment): array
    {
        return [
            'id' => $appointment->uuid,
            'codigo' => $appointment->consultation_code,
            'especialidade' => $appointment->specialty_name,
            'medico' => $appointment->doctor_name,
            'status' => $appointment->provider_status,
            'agendadaPara' => $appointment->scheduled_for->toIso8601String(),
            'pago' => $appointment->is_paid,
            'preco' => $appointment->price !== null ? (float) $appointment->price : null,
            'request_id' => $appointment->request_id,
        ];
    }
}
