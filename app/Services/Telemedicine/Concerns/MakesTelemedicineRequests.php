<?php

namespace App\Services\Telemedicine\Concerns;

use App\Services\Telemedicine\TelemedicineApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared HTTP plumbing for the lsxmedical /api/clinic/* clients: base URL,
 * bearer token, timeout, and mapping the provider's response into a
 * TelemedicineApiException that distinguishes the failure reason, per
 * AGENTS.md section 16.
 */
trait MakesTelemedicineRequests
{
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
