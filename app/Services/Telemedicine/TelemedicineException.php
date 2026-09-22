<?php

namespace App\Services\Telemedicine;

use Exception;

class TelemedicineException extends Exception
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function unavailable(): self
    {
        return new self('Não foi possível falar com a plataforma de atendimento agora.', 503);
    }

    public static function timeout(): self
    {
        return new self('A plataforma de atendimento demorou para responder.', 504);
    }

    public static function unauthorized(): self
    {
        // 401 is reserved for this app's own session expiry on the
        // frontend; a provider credential failure is a backend
        // configuration problem, not something the patient can fix by
        // logging in again, so it is surfaced as unavailable instead.
        return new self('A integração com a plataforma de atendimento não está autorizada.', 503);
    }

    public static function notFound(): self
    {
        return new self('Não encontramos este registro na plataforma de atendimento.', 404);
    }

    public static function rejected(string $message): self
    {
        return new self($message, 422);
    }
}
