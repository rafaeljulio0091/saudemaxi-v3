<?php

namespace App\Policies;

use App\Models\User;

class PharmacyPolicy extends TenantResourcePolicy
{
    public function viewDirectory(User $user): bool
    {
        return $user->isPatient() && $user->tenant_id !== null;
    }
}
