<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConsultationsTest extends TestCase
{
    use RefreshDatabase;

    private function manager(): User
    {
        return User::factory()->manager()->create([
            'tenant_id' => Tenant::factory()->create()->id,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/gestor/consultas')->assertRedirect(route('login'));
    }

    public function test_patient_and_manager_without_tenant_cannot_access_manager_consultations(): void
    {
        $patient = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $orphanManager = User::factory()->manager()->create(['tenant_id' => null]);

        $this->actingAs($patient)->get('/gestor/consultas')->assertForbidden();
        $this->actingAs($orphanManager)->get('/gestor/consultas')->assertForbidden();
        $this->actingAs($orphanManager)->postJson('/gestor/dados/consultations-search')->assertForbidden();
    }

    public function test_manager_page_does_not_put_cpf_in_the_url_or_call_provider(): void
    {
        $manager = $this->manager();
        Http::fake();

        $this->actingAs($manager)->get('/gestor/consultas?cpf=52998224725')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Healthcare/Shared/Consultations')
                ->where('healthcare.profile', 'manager')
                ->missing('filters'));

        Http::assertNothingSent();
    }

    public function test_manager_searches_consultations_with_cpf_in_post_body(): void
    {
        $manager = $this->manager();
        Http::fake([
            '*/api/clinic/consultation-history/*' => Http::response([
                'count' => 1,
                'results' => [[
                    'code' => 'CN-1234',
                    'status' => 'FINISHED',
                    'specialty' => 'Clínica médica',
                    'doctor_name' => 'Dra. Ana',
                ]],
            ]),
        ]);

        $this->actingAs($manager)->postJson('/gestor/dados/consultations-search', [
            'search' => '529.982.247-25',
            'status' => 'FINISHED',
            'doctor_cpf' => '529.982.247-25',
            'start_date_min' => '2024-01-01',
            'start_date_max' => '2024-12-31',
            'page' => 1,
        ])->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('results.0.codigo', 'CN-1234')
            ->assertJsonPath('results.0.medico', 'Dra. Ana');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/clinic/consultation-history/')
            && $request['cpf'] === '52998224725'
            && $request['status'] === 'FINISHED'
            && $request['doctor_cpf'] === '52998224725'
            && $request['start_date_min'] === '2024-01-01'
            && $request['start_date_max'] === '2024-12-31'
            && $request->hasHeader('Authorization'));
    }

    public function test_empty_search_never_calls_provider(): void
    {
        $manager = $this->manager();
        Http::fake();

        $this->actingAs($manager)->postJson('/gestor/dados/consultations-search', [])
            ->assertOk()->assertJsonPath('count', 0)->assertJsonPath('results', []);

        Http::assertNothingSent();
    }

    public function test_provider_failure_is_reported_without_exposing_payload(): void
    {
        $manager = $this->manager();
        Http::fake(['*/api/clinic/consultation-history/*' => Http::response(null, 500)]);

        $this->actingAs($manager)->postJson('/gestor/dados/consultations-search', [
            'search' => '52998224725',
        ])->assertStatus(503)
            ->assertJsonMissing(['search' => '52998224725']);
    }

    public function test_sensitive_filters_are_validated_and_tenant_is_prohibited(): void
    {
        $manager = $this->manager();
        Http::fake();

        $this->actingAs($manager)->postJson('/gestor/dados/consultations-search', [
            'search' => '12345678901',
            'status' => 'NOT_A_STATUS',
            'tenant_id' => Tenant::factory()->create()->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['search', 'status', 'tenant_id']);

        Http::assertNothingSent();
    }
}
