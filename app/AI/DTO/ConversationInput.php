<?php

namespace App\AI\DTO;

final readonly class ConversationInput
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function __construct(
        public array $messages,
        public string $correlationId,
    ) {}
}
