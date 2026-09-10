<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHealthcareDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment(['local', 'testing']) && config('healthcare.demo_enabled'), 404);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
