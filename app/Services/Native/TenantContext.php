<?php

namespace App\Services\Native;

use App\Models\Tenant;
use App\Models\User;

class TenantContext
{
    public function manager(User $user): Tenant
    {
        $user->loadMissing('tenant');
        abort_unless($user->isManager() && $user->tenant, 403, 'Gestor sem vínculo com um cliente.');

        return $user->tenant;
    }

    public function patient(User $user): Tenant
    {
        $user->loadMissing('tenant');
        abort_unless($user->isPatient() && $user->tenant, 403, 'Paciente sem vínculo com um cliente.');

        return $user->tenant;
    }
}
