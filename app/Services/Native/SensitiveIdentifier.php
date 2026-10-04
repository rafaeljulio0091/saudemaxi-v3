<?php

namespace App\Services\Native;

use LogicException;

class SensitiveIdentifier
{
    public function digits(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return preg_replace('/\D/', '', $value);
    }

    public function email(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return mb_strtolower(trim($value));
    }

    public function hash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = (string) config('privacy.identifier_hash_key');
        if ($key === '') {
            throw new LogicException('A chave de índice de privacidade não está configurada.');
        }

        return hash_hmac('sha256', $value, $key);
    }

    public function maskCpf(?string $value): ?string
    {
        $cpf = $this->digits($value);
        if ($cpf === null || strlen($cpf) !== 11) {
            return null;
        }

        return sprintf('***.%s.%s-**', substr($cpf, 3, 3), substr($cpf, 6, 3));
    }

    public function maskEmail(?string $value): ?string
    {
        $email = $this->email($value);
        if ($email === null || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    public function maskPhone(?string $value): ?string
    {
        $phone = $this->digits($value);
        if ($phone === null || strlen($phone) < 4) {
            return null;
        }

        return str_repeat('*', max(strlen($phone) - 4, 0)).substr($phone, -4);
    }
}
