<?php

namespace App\AI\Support;

use RuntimeException;

final class MaxPrompt
{
    public function instructions(): string
    {
        $prompt = file_get_contents(resource_path('prompts/max-assistant.txt'));

        if ($prompt === false) {
            throw new RuntimeException('Não foi possível carregar as instruções do MAX.');
        }

        return $prompt;
    }
}
