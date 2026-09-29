<?php

use App\Core\Module\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventDuringImpersonation;
use App\Http\Middleware\ResolveTenantFromHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResolveTenantFromHost::class,
        ]);

        $middleware->web(append: [
            EnsureTenantActive::class,
            EnsureUserIsActive::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->routeIs('shop.account.*')
            ? route('shop.account.login')
            : route('login'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->routeIs('shop.account.*')
            ? route('shop.account.index')
            : route('dashboard'));

        $middleware->alias([
            'module' => EnsureModuleEnabled::class,
            'platform' => EnsurePlatformAdmin::class,
            'permission' => EnsureRoutePermission::class,
            'not-impersonating' => PreventDuringImpersonation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
