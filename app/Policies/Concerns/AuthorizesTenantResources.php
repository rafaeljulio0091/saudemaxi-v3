<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesTenantResources
{
    public function viewAny(User $user): bool
    {
        return $user->isManager() && $user->tenant_id !== null;
    }

    public function view(User $user, Model $resource): bool
    {
        return $this->managerOwns($user, $resource);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $resource): bool
    {
        return $this->managerOwns($user, $resource);
    }

    public function delete(User $user, Model $resource): bool
    {
        return $this->managerOwns($user, $resource);
    }

    public function restore(User $user, Model $resource): bool
    {
        return $this->managerOwns($user, $resource);
    }

    protected function managerOwns(User $user, Model $resource): bool
    {
        return $user->isManager()
            && $user->tenant_id !== null
            && (int) $user->tenant_id === (int) $resource->getAttribute('tenant_id');
    }
}
