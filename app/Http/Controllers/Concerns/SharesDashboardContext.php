<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

trait SharesDashboardContext
{
    /**
     * Common Inertia props shared by every page inside the sidebar layout:
     * the user's role label and (when set) their tenant's display data.
     *
     * @return array{roleLabel: string, tenant: array{name: string, brand_color: ?string}|null}
     */
    protected function dashboardContext(User $user): array
    {
        $tenant = $user->tenant;

        return [
            'roleLabel' => $user->role->label(),
            'tenant' => $tenant ? [
                'name' => $tenant->name,
                'brand_color' => $tenant->brand_color,
            ] : null,
        ];
    }
}
