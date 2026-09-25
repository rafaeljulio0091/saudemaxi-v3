<?php

namespace App\Policies;

use App\Models\TriageSession;
use App\Models\User;

class TriageSessionPolicy
{
    public function view(User $user, TriageSession $session): bool
    {
        if (! $user->tenant_id || $user->tenant_id !== $session->tenant_id) {
            return false;
        }

        return $user->isPatient() && $session->patient_id === $user->id;
    }

    public function sendMessage(User $user, TriageSession $session): bool
    {
        return $user->isPatient()
            && $user->tenant_id !== null
            && $user->tenant_id === $session->tenant_id
            && $session->patient_id === $user->id;
    }
}
