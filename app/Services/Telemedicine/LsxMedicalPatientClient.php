<?php

namespace App\Services\Telemedicine;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function search(array $query): array
    {
        return $this->request(
            fn (PendingRequest $http) => $http->get(config('lsxmedical.filter_patients_endpoint'), $query),
            'filter-patients',
        );
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

    /**
     * @param  callable(PendingRequest): Response  $call
     * @return array<string, mixed>
     */
    private function request(callable $call, string $operation): array
    {
        $http = Http::baseUrl(config('lsxmedical.base_url'))
            ->withToken(config('lsxmedical.service_token'))
            ->acceptJson()
            ->timeout(config('lsxmedical.timeout'));

        try {
            $response = $call($http);
        } catch (ConnectionException $e) {
            Log::error("lsxmedical.{$operation}.connection_error", [
                'message' => $e->getMessage(),
            ]);

            throw new TelemedicineApiException(
                'Não foi possível contatar o provedor de telemedicina.',
                'unavailable',
            );
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        if ($response->status() === 401 || $response->status() === 403) {
            Log::error("lsxmedical.{$operation}.unauthorized", ['status' => $response->status()]);

            throw new TelemedicineApiException(
                'A integração com o provedor de telemedicina não está autorizada.',
                'unauthorized',
                $response->status(),
            );
        }

        if ($response->status() === 404) {
            throw new TelemedicineApiException(
                'Recurso não encontrado no provedor de telemedicina.',
                'not_found',
                404,
            );
        }

        if ($response->status() === 422) {
            throw new TelemedicineApiException(
                'O provedor de telemedicina rejeitou os dados informados.',
                'invalid',
                422,
                (array) ($response->json('errors') ?? $response->json() ?? []),
            );
        }

        if ($response->status() === 409) {
            throw new TelemedicineApiException(
                'O provedor de telemedicina rejeitou a operação.',
                'business_rejection',
                409,
            );
        }

        Log::error("lsxmedical.{$operation}.request_failed", ['status' => $response->status()]);

        throw new TelemedicineApiException(
            'O provedor de telemedicina está indisponível no momento.',
            'unavailable',
            $response->status(),
        );
    }
}
