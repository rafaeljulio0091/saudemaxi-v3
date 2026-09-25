<?php

namespace App\Triage\Rules;

use Illuminate\Support\Str;

class PatientMessageGuard
{
    public function isSafe(string $message): bool
    {
        $normalized = Str::lower(Str::ascii($message));

        return ! Str::contains($normalized, [
            'nao e uma emergencia',
            'voce nao precisa de atendimento',
            'voce tem ',
            'interrompa o medicamento',
            'pare de tomar',
            'aumente a dose',
            'reduza a dose',
        ]);
    }
}
