<?php

namespace App\Http\Middleware;

use App\Support\EmpresaResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveEmpresaFromHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si ya hay sesión autenticada, el resolver normal por usuario toma prioridad.
        if (Auth::check()) {
            return $next($request);
        }

        $host = strtolower((string) $request->getHost());
        $host = preg_replace('/:\\d+$/', '', $host) ?: '';

        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return $next($request);
        }

        try {
            $mapping = Cache::remember('empresa_host_mapping_v2', 120, function () {
                $columns = ['dominio_principal', 'dominio_secundario', 'sitio_web', 'app_url'];

                try {
                    $rows = DB::table('empresa_configuracion')
                        ->select(array_merge(['empresa_id'], $columns))
                        ->whereNotNull('empresa_id')
                        ->get();
                    $colsToCheck = $columns;
                } catch (\Throwable $e) {
                    // Fallback only if table/columns don't exist
                    if (!Schema::hasTable('empresa_configuracion')) {
                        return ['exact' => [], 'wildcards' => []];
                    }

                    $existingColumns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('empresa_configuracion', $column)));
                    if (empty($existingColumns)) {
                        return ['exact' => [], 'wildcards' => []];
                    }

                    $rows = DB::table('empresa_configuracion')
                        ->select(array_merge(['empresa_id'], $existingColumns))
                        ->whereNotNull('empresa_id')
                        ->get();
                    $colsToCheck = $existingColumns;
                }

                $exact = [];
                $wildcards = [];

                foreach ($rows as $row) {
                    foreach ($colsToCheck as $column) {
                        $value = (string) ($row->{$column} ?? '');
                        foreach ($this->extractHosts($value) as $candidateHost) {
                            if (str_starts_with($candidateHost, '*.')) {
                                $wildcards[] = [
                                    'suffix' => substr($candidateHost, 2),
                                    'empresa_id' => (int) $row->empresa_id,
                                ];
                                continue;
                            }

                            $exact[$candidateHost] = (int) $row->empresa_id;
                        }
                    }
                }

                return ['exact' => $exact, 'wildcards' => $wildcards];
            });
        } catch (\Throwable $e) {
            $mapping = ['exact' => [], 'wildcards' => []];
        }

        if (isset($mapping['exact'][$host])) {
            EmpresaResolver::setContext((int) $mapping['exact'][$host]);
            return $next($request);
        }

        foreach (($mapping['wildcards'] ?? []) as $wildcard) {
            $suffix = $wildcard['suffix'] ?? '';
            $empresaId = (int) ($wildcard['empresa_id'] ?? 0);

            if ($suffix !== '' && $empresaId > 0 && str_ends_with($host, '.' . $suffix)) {
                EmpresaResolver::setContext($empresaId);
                break;
            }
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function extractHosts(string $rawValue): array
    {
        $parts = preg_split('/[\\s,;]+/', strtolower(trim($rawValue))) ?: [];
        $hosts = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (str_starts_with($part, '*.')) {
                $suffix = $this->normalizeHost(substr($part, 2));
                if ($suffix !== '') {
                    $hosts[] = '*.' . $suffix;
                }
                continue;
            }

            $host = $this->normalizeHost($part);
            if ($host !== '') {
                $hosts[] = $host;
                if (str_starts_with($host, 'www.')) {
                    $hosts[] = substr($host, 4);
                }
            }
        }

        return array_values(array_unique($hosts));
    }

    private function normalizeHost(string $value): string
    {
        $candidate = trim($value);
        if ($candidate === '') {
            return '';
        }

        $candidate = str_replace(['https://', 'http://'], '', $candidate);
        $candidate = explode('/', $candidate)[0] ?? '';
        $candidate = preg_replace('/:\\d+$/', '', $candidate) ?: '';

        if ($candidate === '' || str_contains($candidate, ' ')) {
            return '';
        }

        return trim($candidate, '.');
    }
}
