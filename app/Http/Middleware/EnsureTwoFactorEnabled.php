<?php

namespace App\Http\Middleware;

/**
 * EnsureTwoFactorEnabled Middleware
 *
 * Este middleware obliga a los usuarios con ciertos roles a tener
 * habilitada la autenticación de dos factores (2FA).
 */

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TEMPORALMENTE DESACTIVADO - Permitir acceso sin 2FA
        // TODO: Reactivar después de corregir el problema de persistencia de 2FA
        return $next($request);
    }
}
