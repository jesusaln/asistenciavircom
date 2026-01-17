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

        // Verificar si existe algún super-admin o al menos una empresa configurada
        $isInstalled = false;
        try {
            // Opción 1: Checar si hay super-admin (producción)
            if (\Schema::hasTable('roles')) {
                $isInstalled = User::role('super-admin')->exists();
            }
            // Opción 2: En desarrollo, si hay al menos una empresa configurada, permitir acceso
            if (!$isInstalled && \Schema::hasTable('empresa_configuracion')) {
                $isInstalled = \DB::table('empresa_configuracion')->exists();
            }
        } catch (\Exception $e) {
            // Si hay error de DB, intentar fallback a empresa_configuracion
            try {
                if (\Schema::hasTable('empresa_configuracion')) {
                    $isInstalled = \DB::table('empresa_configuracion')->exists();
                }
            } catch (\Exception $e2) {
                $isInstalled = false;
            }
        }

        // Si NO está instalado
        if (!$isInstalled) {
            // Si ya estamos en una ruta de setup, permitir
            if ($request->routeIs('setup.*')) {
                return $next($request);
            }
            // Verificar si la ruta setup.index existe antes de redirigir
            if (Route::has('setup.index')) {
                return redirect()->route('setup.index');
            }
            // Fallback: si no hay ruta de setup, permitir paso (dev mode)
            return $next($request);
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
