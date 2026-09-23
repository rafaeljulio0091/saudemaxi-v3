<?php

namespace Tests\Unit\Services\Telemedicine;

use App\Services\Telemedicine\TelemedicineClient;
use App\Services\Telemedicine\TelemedicineException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelemedicineClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'telemedicine.base_url' => 'https://provider.test/api/clinic',
            'telemedicine.token' => 'test-token',
        ]);
    }

    public function test_login_patient_sends_only_cpf_with_the_bearer_token(): void
    {
        Http::fake(['provider.test/*' => Http::response([
            'magic_link' => 'https://provider.test/paciente/patient-login/abc/',
            'expires_at' => '2026-09-23T22:53:39.836748',
            'message' => 'Magic link gerado com sucesso',
        ], 200)]);

        $result = (new TelemedicineClient)->loginPatient('85676856050');

        $this->assertSame('https://provider.test/paciente/patient-login/abc/', $result['magic_link']);
        Http::assertSent(fn ($request) => $request->url() === 'https://provider.test/api/clinic/login-patient/'
            && $request['cpf'] === '85676856050'
            && ! array_key_exists('password', $request->data())
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_login_patient_rejects_malformed_cpf(): void
    {
        Http::fake(['provider.test/*' => Http::response(['error' => 'CPF inválido'], 400)]);

        try {
            (new TelemedicineClient)->loginPatient('00000000000');
            $this->fail('Expected a TelemedicineException.');
        } catch (TelemedicineException $e) {
            $this->assertSame(422, $e->status);
            $this->assertSame('CPF inválido', $e->getMessage());
        }
    }

    public function test_login_patient_without_service_token_is_unavailable(): void
    {
        Http::fake(['provider.test/*' => Http::response(null, 401)]);

        try {
            (new TelemedicineClient)->loginPatient('85676856050');
            $this->fail('Expected a TelemedicineException.');
        } catch (TelemedicineException $e) {
            $this->assertSame(503, $e->status);
        }
    }

    public function test_login_patient_maps_server_errors_to_unavailable(): void
    {
        Http::fake(['provider.test/*' => Http::response(null, 500)]);

        try {
            (new TelemedicineClient)->loginPatient('85676856050');
            $this->fail('Expected a TelemedicineException.');
        } catch (TelemedicineException $e) {
            $this->assertSame(503, $e->status);
        }
    }

    public function test_login_patient_maps_connection_timeout_to_unavailable(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        try {
            (new TelemedicineClient)->loginPatient('85676856050');
            $this->fail('Expected a TelemedicineException.');
        } catch (TelemedicineException $e) {
            $this->assertSame(504, $e->status);
        }
    }

    public function test_login_patient_handles_invalid_json_body_gracefully(): void
    {
        Http::fake(['provider.test/*' => Http::response('<html>not json</html>', 200, ['Content-Type' => 'text/html'])]);

        $result = (new TelemedicineClient)->loginPatient('85676856050');

        $this->assertSame([], $result);
    }

    public function test_missing_configuration_is_reported_as_unavailable_without_an_http_call(): void
    {
        config(['telemedicine.token' => null]);
        Http::fake();

        try {
            (new TelemedicineClient)->loginPatient('85676856050');
            $this->fail('Expected a TelemedicineException.');
        } catch (TelemedicineException $e) {
            $this->assertSame(503, $e->status);
        }
        Http::assertNothingSent();
    }
}
