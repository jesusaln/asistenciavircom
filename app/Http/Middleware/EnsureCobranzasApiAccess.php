<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza /api/cobranzas/* igual que el menú móvil: permiso view cuentas_por_cobrar
 * (incluye permisos heredados por rol) o roles típicos admin/cobranza/super-admin.
 *
 * hasAnyRole(['admin']) falla si en BD el rol se llama "Administrador"; por eso se
 * normaliza el nombre (espacios/guiones, mayúsculas) como en la app Ionic.
 */
class EnsureCobranzasApiAccess
{
    private const COBRANZAS_ROLE_SLUGS = [
        'super-admin',
        'superadmin',
        'admin',
        'administrador',
        'cobranza',
        'cobranzas',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Incluye permisos otorgados vía rol (Spatie + Gate)
        if ($user->can('view cuentas_por_cobrar')) {
            return $next($request);
        }

        if ($this->userHasCobranzasRoleByName($user)) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a cobranzas.');
    }

    private function userHasCobranzasRoleByName(object $user): bool
    {
        foreach ($user->getRoleNames() as $name) {
            $slug = mb_strtolower(preg_replace('/[\s_]+/u', '-', trim((string) $name)), 'UTF-8');
            if (in_array($slug, self::COBRANZAS_ROLE_SLUGS, true)) {
                return true;
            }
        }

        return false;
    }
}
