<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_sees_the_patient_dashboard_with_real_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Maria Paciente']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Patient')
            ->where('roleLabel', 'Paciente')
        );
    }

    public function test_manager_is_redirected_from_dashboard_to_manager_area(): void
    {
        $manager = User::factory()->manager()->create();

        $response = $this->actingAs($manager)->get('/dashboard');

        $response->assertRedirect(route('healthcare.manager.dashboard'));
    }

    public function test_manager_sees_the_manager_dashboard_with_tenant_data(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Prefeitura de Queimados']);
        $manager = User::factory()->manager()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($manager)->get('/gestor/painel');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Manager')
            ->where('roleLabel', 'Gestor da clínica')
            ->where('tenant.name', 'Prefeitura de Queimados')
        );
    }

    public function test_patient_cannot_access_the_manager_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/gestor/painel');

        $response->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/gestor/painel')->assertRedirect(route('login'));
    }
}
