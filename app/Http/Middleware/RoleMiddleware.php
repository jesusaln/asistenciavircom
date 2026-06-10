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
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Verifica si el usuario tiene alguno de los roles permitidos o es super-admin
        $user = Auth::user();

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        foreach ($roles as $roleGroup) {
            $individualRoles = explode('|', $roleGroup);
            foreach ($individualRoles as $role) {
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }
        }

        // Si no tiene ningún rol permitido, aborta o redirige
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Forbidden. No tienes permiso para acceder a este recurso.'], 403);
        }

        abort(403, 'No tienes permiso para acceder a esta página.');
    }
}
