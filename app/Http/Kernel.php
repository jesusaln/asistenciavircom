<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        // \App\Http\Middleware\HandleInertiaRequests::class, // Eliminado de global, ya está en web group en bootstrap/app.php
        // \App\Http\Middleware\DebugCorsMiddleware::class, // Middleware para debuggear CORS
        // eliminado \App\Http\Middleware\CorsMiddleware::class,
        // \App\Http\Middleware\AlignSequencesMiddleware::class, // Alinea secuencias en PostgreSQL
    ];

    /**
     * The application's middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\HandleSessionExpiration::class,
            \App\Http\Middleware\CustomVerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class, // Necesario para Inertia
            \App\Http\Middleware\CleanJsonResponse::class, // Limpia caracteres UTF-8 inválidos
            \App\Http\Middleware\EnsureSystemInstalled::class, // Verificar instalación
            \App\Http\Middleware\EnforceEmpresaContext::class, // 🛡️ Blindaje Multi-empresa
        ],

        'api' => [
            \App\Http\Middleware\EncryptCookies::class, // Necesario para descifrar la sesión
            \Illuminate\Session\Middleware\StartSession::class, // FORZAR inicio de sesión para evitar 401
            \App\Http\Middleware\ForceWebAuthForApi::class, // PUENTE: usar sesión web para API
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\CorsMiddleware::class, // Añadido aquí para que se aplique solo a las rutas API
            \App\Http\Middleware\EnforceEmpresaContext::class, // 🛡️ Blindaje Multi-empresa (Añadido para API)
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases can be used in route definitions instead of full class names.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \App\Http\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class, // Registramos tu middleware aquí
        '2fa.required' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
    ];

    /**
     * Route middleware (backward compat for older Laravel APIs expecting $routeMiddleware).
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];
}
