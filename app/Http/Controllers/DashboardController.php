<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SharesDashboardContext;
use App\Services\Healthcare\HealthcareContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use SharesDashboardContext;

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
    public function manager(Request $request, HealthcareContext $context): Response
    {
        // Without a tenant there is nothing to manage yet: keep the previous
        // screen, which explains that the user is not linked to a client.
        if (! $request->user()->tenant) {
            return $this->render($request, 'Dashboard/Manager');
        }

        return Inertia::render('Healthcare/Manager/Dashboard', [
            'healthcare' => $context->forManager($request->user()),
            'title' => 'Painel de gestão',
        ]);
    }

    private function render(Request $request, string $component): Response
    {
        return Inertia::render($component, $this->dashboardContext($request->user()));
    }
}
