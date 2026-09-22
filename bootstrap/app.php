<?php

use App\Http\Middleware\EnsureBelongsToTenant;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'tenant.member' => EnsureBelongsToTenant::class,
        ]);

        // Laravel reorders middleware by its own priority list regardless of
        // the order given to Route::middleware(), so tenant resolution must
        // be pinned explicitly around auth: an unknown subdomain must 404
        // before the auth redirect, and tenant membership must be checked
        // only once a user is authenticated.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: ResolveTenant::class,
        );
        $middleware->appendToPriorityList(
            after: AuthenticatesRequests::class,
            append: EnsureBelongsToTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
