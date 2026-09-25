<?php

namespace App\AI\Contracts;

use App\AI\DTO\DecisionInput;
use App\AI\DTO\DecisionResult;

interface DecisionAIProvider
{
    public function classify(DecisionInput $input): DecisionResult;
}
