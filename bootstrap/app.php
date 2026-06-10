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
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withProviders([
        // ... existing providers
        \App\Providers\DatabaseConnectionManager::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            "api/webhooks/*", 
            "trading/bulk-save-experience",
            "trading/*",
            "login"
        ]);

        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\TokenFromQuery::class,
        ], append: [
            \App\Http\Middleware\ForceWebAuthForApi::class,
        ]);

        $middleware->web(prepend: [
            \App\Http\Middleware\SwitchDatabaseConnection::class,
        ], append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            // \App\Http\Middleware\EnsureSystemInstalled::class,
            \App\Http\Middleware\EnforceEmpresaContext::class,
            // \App\Http\Middleware\VerifyLoginCode::class,
        ]);

        $middleware->priority([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\ForceWebAuthForApi::class,
            \App\Http\Middleware\SwitchDatabaseConnection::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        $middleware->encryptCookies(except: [
            'trusted_device',
            'selected_company',
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'portal.debt' => \App\Http\Middleware\CheckClientDebt::class,
            '2fa.required' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
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

            $user = Auth::user();

            if ($user && empty($user->empresa_id)) {
                return route('empresas.index');
            }

            return route('panel');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Log de emergencia para depurar errores en producción
                \Illuminate\Support\Facades\Log::error('API EXCEPTION: ' . $e->getMessage(), [
                    'class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $isDebug = config('app.debug');
                $status = 500;
                $message = null;
                
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                    $message = $e->getMessage();
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                    $message = 'Unauthenticated.';
                } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    $status = 403;
                    $message = 'This action is unauthorized.';
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $status = 404;
                    $message = 'Recurso no encontrado.';
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    return null; // Dejar que Laravel maneje validaciones
                }

                $message = $message ?: ($isDebug ? $e->getMessage() : 'Error interno del servidor.');
                
                // Sanitizar paths siempre, incluso en debug
                $basePath = base_path();
                $message = str_replace([$basePath, str_replace('/', '\\', $basePath)], '', $message);

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => $isDebug ? [
                        'type' => get_class($e),
                        'file' => str_replace($basePath, '', $e->getFile()),
                        'line' => $e->getLine(),
                    ] : null
                ], $status);
            }
        });
    })
->create();
