<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $suffix = '.'.config('healthcare.tenant_base_domain');
        $host = $request->getHost();
        abort_unless(str_ends_with($host, $suffix), 404);

        $subdomain = substr($host, 0, -strlen($suffix));
        $tenant = Tenant::where('subdomain', $subdomain)->first();
        abort_unless($tenant, 404);

        $request->attributes->set('tenant', $tenant);
        app()->instance(Tenant::class, $tenant);

        return $next($request);
    }
}
