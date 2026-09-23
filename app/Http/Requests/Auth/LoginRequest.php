<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The identifier accepts an e-mail or a CPF (11 digits), matching the
     * unified login already shown on the reference product's access
     * screens. Whichever shape it takes, the password below is always
     * checked locally; see LoginRequest::credentials().
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL) && ! $this->looksLikeCpf($value)) {
                    $fail('Informe um e-mail ou um CPF válido.');
                }
            }],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->credentials(), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Builds the credentials array for Auth::attempt(), matching the
     * identifier against the local "cpf" or "email" column.
     *
     * The password is always verified locally (bcrypt). SaudeMaxi does not
     * delegate password checks to the telemedicine provider: its
     * login-patient/ endpoint only confirms a CPF is registered and does
     * not validate any password, so it cannot be used as a credential
     * check without letting anyone who knows a patient's CPF sign in as
     * them (see AGENTS.md sections 15 and 25).
     *
     * @return array<string, string>
     */
    private function credentials(): array
    {
        $identifier = trim((string) $this->string('email'));
        $column = $this->looksLikeCpf($identifier) ? 'cpf' : 'email';
        $value = $column === 'cpf' ? preg_replace('/\D/', '', $identifier) : $identifier;

        return [$column => $value, 'password' => $this->string('password')->value()];
    }

    private function looksLikeCpf(string $value): bool
    {
        return preg_match('/^\d{11}$/', preg_replace('/\D/', '', $value)) === 1
            && ! str_contains($value, '@');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
