<?php

namespace App\Jobs\Middleware;

use App\Support\EmpresaResolver;
use Closure;

class EnforceTenantContext
{
    /**
     * Process the queued job.
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, Closure $next)
    {
        $empresaId = null;

        // 1. Intentar obtener empresa_id directamente del job
        if (isset($job->empresa_id)) {
            $empresaId = $job->empresa_id;
        }
        // 2. Intentar obtener del user asociado
        elseif (isset($job->user) && isset($job->user->empresa_id)) {
            $empresaId = $job->user->empresa_id;
        }

        if ($empresaId) {
            EmpresaResolver::setContext($empresaId);
        }

        try {
            return $next($job);
        } finally {
            // Limpiar contexto después de ejecución para no contaminar daemon workers
            EmpresaResolver::clearCache();
        }
    }
}
