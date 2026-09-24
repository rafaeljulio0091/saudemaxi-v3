<?php

namespace App\Services\Telemedicine;

use App\Services\Telemedicine\Concerns\MakesTelemedicineRequests;
use Illuminate\Http\Client\PendingRequest;

/**
 * Client for the patient endpoints of the lsxmedical telemedicine provider
 * (base context /api/clinic/), documented in AGENTS.md section 12.
 *
 * The query parameters accepted by "filter-patients" beyond pagination and a
 * free text search are not documented anywhere available to this codebase;
 * they are a best-effort guess and must be validated against a real
 * homologation token before relying on them in production.
 */
class LsxMedicalPatientClient
{
    use MakesTelemedicineRequests;

    /**
     * @param  array{search?: ?string, status?: ?string, holder?: ?string, page?: ?int}  $filters
     * @return array{count: int, results: array<int, array<string, mixed>>}
     */
    public function search(array $filters): array
    {
        // The "search", "status" and "is_dependent" query parameter names are a
        // best-effort guess: the real contract of GET filter-patients/ could
        // not be verified against a homologation token from this environment.
        $query = array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'is_dependent' => match ($filters['holder'] ?? null) {
                'titular' => false,
                'dependente' => true,
                default => null,
            },
            'page' => $filters['page'] ?? 1,
            'page_size' => 10,
        ], fn ($value) => $value !== null);

        $result = $this->request(
            fn (PendingRequest $http) => $http->get(config('lsxmedical.filter_patients_endpoint'), $query),
            'filter-patients',
        );

        return [
            'count' => $result['count'] ?? 0,
            'results' => $result['results'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->request(
            fn (PendingRequest $http) => $http->post(config('lsxmedical.create_patient_endpoint'), $payload),
            'create-patient',
        );
    }
}
