<?php

namespace App\AI\Contracts;

use App\AI\DTO\AssistantInput;
use App\AI\DTO\AssistantReplyResult;

interface AssistantReplyProvider
{
    /**
     * @param  list<string>  $allowedActions
     */
    public function reply(AssistantInput $input, array $allowedActions): AssistantReplyResult;
}
