<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O campo :attribute deve conter um CPF válido.');

            return;
        }

        for ($digit = 9; $digit < 11; $digit++) {
            $sum = 0;
            for ($index = 0; $index < $digit; $index++) {
                $sum += (int) $cpf[$index] * (($digit + 1) - $index);
            }

            $check = (10 * $sum) % 11;
            $check = $check === 10 ? 0 : $check;

            if ($check !== (int) $cpf[$digit]) {
                $fail('O campo :attribute deve conter um CPF válido.');

                return;
            }
        }
    }
}
