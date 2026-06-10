<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilterByEmpresa
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Si el usuario no está autenticado, continuar sin filtrar
        if (!$user) {
            return $next($request);
        }

        // Obtener el ID de la empresa del usuario
        $empresaId = $user->empresa_id;

        // Si el usuario no tiene una empresa asociada, continuar sin filtrar
        if (!$empresaId) {
            return $next($request);
        }

        // Aplicar el filtro de empresa a todos los modelos que lo requieran
        // Esto se puede hacer mediante un trait o un scope global
        // Por ahora, simplemente pasamos el ID de la empresa a la solicitud
        $request->merge(['empresa_id' => $empresaId]);

        return $next($request);
    }
}