<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class DbConnectionMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor-connections
                            {--watch : Monitor connections continuously}
                            {--disconnect : Disconnect idle connections}
                            {--threshold=600 : Idle threshold in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitorea y gestiona conexiones de base de datos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('watch')) {
            return $this->watchMode();
        }

        return $this->showStatus();
    }

    /**
     * Muestra el estado actual de conexiones
     */
    protected function showStatus(): int
    {
        $this->info('🔌 Database Connection Monitor');
        $this->newLine();

        $connections = $this->getConnectionStatus();

        $this->info("Active connections: " . count($connections));
        $this->newLine();

        if (empty($connections)) {
            $this->info('✅ No active database connections');
            return Command::SUCCESS;
        }

        $headers = ['Connection', 'Status', 'Transaction', 'Idle (s)'];
        $rows = [];

        foreach ($connections as $name => $status) {
            $rows[] = [
                $name,
                $status['connected'] ? '🟢 Connected' : '🔴 Disconnected',
                $status['in_transaction'] ? '🔄 Yes' : '✅ No',
                $status['idle_seconds'] ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        // Opción para desconectar conexiones inactivas
        if ($this->confirm('Do you want to disconnect idle connections?')) {
            return $this->disconnectIdle();
        }

        return Command::SUCCESS;
    }

    /**
     * Modo de monitoreo continuo
     */
    protected function watchMode(): int
    {
        $this->info('👀 Watching database connections (Ctrl+C to exit)');
        $this->newLine();

        $threshold = (int) $this->option('threshold');
        $lastCount = 0;

        while (true) {
            $connections = $this->getConnectionStatus();
            $count = count($connections);
            $time = now()->format('H:i:s');

            // Mostrar cambio si hay
            if ($count !== $lastCount || $this->option('verbose')) {
                $status = $count > 0 ? "🔌 {$count} connections" : '✅ No connections';
                $this->info("[{$time}] {$status}");
            }

            // Alertar sobre conexiones inactivas largas
            foreach ($connections as $name => $info) {
                if (isset($info['idle_seconds']) && $info['idle_seconds'] > $threshold) {
                    $this->warn("[{$time}] ⚠️  Connection '{$name}' idle for {$info['idle_seconds']}s");
                }
            }

            $lastCount = $count;
            sleep(5);
        }
    }

    /**
     * Desconecta conexiones inactivas
     */
    protected function disconnectIdle(): int
    {
        $threshold = (int) $this->option('threshold');
        $this->info("Disconnecting connections idle for more than {$threshold} seconds...");

        $disconnected = 0;

        foreach (DB::getConnections() as $name => $connection) {
            try {
                // Verificar si la conexión está inactiva
                if ($connection->getPdo()) {
                    // Forzar desconexión
                    DB::purge($name);
                    $disconnected++;
                    $this->line("   ✅ Disconnected: {$name}");
                }
            } catch (\Throwable $e) {
                $this->warn("   ❌ Failed to disconnect {$name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Total disconnected: {$disconnected}");

        return Command::SUCCESS;
    }

    /**
     * Obtiene el estado de todas las conexiones
     */
    protected function getConnectionStatus(): array
    {
        $status = [];

        foreach (DB::getConnections() as $name => $connection) {
            try {
                // Verificar si la conexión está activa
                $pdo = $connection->getPdo();

                // Obtener información de la conexión PostgreSQL
                $idleTime = null;
                if ($connection->getDriverName() === 'pgsql') {
                    try {
                        $result = $connection->select("
                            SELECT COALESCE(EXTRACT(EPOCH FROM (now() - state_change)), 0) as idle_seconds
                            FROM pg_stat_activity
                            WHERE pid = pg_backend_pid()
                        ");
                        $idleTime = !empty($result) ? (int) $result[0]->idle_seconds : null;
                    } catch (\Throwable $e) {
                        // Si falla la query, usar tiempo actual
                        $idleTime = null;
                    }
                }

                $status[$name] = [
                    'connected' => $pdo !== null,
                    'in_transaction' => $connection->transactionLevel() > 0,
                    'idle_seconds' => $idleTime,
                ];
            } catch (\Throwable $e) {
                $status[$name] = [
                    'connected' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $status;
    }

    /**
     * Obtiene estadísticas de PostgreSQL
     */
    protected function getPgStats(): array
    {
        try {
            $stats = DB::select("
                SELECT
                    state,
                    COUNT(*) as count,
                    MAX(EXTRACT(EPOCH FROM (now() - state_change))) as max_idle_seconds
                FROM pg_stat_activity
                WHERE usename = current_user
                GROUP BY state
            ");

            return [
                'active' => collect($stats)->firstWhere('state', 'active')->count ?? 0,
                'idle' => collect($stats)->firstWhere('state', 'idle')->count ?? 0,
                'max_idle' => collect($stats)->firstWhere('state', 'idle')->max_idle_seconds ?? 0,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
