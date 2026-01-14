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
        // Excluir rutas de debug, assets, api o sanctum para no interferir
        if ($request->is('_debugbar/*') || $request->is('sanctum/*') || $request->is('api/*') || app()->environment('testing')) {
            return $next($request);
        }

        // Verificar si existe algún super-admin (indicador de instalación completa)
        $isInstalled = false;
        try {
            if (\Schema::hasTable('roles')) {
                $isInstalled = User::role('super-admin')->exists();
            }
        } catch (\Exception $e) {
            // Si el rol no existe o hay error de DB, asumimos que no está instalado
            $isInstalled = false;
        }

        // Si NO está instalado
        if (!$isInstalled) {
            // Si ya estamos en una ruta de setup, permitir
            if ($request->routeIs('setup.*')) {
                return $next($request);
            }
            // Redirigir a setup
            return redirect()->route('setup.index');
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
