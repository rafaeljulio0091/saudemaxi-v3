<?php

namespace App\Triage\DTO;

final readonly class SafetyResult
{
    public function __construct(
        public bool $isCritical,
        public ?string $ruleId,
        public string $version,
    ) {}
}
