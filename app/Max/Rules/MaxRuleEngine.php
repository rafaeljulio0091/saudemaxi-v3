<?php

namespace App\Max\Rules;

use App\Triage\Rules\SafetyRuleEngine;
use Illuminate\Support\Str;

/**
 * Deterministic business rules of the MAX assistant, evaluated before and
 * independently of any AI provider. Emergency always wins: it also honours
 * the clinically approved rules of SafetyRuleEngine (config/triage.php).
 *
 * The keyword lists are the ones already used by the demo assistant.
 */
class MaxRuleEngine
{
    public const INTENTS = ['emergency', 'privacy', 'medication', 'referral', 'navigation'];

    private const TERMS = [
        'emergency' => ['dor no peito', 'peito apertado', 'falta de ar', 'nao consigo respirar', 'desmaio', 'desmaiei', 'convuls', 'sangrando', 'boca torta', 'avc', 'infarto', 'me matar', 'suicid'],
        'privacy' => ['mental', 'nr1', 'nr-1', 'psicossocial', 'depress', 'ansiedade'],
        'medication' => ['medicament', 'receita', 'remedio', 'farmacia', 'trocar'],
        'referral' => ['dor', 'febre', 'tosse', 'enjoo', 'nausea', 'tontura', 'vomit', 'sinto', 'estou mal'],
    ];

    public function __construct(private SafetyRuleEngine $safety) {}

    public function intentFor(string $message): string
    {
        if ($this->safety->inspect($message)->isCritical) {
            return 'emergency';
        }

        $text = Str::lower(Str::ascii($message));

        foreach (self::TERMS as $intent => $terms) {
            if (Str::contains($text, $terms)) {
                return $intent;
            }
        }

        return 'navigation';
    }
}
