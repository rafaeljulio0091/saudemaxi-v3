<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantResources;

class PatientPolicy
{
    use AuthorizesTenantResources;

    public function view(User $user, Patient $patient): bool
    {
        if ($this->managerOwns($user, $patient)) {
            return true;
        }

        return $user->isPatient()
            && $user->tenant_id !== null
            && (int) $user->tenant_id === (int) $patient->tenant_id
            && (int) $patient->user_id === (int) $user->id;
    }
}
