<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

class MonitorSystemHealth extends Command
{
    protected $signature = 'monitor:health {--alert : Enviar alerta si hay problemas}';
    protected $description = 'Monitorea salud del sistema: queues, memoria, Reverb, errores';

    public function handle(): int
    {
        $monitor = app(MonitoringService::class);
        $status = $monitor->check();

        $this->info('=== CDD System Health ===');
        $this->line("Timestamp: {$status['timestamp']}");

        $this->line("\n📦 Queues:");
        $this->line("  Failed: {$status['failed_jobs']}");
        $this->line("  Pending (default): {$status['pending_jobs']}");
        $this->line("  Dead Letter: {$status['dead_letter_jobs']}");

        $this->line("\n🔌 Servicios:");
        $this->line("  Reverb: " . ($status['reverb_ok'] ? '✅ OK' : '❌ DOWN'));
        $this->line("  Redis: " . ($status['redis_ok'] ? '✅ OK' : '❌ DOWN'));
        $this->line("  DB: " . ($status['db_ok'] ? '✅ OK' : '❌ DOWN'));

        $this->line("\n💾 Recursos:");
        $this->line("  Memoria usada: {$status['memory_used']}");
        $this->line("  Disco usado: {$status['disk_used']}");

        $alerts = $status['alerts'] ?? [];
        if (!empty($alerts)) {
            $this->line("\n🚨 ALERTAS:");
            foreach ($alerts as $alert) {
                $this->error("  • {$alert}");
            }
            return 1;
        }

        $this->line("\n✅ Sin alertas.");
        return 0;
    }
}
