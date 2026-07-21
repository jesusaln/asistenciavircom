<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\EmpresaResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromHeader
{
    private array $except = [
        'api/login',
        'api/logout',
        'api/auth/central-login',
        'api/auth/select-tenant',
        'api/auth/central-register',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->except as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        $tenantSlug = $request->header('X-Tenant');

        if ($tenantSlug) {
            $tenant = Tenant::where('slug', $tenantSlug)->first();

            if (!$tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant no encontrado.'
                ], 404);
            }

            if (!$tenant->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant inactivo o expirado.'
                ], 403);
            }

            $tenant->switchToThis();
            \Illuminate\Support\Facades\Cache::forget('empresa_fallback_id');

            // LEGÍTIMO cross-tenant: este middleware RESUELVE el tenant
            // desde el header X-Tenant, así que la búsqueda en todas las
            // empresas_configuracion es por diseño.
            $empresaId = DB::table('empresa_configuracion')
                ->where('dominio_principal', 'ilike', "%{$tenantSlug}%")
                ->orWhere('dominio_secundario', 'ilike', "%{$tenantSlug}%")
                ->value('id');

            if ($empresaId) {
                \App\Support\EmpresaResolver::setContext((int) $empresaId);
            }
        }

        return $next($request);
    }
}
