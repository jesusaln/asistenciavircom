<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Health check endpoint para monitoreo del sistema.
     * Accesible en: /health
     */
    public function __invoke()
    {
        $checks = [];
        $healthy = true;

        // 1. Verificar conexión a base de datos
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'ok',
                'message' => 'Conexión a PostgreSQL exitosa'
            ];
        } catch (\Exception $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'error',
                'message' => 'Error de conexión: ' . $e->getMessage()
            ];
            Log::error('Health Check - Database Error: ' . $e->getMessage());
        }

        // 2. Verificar storage (escritura/lectura)
        try {
            $testFile = 'health_check_test_' . time() . '.txt';
            Storage::disk('local')->put($testFile, 'test');
            $content = Storage::disk('local')->get($testFile);
            Storage::disk('local')->delete($testFile);

            if ($content === 'test') {
                $checks['storage'] = [
                    'status' => 'ok',
                    'message' => 'Storage funcionando correctamente'
                ];
            } else {
                throw new \Exception('Contenido no coincide');
            }
        } catch (\Exception $e) {
            $healthy = false;
            $checks['storage'] = [
                'status' => 'error',
                'message' => 'Error de storage: ' . $e->getMessage()
            ];
            Log::error('Health Check - Storage Error: ' . $e->getMessage());
        }

        // 3. Verificar caché
        try {
            $cacheKey = 'health_check_test_' . time();
            Cache::put($cacheKey, 'test', 10);
            $cached = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cached === 'test') {
                $checks['cache'] = [
                    'status' => 'ok',
                    'message' => 'Caché funcionando correctamente'
                ];
            } else {
                throw new \Exception('Valor cacheado no coincide');
            }
        } catch (\Exception $e) {
            // Caché es opcional, no marcamos como unhealthy
            $checks['cache'] = [
                'status' => 'warning',
                'message' => 'Caché no disponible: ' . $e->getMessage()
            ];
        }

        // 4. Verificar espacio en disco
        try {
            $freeSpace = disk_free_space(storage_path());
            $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);

            if ($freeSpaceGB > 1) {
                $checks['disk'] = [
                    'status' => 'ok',
                    'message' => "Espacio disponible: {$freeSpaceGB} GB"
                ];
            } else {
                $healthy = false;
                $checks['disk'] = [
                    'status' => 'warning',
                    'message' => "Espacio bajo: {$freeSpaceGB} GB"
                ];
            }
        } catch (\Exception $e) {
            $checks['disk'] = [
                'status' => 'unknown',
                'message' => 'No se pudo verificar espacio en disco'
            ];
        }

        // 5. Información del sistema
        $checks['app'] = [
            'status' => 'ok',
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $response = [
            'healthy' => $healthy,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks
        ];

        return response()->json($response, $healthy ? 200 : 503);
    }
}
