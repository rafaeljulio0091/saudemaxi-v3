<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PatientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/gestor/pacientes')->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_the_manager_patients_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/gestor/pacientes')->assertForbidden();
    }

    public function test_manager_sees_patients_returned_by_the_telemedicine_provider(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/filter-patients/*' => Http::response([
                'count' => 1,
                'next' => null,
                'previous' => null,
                'results' => [
                    ['name' => 'João da Silva', 'cpf' => '123.456.789-00', 'email' => 'joao@email.com'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($manager)->get('/gestor/pacientes');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Patients')
            ->where('patients.count', 1)
            ->where('patients.results.0.name', 'João da Silva')
            ->where('error', null)
        );

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization')
            && str_contains($request->url(), '/api/clinic/filter-patients/'));
    }

    public function test_patients_page_shows_an_honest_error_when_the_provider_is_unavailable(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/filter-patients/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($manager)->get('/gestor/pacientes');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Patients')
            ->where('patients.count', 0)
            ->where('patients.results', [])
            ->has('error')
            ->where('error', fn (string $message) => str_contains($message, 'indisponível'))
        );
    }

    public function test_manager_can_create_a_patient_via_the_telemedicine_provider(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/create-patient/*' => Http::response([
                'name' => 'João da Silva',
                'cpf' => '123.456.789-00',
            ], 201),
        ]);

        $response = $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'João da Silva',
            'cpf' => '123.456.789-00',
            'email' => 'joao@email.com',
            'birth_date' => '1990-01-01',
            'phone' => '11999999999',
            'address' => [
                'street' => 'Rua Exemplo',
                'city' => 'São Paulo',
                'state' => 'SP',
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('healthcare.manager.patients'));

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/clinic/create-patient/')
                && $request['cpf'] === '123.456.789-00'
                && $request['address']['city'] === 'São Paulo';
        });
    }

    public function test_patient_creation_requires_name_and_cpf(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake();

        $response = $this->actingAs($manager)->post('/gestor/pacientes', []);

        $response->assertSessionHasErrors(['name', 'cpf']);
        Http::assertNothingSent();
    }

    public function test_patient_creation_surfaces_provider_validation_errors(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/create-patient/*' => Http::response([
                'errors' => ['cpf' => ['CPF já cadastrado.']],
            ], 422),
        ]);

        $response = $this->actingAs($manager)->post('/gestor/pacientes', [
            'name' => 'João da Silva',
            'cpf' => '123.456.789-00',
        ]);

        $response->assertSessionHasErrors(['cpf']);
    }
}
