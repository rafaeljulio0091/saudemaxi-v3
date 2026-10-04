<?php

namespace App\Enums;

enum AppointmentSyncStatus: string
{
    case Creating = 'creating';
    case Confirmed = 'confirmed';
    case ReconciliationRequired = 'reconciliation_required';
    case Rejected = 'rejected';
}
