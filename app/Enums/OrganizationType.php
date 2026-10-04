<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Clinic = 'clinic';
    case Hospital = 'hospital';
    case MunicipalSecretariat = 'municipal_secretariat';
    case Laboratory = 'laboratory';
    case Pharmacy = 'pharmacy';
    case Other = 'other';
}
