<?php

namespace App\Services\Telemedicine;

final class LsxMedicalAuthResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly bool $providerUnavailable,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
    ) {}

    public static function success(string $email, ?string $name): self
    {
        return new self(successful: true, providerUnavailable: false, email: $email, name: $name);
    }

    public static function invalidCredentials(): self
    {
        return new self(successful: false, providerUnavailable: false);
    }

    public static function unavailable(): self
    {
        return new self(successful: false, providerUnavailable: true);
    }
}
