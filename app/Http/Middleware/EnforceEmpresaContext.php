<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\Auth;

class EnforceEmpresaContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }



        // Rutas excluidas
        $excludedRoutes = [
            'setup',
            'setup/*',
            'logout',
            'user/profile',
            'livewire/*',
            'sanctum/*',
            'empresas',      // Whitelist para crear empresa
            'empresas/*',    // Whitelist para guardar empresa
            'notifications/unread-count', // Ajax global
            'empresa/configuracion/api',  // Ajax global
            'portal',                     // Portal de clientes
            'portal/*',                   // Rutas del portal
        ];

        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // Usuario Super Admin puede saltarse esto si es necesario (opcional)
        // if (Auth::user()->hasRole('Super Admin')) { return $next($request); }

        // Intentar resolver la empresa
        $empresaId = EmpresaResolver::resolveId();

        if (!$empresaId) {
            \Illuminate\Support\Facades\Log::info('Middleware 403: No empresaId resolved for user: ' . Auth::id() . ' at path: ' . $request->path());
            // Si hay usuario pero no hay empresa, es un estado inconsistente o usuario sin asignar
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No se ha detectado un contexto de empresa válido para este usuario.'], 403);
            }

            // Redirigir a la pantalla de crear su primera empresa
            return redirect()->route('empresas.index')->with('warning', 'Debe crear o seleccionar una empresa para continuar.');

            // abort(403, 'Acceso Denegado: Su usuario no tiene una empresa asignada o el contexto no pudo ser resuelto.');
        }

        return $next($request);
    }
}
