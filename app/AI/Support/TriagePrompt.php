<?php

namespace App\AI\Support;

use RuntimeException;

final class TriagePrompt
{
    public function instructions(): string
    {
        $prompt = file_get_contents(resource_path('prompts/triage-assistant.txt'));

        if ($prompt === false) {
            throw new RuntimeException('Não foi possível carregar as instruções da triagem.');
        }

        return $prompt;
    }
}
