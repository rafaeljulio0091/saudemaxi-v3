<?php

namespace App\Services\Telemedicine;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Centralized access to the external telemedicine platform's clinic API.
 * See AGENTS.md section 12 for the integration contract and constraints.
 */
class TelemedicineClient
{
    public function createEmergencyConsultation(string $cpf, string $externalUrl): array
    {
        return $this->post('create-emergency-consultation/', [
            'cpf' => $cpf,
            'external_url' => $externalUrl,
        ]);
    }

    public function consultationHistory(): array
    {
        return $this->get('consultation-history/');
    }

    public function updatePatient(string $cpf, array $fields): array
    {
        return $this->request('patch', 'update-patient/', [...$fields, 'cpf' => $cpf]);
    }

    public function createPatient(array $fields): array
    {
        return $this->post('create-patient/', $fields);
    }

    private function get(string $path): array
    {
        return $this->request('get', $path);
    }

    private function post(string $path, array $payload): array
    {
        return $this->request('post', $path, $payload);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $baseUrl = config('telemedicine.base_url');
        $token = config('telemedicine.token');
        if (! $baseUrl || ! $token) {
            throw TelemedicineException::unavailable();
        }

        try {
            $response = $this->client()->send($method, rtrim($baseUrl, '/').'/'.ltrim($path, '/'), [
                'json' => $payload !== [] ? $payload : null,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('telemedicine.connection_failed', ['path' => $path]);
            throw TelemedicineException::timeout();
        } catch (RequestException $e) {
            // withOptions(['http_errors' => false]) keeps the higher-level
            // get()/post() helpers from throwing on 4xx/5xx, but send() can
            // still raise this; route it through the same status mapping.
            return $this->handle($e->response, $path);
        }

        return $this->handle($response, $path);
    }

    private function client(): PendingRequest
    {
        return Http::withToken(config('telemedicine.token'))
            ->acceptJson()
            ->timeout((int) config('telemedicine.timeout', 15))
            ->withOptions(['http_errors' => false]);
    }

    private function handle(Response $response, string $path): array
    {
        if ($response->status() === 401 || $response->status() === 403) {
            Log::warning('telemedicine.unauthorized', ['path' => $path]);
            throw TelemedicineException::unauthorized();
        }
        if ($response->status() === 404) {
            throw TelemedicineException::notFound();
        }
        if ($response->status() === 422) {
            throw TelemedicineException::rejected($this->safeMessage($response));
        }
        if ($response->serverError()) {
            Log::warning('telemedicine.server_error', ['path' => $path, 'status' => $response->status()]);
            throw TelemedicineException::unavailable();
        }
        if (! $response->successful()) {
            Log::warning('telemedicine.unexpected_status', ['path' => $path, 'status' => $response->status()]);
            throw TelemedicineException::unavailable();
        }

        return $response->json() ?? [];
    }

    private function safeMessage(Response $response): string
    {
        $message = $response->json('error') ?? $response->json('message');

        return is_string($message) && $message !== ''
            ? Str::limit($message, 200)
            : 'A plataforma de atendimento recusou a solicitação.';
    }
}
