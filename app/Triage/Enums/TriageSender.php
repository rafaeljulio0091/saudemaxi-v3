<?php

namespace App\Triage\Enums;

enum TriageSender: string
{
    case Patient = 'patient';
    case Assistant = 'assistant';
}
