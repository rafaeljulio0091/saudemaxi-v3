<?php

namespace App\Services\Telemedicine;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LsxMedicalAuthClient
{
    public function authenticate(string $email, string $password): LsxMedicalAuthResult
    {
        try {
            $response = Http::baseUrl(config('lsxmedical.base_url'))
                ->withToken(config('lsxmedical.service_token'))
                ->acceptJson()
                ->timeout(config('lsxmedical.timeout'))
                ->post(config('lsxmedical.login_endpoint'), [
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (ConnectionException $e) {
            Log::error('lsxmedical.login.connection_error', [
                'message' => $e->getMessage(),
            ]);

            return LsxMedicalAuthResult::unavailable();
        }

        if (in_array($response->status(), [401, 422], true)) {
            return LsxMedicalAuthResult::invalidCredentials();
        }

        if ($response->failed()) {
            Log::error('lsxmedical.login.request_failed', [
                'status' => $response->status(),
            ]);

            return LsxMedicalAuthResult::unavailable();
        }

        $data = $response->json();

        if (empty($data['email'])) {
            Log::error('lsxmedical.login.invalid_response');

            return LsxMedicalAuthResult::unavailable();
        }

        return LsxMedicalAuthResult::success($data['email'], $data['name'] ?? null);
    }
}
