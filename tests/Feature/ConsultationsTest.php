<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsultationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/gestor/consultas')->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_the_manager_consultations_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/gestor/consultas')->assertForbidden();
    }

    public function test_manager_sees_an_empty_prompt_without_a_cpf_and_never_calls_the_provider(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake();

        $response = $this->actingAs($manager)->get('/gestor/consultas');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Consultations')
            ->where('consultations.count', 0)
            ->where('consultations.results', [])
        );

        Http::assertNothingSent();
    }

    public function test_manager_sees_consultation_history_for_a_given_cpf(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/consultation-history/*' => Http::response([
                'count' => 1,
                'next' => null,
                'previous' => null,
                'results' => [
                    ['code' => 'CN-1234', 'status' => 'FINISHED', 'specialty' => 'Clínica médica'],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($manager)->get('/gestor/consultas?cpf=12345678901');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Consultations')
            ->where('consultations.count', 1)
            ->where('consultations.results.0.code', 'CN-1234')
            ->where('error', null)
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/clinic/consultation-history/')
                && $request['cpf'] === '12345678901'
                && $request->hasHeader('Authorization');
        });
    }

    public function test_filters_are_forwarded_to_the_provider(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/consultation-history/*' => Http::response(['count' => 0, 'results' => []], 200),
        ]);

        $this->actingAs($manager)->get('/gestor/consultas?'.http_build_query([
            'cpf' => '12345678901',
            'status' => 'FINISHED',
            'doctor_cpf' => '98765432100',
            'start_date_min' => '2024-01-01',
            'start_date_max' => '2024-12-31',
        ]));

        Http::assertSent(function ($request) {
            return $request['status'] === 'FINISHED'
                && $request['doctor_cpf'] === '98765432100'
                && $request['start_date_min'] === '2024-01-01'
                && $request['start_date_max'] === '2024-12-31';
        });
    }

    public function test_consultations_page_shows_an_honest_error_when_the_provider_is_unavailable(): void
    {
        $manager = User::factory()->manager()->create();

        Http::fake([
            '*/api/clinic/consultation-history/*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($manager)->get('/gestor/consultas?cpf=12345678901');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Manager/Consultations')
            ->where('consultations.count', 0)
            ->where('consultations.results', [])
            ->has('error')
        );
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get('/gestor/consultas?cpf=12345678901&status=NOT_A_STATUS');

        $response->assertSessionHasErrors('status');
    }
}
