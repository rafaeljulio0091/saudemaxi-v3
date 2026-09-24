<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard for the authenticated user's role.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->user()->role === UserRole::Manager) {
            return redirect()->route('healthcare.manager.dashboard');
        }

        return $this->render($request, 'Dashboard/Patient');
    }

    /**
     * Display the clinic manager dashboard.
     */
    public function manager(Request $request): Response
    {
        return $this->render($request, 'Dashboard/Manager');
    }

    private function render(Request $request, string $component): Response
    {
        $user = $request->user();
        $tenant = $user->tenant;

        return Inertia::render($component, [
            'roleLabel' => $user->role->label(),
            'tenant' => $tenant ? [
                'name' => $tenant->name,
                'brand_color' => $tenant->brand_color,
            ] : null,
        ]);
    }
}
