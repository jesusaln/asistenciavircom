<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SwitchDatabaseConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            // Priorizar cookie para persistencia tras logout, fallback a session
            $company = $request->cookie('selected_company') ?: Session::get('selected_company', 'climas');

            // 1. Validar si el usuario está autenticado y tiene permiso
            if (Auth::check()) {
                $user = Auth::user();
                
                // Super-admins se saltan todas las restricciones
                if (!$user->hasRole('super-admin')) {
                    if (!empty($user->empresas_acceso)) {
                        $allowed = explode(',', $user->empresas_acceso);
                        if (!in_array($company, $allowed)) {
                            // Si no tiene permiso para la seleccionada, fallback a la primera permitida o climas
                            $company = $allowed[0] ?? 'climas';
                        }
                    }
                }
            }

            // 2. Ejecutar el switch de conexión
            if ($company === 'vircom') {
                try {
                    Config::set('database.default', 'vircom');
                    DB::setDefaultConnection('vircom');
                    DB::purge('pgsql');
                    DB::purge('vircom');
                    DB::connection('vircom')->getPdo();
                    
                    \App\Support\EmpresaResolver::clearCache();
                    \App\Models\EmpresaConfiguracion::clearCache();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Middleware: FAILED switch to Vircom: " . $e->getMessage());
                    $company = 'climas';
                }
            }

            if ($company !== 'vircom' && config('database.default') !== 'pgsql') {
                Config::set('database.default', 'pgsql');
                DB::setDefaultConnection('pgsql');
                DB::purge('pgsql');
                \App\Support\EmpresaResolver::clearCache();
                \App\Models\EmpresaConfiguracion::clearCache();
            }
            
            // Guardar en sesión si cambió por restricción
            if ($company !== $request->cookie('selected_company')) {
                Session::put('selected_company', $company);
            }
        }

        return $next($request);
    }
}
