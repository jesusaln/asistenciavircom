<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\EmpresaResolver;

class EnforceEmpresaContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }
        $userId = auth()->id();

        // Rutas excluidas
        $excludedRoutes = [
            'login',
            'register',
            'forgot-password',
            'reset-password/*',
            'setup',
            'setup/*',
            'logout',
            'user/profile',
            'livewire/*',
            'sanctum/*',
            'empresas*',     // Whitelist para crear y guardar empresa (evita loops)
            'notifications/unread-count', // Ajax global
            'empresa/configuracion/api',  // Ajax global
            'portal',                     // Portal de clientes
            'portal/*',                   // Rutas del portal
        ];

        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                \Illuminate\Support\Facades\Log::info('EnforceEmpresaContext: Path is EXCLUDED: ' . $request->path() . ' matched rule: ' . $route);
                return $next($request);
            }
        }

        // Usuario Super Admin puede saltarse esto si es necesario (opcional)
        // if (Auth::user()->hasRole('Super Admin')) { return $next($request); }

        // Intentar resolver la empresa
        $empresaId = EmpresaResolver::resolveId();

        if (!$empresaId) {
            \Illuminate\Support\Facades\Log::info('EnforceEmpresaContext REDIRECTING to empresas.index from path: ' . $request->path() . ' (resolved userId: ' . $userId . ')');
            // Si hay usuario pero no hay empresa, es un estado inconsistente o usuario sin asignar
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No se ha detectado un contexto de empresa válido para este usuario.'], 403);
            }

            // Redirigir a la pantalla de crear su primera empresa
            return redirect()->route('empresas.index')->with('warning', 'Debe crear o seleccionar una empresa para continuar.');
        }

        $request->attributes->set('empresa_id', $empresaId);
        EmpresaResolver::setContext($empresaId);

        return $next($request);
    }
}
