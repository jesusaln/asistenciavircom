<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class EnsureSystemInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Excluir rutas de debug, assets, api o sanctum para no interferir, o si estamos en local/testing
        if ($request->is('_debugbar/*') || $request->is('sanctum/*') || $request->is('api/*') || app()->environment('testing') || app()->environment('local')) {
            return $next($request);
        }

        // Verificar si existe algún usuario registrado en el sistema
        $isInstalled = false;
        try {
            $isInstalled = \App\Models\User::withoutGlobalScope('empresa')->exists();
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        // Si NO hay usuarios registrados
        if (!$isInstalled) {
            // Si ya estamos en la ruta de registro o api, permitir el paso
            if ($request->is('register') || $request->is('api/*') || $request->is('sanctum/*')) {
                return $next($request);
            }

            // Redirigir a la pantalla de registro
            if (Route::has('register')) {
                return redirect()->route('register');
            }
        }

        // Si SÍ está instalado
        if ($isInstalled) {
            // Si intenta acceder a setup, bloquear y mandar al login o panel
            if ($request->routeIs('setup.*')) {
                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
