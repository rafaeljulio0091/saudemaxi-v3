<?php

namespace App\Services\Telemedicine;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LsxMedicalAuthenticator
{
    public function __construct(private readonly LsxMedicalAuthClient $client) {}

    /**
     * Authenticate the given credentials against the lsxmedical API and,
     * on success, log the matching local user in.
     *
     * @param  array{email?: string, password?: string}  $credentials
     */
    public function attempt(array $credentials, bool $remember = false): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (! $email || ! $password) {
            return false;
        }

        $result = $this->client->authenticate($email, $password);

        if (! $result->successful) {
            if ($result->providerUnavailable) {
                Log::error('lsxmedical.login.unavailable');
            }

            return false;
        }

        $user = User::firstOrNew(['email' => $result->email]);
        $user->name = $result->name ?: ($user->name ?: $result->email);

        if (! $user->exists) {
            $user->password = Hash::make(Str::random(40));
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, $remember);

        return true;
    }
}
