<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Closure;
use Illuminate\Http\Request;

class CustomVerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'prestamos/calcular-pagos',
        'sanctum/csrf-cookie',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Para rutas excluidas específicamente, saltar verificación CSRF
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        // Para otras rutas, usar la verificación estándar de Laravel
        return parent::handle($request, $next);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function inExceptArray($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if CSRF verification should be skipped for this request.
     */
    protected function shouldSkipCsrfVerification(Request $request): bool
    {
        $path = $request->path();

        // Excluir rutas de autenticación
        if (str_contains($path, 'login') ||
            str_contains($path, 'logout') ||
            str_contains($path, 'register') ||
            str_contains($path, 'password') ||
            str_contains($path, 'sanctum/csrf-cookie')) {
            return true;
        }

        // Excluir rutas de API
        if (str_contains($path, 'api/')) {
            return true;
        }

        // Excluir rutas específicas que pueden causar problemas
        $excludedRoutes = [
            'login',
            'logout',
            'register',
            'password/reset',
            'password/email',
            'password/confirm',
            'sanctum/csrf-cookie',
            'prestamos/calcular-pagos' // Excluir cálculo de pagos para evitar problemas CSRF
        ];

        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if this request requires CSRF verification.
     */
    protected function requiresCsrfCheck(Request $request): bool
    {
        $unsafeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        return in_array($request->method(), $unsafeMethods) &&
               !$this->shouldSkipCsrfVerification($request);
    }
}
