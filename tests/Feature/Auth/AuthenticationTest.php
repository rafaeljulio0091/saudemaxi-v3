<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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

    public function test_users_can_authenticate_with_a_locally_linked_cpf(): void
    {
        $user = User::factory()->create(['cpf' => '85676856050']);

        $response = $this->post('/login', [
            'email' => '856.768.560-50',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_cpf_login_still_checks_the_local_password(): void
    {
        // login-patient/ on the real provider does not validate any
        // password (confirmed against homologacao on 23/09/2026), so the
        // local password must remain the only thing that can grant a
        // session; a wrong password for a real, linked CPF must still fail.
        User::factory()->create(['cpf' => '85676856050']);

        $response = $this->post('/login', [
            'email' => '85676856050',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_with_an_unlinked_cpf_fails_without_creating_a_user(): void
    {
        $this->post('/login', [
            'email' => '85676856050',
            'password' => 'anything',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_rejects_an_identifier_that_is_neither_email_nor_cpf(): void
    {
        $response = $this->post('/login', [
            'email' => 'not-an-email-or-cpf',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_and_successful_login_never_log_the_password(): void
    {
        $user = User::factory()->create(['cpf' => '85676856050']);
        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $this->post('/login', ['email' => '85676856050', 'password' => 'super-secret-password']);

        $haystack = implode("\n", $logged);
        $this->assertStringNotContainsString('wrong-password', $haystack);
        $this->assertStringNotContainsString('super-secret-password', $haystack);
    }
}
