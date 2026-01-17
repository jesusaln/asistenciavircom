<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnsureSystemInstalled::class,
            \App\Http\Middleware\EnforceEmpresaContext::class,
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'portal.debt' => \App\Http\Middleware\CheckClientDebt::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('portal/*') || $request->is('portal')) {
                return route('portal.login');
            }
            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('client')->check()) {
                return route('portal.dashboard');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
