<?php

namespace App\AI\Contracts;

use App\AI\DTO\AssistantInput;
use App\AI\DTO\AssistantIntentResult;

interface AssistantIntentProvider
{
    public function classifyIntent(AssistantInput $input): AssistantIntentResult;
}
