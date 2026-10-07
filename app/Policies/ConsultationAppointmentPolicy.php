<?php

namespace App\Policies;

use App\Models\ConsultationAppointment;
use App\Models\User;

class ConsultationAppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPatient() && $user->tenant_id !== null;
    }

    public function view(User $user, ConsultationAppointment $appointment): bool
    {
        return $this->viewAny($user)
            && (int) $user->tenant_id === (int) $appointment->tenant_id
            && (int) $user->id === (int) $appointment->user_id;
    }
}
