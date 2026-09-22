<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant isolation is a security boundary (AGENTS.md section 9): the
 * authenticated user must belong to the tenant resolved from the request
 * subdomain, never the other way around.
 */
class EnsureBelongsToTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('tenant');
        $user = $request->user();
        abort_unless($tenant && $user && $user->tenant_id === $tenant->id, 403, 'Esta conta não pertence a este ambiente.');

        return $next($request);
    }
}
