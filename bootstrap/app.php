<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            // Estandarización de Errores para API
            if ($request->expectsJson() || $request->is('api/*')) {
                // Manejo especial para errores de validación
                if ($exception instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => $exception->getMessage(),
                        'errors' => $exception->errors(),
                        'status' => 422,
                    ], 422);
                }

                // Obtener el mensaje real si no es 500, de lo contrario un mensaje genérico.
                $status = $response->getStatusCode();
                $message = $exception->getMessage() ?: 'Ha ocurrido un error inesperado.';
                
                if ($status === 500 && !app()->environment(['local', 'testing'])) {
                    $message = 'Error interno del servidor. Soporte técnico ha sido notificado.';
                }
                
                if ($status === 404 && empty($exception->getMessage())) {
                    $message = 'El recurso solicitado no fue encontrado.';
                }

                return response()->json([
                    'success' => false,
                    'error' => $message,
                    'status' => $status,
                ], $status);
            }

            // Estandarización de Errores Web (Inertia/Vue)
            $status = $response->getStatusCode();
            $allowedStatuses = [500, 503, 404, 403, 401, 419, 429];

            if (in_array($status, $allowedStatuses)) {
                // En producción/staging mostramos siempre la vista bonita.
                // En local, mostramos la vista bonita para 404/403, pero permitimos ver el stack trace de Ignition para errores 500.
                if (!app()->environment(['local', 'testing']) || in_array($status, [404, 403, 401, 419])) {
                    return Inertia::render('Errors/Error', ['status' => $status])
                        ->toResponse($request)
                        ->setStatusCode($status);
                }
            }

            return $response;
        });
    })->create();
