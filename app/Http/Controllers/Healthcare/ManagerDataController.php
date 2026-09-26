<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Services\Healthcare\ManagerDataService;
use App\Services\Healthcare\TenantPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagerDataController extends Controller
{
    public function read(Request $request, ManagerDataService $service, string $resource): JsonResponse
    {
        return response()->json($service->read($request->user(), $resource));
    }

    public function execute(Request $request, ManagerDataService $service, string $operation): JsonResponse
    {
        // Tenant/plan ownership is never taken from the browser: only the
        // fields below are accepted and the tenant comes from the session.
        $input = match ($operation) {
            'plan' => $request->validate([
                'id' => ['required', 'integer'],
                'module' => ['required', Rule::in(TenantPlanService::TOGGLEABLE_MODULES)],
                'enabled' => ['required', 'boolean'],
            ]),
            'branding' => $request->validate([
                'nome' => ['required', 'string', 'max:255'],
                'cor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'saudacao' => ['required', 'string', 'max:255'],
            ]),
            default => abort(404),
        };

        return response()->json($service->execute($request->user(), $operation, $input));
    }
}
