<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleSessionExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la sesión ha expirado
        if ($this->isSessionExpired($request)) {
            // Cerrar sesión si está autenticado
            if (Auth::check()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Redirigir al login con mensaje
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session expired'], 401);
            } else {
                return redirect()->route('login')->with('error', 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.');
            }
        }

        return $next($request);
    }

    /**
     * Check if the current session has expired.
     */
    protected function isSessionExpired(Request $request): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // Verificar si hay usuario autenticado pero la sesión está vacía
        if (!$request->session()->has('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d')) {
            return true;
        }

        // Verificar tiempo de vida de la sesión
        $lastActivity = $request->session()->get('last_activity');
        if (!$lastActivity) {
            return false;
        }
        $lifetime = config('session.lifetime', 120) * 60; // en segundos

        if (time() - $lastActivity > $lifetime) {
            return true;
        }

        return false;
    }
}
