<?php

namespace App\AI\DTO;

final readonly class DecisionInput
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public array $state,
        public string $correlationId,
    ) {}
}
