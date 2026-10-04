<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O campo :attribute deve conter um CNPJ válido.');

            return;
        }

        foreach ([12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], 13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $position => $weights) {
            $sum = 0;
            foreach ($weights as $index => $weight) {
                $sum += (int) $cnpj[$index] * $weight;
            }

            $remainder = $sum % 11;
            $check = $remainder < 2 ? 0 : 11 - $remainder;

            if ($check !== (int) $cnpj[$position]) {
                $fail('O campo :attribute deve conter um CNPJ válido.');

                return;
            }
        }
    }
}
