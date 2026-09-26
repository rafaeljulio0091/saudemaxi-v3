<?php

namespace App\AI\DTO;

final readonly class AssistantInput
{
    /**
     * @param  string  $message  already redacted by MaxAssistantService
     * @param  'patient'|'manager'  $profile
     */
    public function __construct(
        public string $message,
        public string $profile,
        public ?string $page,
        public string $correlationId,
    ) {}
}
