<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class MonitoringService
{
    protected array $alerts = [];

    public function check(): array
    {
        $this->alerts = [];

        $reverbOk = $this->checkReverb();
        $redisOk = $this->checkRedis();
        $dbOk = $this->checkDatabase();

        $failedJobs = $this->countFailedJobs();
        $pendingJobs = $this->countPendingJobs();
        $deadLetterJobs = DB::table('failed_jobs')->count();

        $memoryUsed = $this->getMemoryUsage();
        $diskUsed = $this->getDiskUsage();

        // Alertas automáticas
        if ($failedJobs > 0) {
            $this->alerts[] = "{$failedJobs} jobs fallidos detectados en Redis";
        }
        if ($pendingJobs > 100) {
            $this->alerts[] = "{$pendingJobs} jobs pendientes en cola - posible atasco";
        }
        if (!$reverbOk) {
            $this->alerts[] = "Reverb no responde - reinicio recomendado";
        }
        if (!$redisOk) {
            $this->alerts[] = "Redis no responde - CRÍTICO";
        }
        if (!$dbOk) {
            $this->alerts[] = "Base de datos no responde - CRÍTICO";
        }

        // WhatsApp stats
        $whatsappStats = $this->getWhatsAppStats();

        // Citas hoy
        $citasHoy = $this->getCitasHoy();

        // Usuarios activos
        $usuariosActivos = $this->getActiveUsers();

        // DB info
        $dbInfo = $this->getDbInfo();

        return [
            'timestamp' => now()->toDateTimeString(),
            'failed_jobs' => $failedJobs,
            'pending_jobs' => $pendingJobs,
            'dead_letter_jobs' => $deadLetterJobs,
            'reverb_ok' => $reverbOk,
            'redis_ok' => $redisOk,
            'db_ok' => $dbOk,
            'memory_used' => $memoryUsed,
            'disk_used' => $diskUsed,
            'whatsapp' => $whatsappStats,
            'citas_hoy' => $citasHoy,
            'usuarios_activos' => $usuariosActivos,
            'db_info' => $dbInfo,
            'alerts' => $this->alerts,
        ];
    }

    protected function getWhatsAppStats(): array
    {
        try {
            return [
                'chats_24h' => DB::table('whats_app_chats')->where('created_at', '>=', now()->subDay())->count(),
                'inbound_24h' => DB::table('whats_app_chats')->where('direction', 'inbound')->where('created_at', '>=', now()->subDay())->count(),
                'outbound_24h' => DB::table('whats_app_chats')->where('direction', 'outbound')->where('created_at', '>=', now()->subDay())->count(),
                'feedback_positivo_7d' => DB::table('chatbot_feedback')->where('sentiment', 'positive')->where('created_at', '>=', now()->subDays(7))->count(),
                'feedback_negativo_7d' => DB::table('chatbot_feedback')->where('sentiment', 'negative')->where('created_at', '>=', now()->subDays(7))->count(),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getCitasHoy(): array
    {
        try {
            $estados = DB::table('citas')
                ->select('estado', DB::raw('count(*) as total'))
                ->whereDate('fecha_hora', today())
                ->groupBy('estado')
                ->pluck('total', 'estado')
                ->toArray();

            return [
                'total' => array_sum($estados),
                'programadas' => ($estados['programado'] ?? 0) + ($estados['programada'] ?? 0),
                'completadas' => ($estados['completado'] ?? 0) + ($estados['completada'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getActiveUsers(): int
    {
        try {
            // Usuarios activos en las últimas 2 horas
            return DB::table('users')
                ->where('last_active_at', '>=', now()->subHours(2))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function getDbInfo(): array
    {
        try {
            $sizeResult = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            return [
                'size' => $sizeResult[0]->size ?? 'N/A',
                'connection' => DB::connection()->getName(),
            ];
        } catch (\Throwable $e) {
            return ['size' => 'N/A', 'connection' => DB::connection()->getName()];
        }
    }

    protected function countFailedJobs(): int
    {
        try {
            $connection = config('queue.default');
            return Cache::store('redis')->connection()->zcard("queues:{$connection}:failed") ?: 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function countPendingJobs(): int
    {
        try {
            $pending = 0;
            foreach (['default', 'low', 'high'] as $queue) {
                $pending += Redis::connection()->llen("queues:{$queue}") ?: 0;
            }
            return $pending;
        } catch (\Throwable $e) {
            return -1;
        }
    }

    protected function checkReverb(): bool
    {
        try {
            $response = Http::timeout(3)->send('GET', 'http://climasdeldesierto-reverb-1:8080/');
            return $response->status() !== 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function getMemoryUsage(): string
    {
        return round(memory_get_usage(true) / 1024 / 1024, 1) . ' MB (PHP)';
    }

    protected function getDiskUsage(): string
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        $used = round((1 - $free / $total) * 100, 1);
        return "{$used}%";
    }
}
