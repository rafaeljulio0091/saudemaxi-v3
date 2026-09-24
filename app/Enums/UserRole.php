<?php

namespace App\Enums;

enum UserRole: string
{
    case Patient = 'patient';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Patient => 'Paciente',
            self::Manager => 'Gestor da clínica',
        };
    }
}
