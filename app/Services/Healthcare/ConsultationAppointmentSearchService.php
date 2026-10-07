<?php

namespace App\Services\Healthcare;

use App\Enums\AppointmentSyncStatus;
use App\Models\ConsultationAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ConsultationAppointmentSearchService
{
    /**
     * @param  array{search?: string|null, status?: string|null, page?: int, per_page?: int}  $input
     * @return array{count: int, results: list<array<string, mixed>>, page: int, per_page: int}
     */
    public function search(User $patient, array $input): array
    {
        abort_unless($patient->isPatient() && $patient->tenant_id !== null, 403);

        $page = (int) ($input['page'] ?? 1);
        $perPage = (int) ($input['per_page'] ?? 10);
        $search = trim((string) ($input['search'] ?? ''));
        $query = $this->query($patient, $input['status'] ?? null);

        if ($search !== '') {
            return $this->searchEncryptedFields($query, $search, $page, $perPage);
        }

        $count = (clone $query)->count();
        $results = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (ConsultationAppointment $appointment): array => $this->present($appointment))
            ->all();

        return [
            'count' => $count,
            'results' => $results,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return Builder<ConsultationAppointment> */
    private function query(User $patient, ?string $status): Builder
    {
        return ConsultationAppointment::query()
            ->select([
                'id',
                'uuid',
                'consultation_code',
                'specialty_name',
                'doctor_name',
                'scheduled_for',
                'provider_status',
            ])
            ->where('tenant_id', $patient->tenant_id)
            ->where('user_id', $patient->id)
            ->where('sync_status', AppointmentSyncStatus::Confirmed)
            ->when($status, fn (Builder $query, string $status): Builder => $query
                ->where('provider_status', $status))
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id');
    }

    /**
     * Names are encrypted at rest, so a database LIKE would not work. The
     * cursor keeps memory bounded while the query remains scoped to one
     * authenticated patient and tenant.
     *
     * @param  Builder<ConsultationAppointment>  $query
     * @return array{count: int, results: list<array<string, mixed>>, page: int, per_page: int}
     */
    private function searchEncryptedFields(Builder $query, string $search, int $page, int $perPage): array
    {
        $needle = $this->normalize($search);
        $offset = ($page - 1) * $perPage;
        $count = 0;
        $results = [];

        foreach ($query->cursor() as $appointment) {
            if (! $this->matches($appointment, $needle)) {
                continue;
            }

            if ($count >= $offset && count($results) < $perPage) {
                $results[] = $this->present($appointment);
            }

            $count++;
        }

        return [
            'count' => $count,
            'results' => $results,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function matches(ConsultationAppointment $appointment, string $needle): bool
    {
        foreach ([
            $appointment->consultation_code,
            $appointment->specialty_name,
            $appointment->doctor_name,
        ] as $value) {
            if (str_contains($this->normalize((string) $value), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii($value));
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
            'duracao' => null,
        ];
    }
}
