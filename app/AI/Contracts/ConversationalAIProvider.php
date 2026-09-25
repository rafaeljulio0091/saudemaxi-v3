<?php

namespace App\AI\Contracts;

use App\AI\DTO\ConversationInput;
use App\AI\DTO\ConversationResult;

interface ConversationalAIProvider
{
    public function respond(ConversationInput $input): ConversationResult;
}
