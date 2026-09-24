<?php

namespace App\Services\Telemedicine;

use App\Services\Telemedicine\Concerns\MakesTelemedicineRequests;
use Illuminate\Http\Client\PendingRequest;

/**
 * Client for GET /api/clinic/consultation-history/, the only documented way
 * to reconcile consultation state, since the provider has no webhook
 * (AGENTS.md section 12.4).
 *
 * Every documented example scopes the query by patient "cpf", so this client
 * treats it as required and never calls the provider without one.
 */
class LsxMedicalConsultationClient
{
    use MakesTelemedicineRequests;

    /**
     * @param  array{cpf?: ?string, status?: ?string, doctor_cpf?: ?string, start_date_min?: ?string, start_date_max?: ?string, page?: ?int}  $filters
     * @return array{count: int, results: array<int, array<string, mixed>>}
     */
    public function search(array $filters): array
    {
        if (empty($filters['cpf'])) {
            return ['count' => 0, 'results' => []];
        }

        $query = array_filter([
            'cpf' => $filters['cpf'],
            'status' => $filters['status'] ?? null,
            'doctor_cpf' => $filters['doctor_cpf'] ?? null,
            'start_date_min' => $filters['start_date_min'] ?? null,
            'start_date_max' => $filters['start_date_max'] ?? null,
            'page' => $filters['page'] ?? 1,
        ], fn ($value) => $value !== null && $value !== '');

        $result = $this->request(
            fn (PendingRequest $http) => $http->get(config('lsxmedical.consultation_history_endpoint'), $query),
            'consultation-history',
        );

        // The response shape (plain list vs. a DRF-style {count, results}
        // envelope) is not documented for this endpoint; handle both.
        if (array_is_list($result)) {
            return ['count' => count($result), 'results' => $result];
        }

        return [
            'count' => $result['count'] ?? count($result['results'] ?? []),
            'results' => $result['results'] ?? [],
        ];
    }
}
