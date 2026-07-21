<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Support\EmpresaResolver;

class ResolveTenantFromDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-Tenant')) {
            return $next($request);
        }

        $host = $request->getHost();
        $host = preg_replace('/^www\./', '', $host);

        if ($this->isSystemOrConsoleRequest($host)) {
            return $next($request);
        }

        if ($request->is('admin-central*') || $request->is('central/*')) {
            return $next($request);
        }

        $tenant = Tenant::resolveFromDomain($host);

        if (!$tenant) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tenant no encontrado para este dominio.'], 404);
            }

            if (app()->environment('local')) {
                $tenant = Tenant::first();
            }
        }

        if ($tenant) {
            $this->switchToTenant($tenant);
        }

        return $next($request);
    }

    private function isSystemOrConsoleRequest(string $host): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        return in_array($host, ['localhost', '127.0.0.1', 'cdd_system']);
    }

    private function switchToTenant(Tenant $tenant): void
    {
        $tenant->switchToThis();
    }
}
