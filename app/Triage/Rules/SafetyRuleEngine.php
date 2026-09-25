<?php

namespace App\Triage\Rules;

use App\Triage\DTO\SafetyResult;
use Illuminate\Support\Str;

class SafetyRuleEngine
{
    public function inspect(string $message): SafetyResult
    {
        $version = (string) config('triage.safety.version');
        $normalized = Str::lower(Str::ascii($message));

        foreach ((array) config('triage.safety.rules', []) as $rule) {
            if (! is_array($rule) || blank($rule['id'] ?? null)) {
                continue;
            }

            $terms = array_filter((array) ($rule['terms_any'] ?? []), 'is_string');

            foreach ($terms as $term) {
                if ($term !== '' && Str::contains($normalized, Str::lower(Str::ascii($term)))) {
                    return new SafetyResult(true, (string) $rule['id'], $version);
                }
            }
        }

        return new SafetyResult(false, null, $version);
    }
}
