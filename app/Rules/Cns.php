<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cns implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cns = preg_replace('/\D/', '', (string) $value);

        if (strlen($cns) !== 15 || preg_match('/^(\d)\1{14}$/', $cns)) {
            $fail('O campo :attribute deve conter um CNS válido.');

            return;
        }

        $sum = 0;
        foreach ([15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1] as $index => $weight) {
            $sum += (int) $cns[$index] * $weight;
        }

        if ($sum % 11 !== 0) {
            $fail('O campo :attribute deve conter um CNS válido.');
        }
    }
}
