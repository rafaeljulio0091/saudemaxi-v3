<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_login_uses_local_database_when_lsxmedical_login_is_disabled(): void
    {
        config(['lsxmedical.login_enabled' => false]);

        Http::fake();

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        Http::assertNothingSent();
    }

    public function test_login_authenticates_against_lsxmedical_api_when_enabled(): void
    {
        config(['lsxmedical.login_enabled' => true]);

        Http::fake([
            '*/api/clinic/patients/authenticate' => Http::response([
                'email' => 'paciente@example.com',
                'name' => 'Paciente Teste',
            ], 200),
        ]);

        $response = $this->post('/login', [
            'email' => 'paciente@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'paciente@example.com',
            'name' => 'Paciente Teste',
        ]);
    }

    public function test_login_via_lsxmedical_api_fails_with_invalid_credentials(): void
    {
        config(['lsxmedical.login_enabled' => true]);

        Http::fake([
            '*/api/clinic/patients/authenticate' => Http::response(null, 401),
        ]);

        $this->post('/login', [
            'email' => 'paciente@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'paciente@example.com',
        ]);
    }

    public function test_login_via_lsxmedical_api_fails_gracefully_when_provider_is_unavailable(): void
    {
        config(['lsxmedical.login_enabled' => true]);

        Http::fake([
            '*/api/clinic/patients/authenticate' => Http::response(null, 500),
        ]);

        $this->post('/login', [
            'email' => 'paciente@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertGuest();
    }
}
