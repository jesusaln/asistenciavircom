<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Verifica si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login'); // Redirige al login si no está autenticado
        }

        // Verifica si el usuario tiene alguno de los roles permitidos o es super-admin
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request); // Permite el acceso si coincide algún rol
            }
        }

        // Si no tiene ningún rol permitido, aborta o redirige
        abort(403, 'No tienes permiso para acceder a esta página.');
        // Alternativa: return redirect()->route('home')->with('error', 'Acceso denegado.');
    }
}
