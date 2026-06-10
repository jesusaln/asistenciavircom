<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;

/**
 * DatabaseConnectionManager Provider
 *
 * Gestiona las conexiones de base de datos para prevenir conexiones perpetuas.
 * Monitorea y libera conexiones inactivas después de requests largos.
 *
 * Soluciona Error #42: Conexiones PostgreSQL activas por más de 18 horas.
 */
class DatabaseConnectionManager extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('db.connection-manager', function ($app) {
            return new class {
                /**
                 * Tiempo máximo de inactividad (en segundos)
                 */
                protected int $maxIdleTime = 600; // 10 minutos

                /**
                 * Tiempo máximo de vida de una conexión (en segundos)
                 */
                protected int $maxLifetime = 1800; // 30 minutos

                /**
                 * Registro de conexiones activas
                 */
                protected array $activeConnections = [];

                /**
                 * Inicializa el monitoreo de conexiones
                 */
                public function initialize(): void
                {
                    // Escuchar eventos de queries para trackear actividad
                    DB::listen(function (QueryExecuted $query) {
                        $this->recordActivity();
                    });

                    // Registrar cleanup en shutdown
                    register_shutdown_function(function () {
                        $this->disconnectAll();
                    });
                }

                /**
                 * Registra actividad en la conexión actual
                 */
                public function recordActivity(): void
                {
                    $connectionName = DB::getDefaultConnection();
                    $this->activeConnections[$connectionName] = now()->timestamp;
                }

                /**
                 * Desconecta todas las conexiones activas
                 */
                public function disconnectAll(): void
                {
                    foreach (DB::getConnections() as $name => $connection) {
                        $this->disconnect($name);
                    }
                    $this->activeConnections = [];
                }

                /**
                 * Desconecta una conexión específica
                 */
                public function disconnect(string $connectionName): void
                {
                    try {
                        DB::purge($connectionName);
                        unset($this->activeConnections[$connectionName]);
                    } catch (\Throwable $e) {
                        Log::warning("Failed to disconnect DB connection {$connectionName}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                /**
                 * Desconecta conexiones inactivas
                 */
                public function disconnectIdleConnections(): int
                {
                    $disconnected = 0;
                    $now = now()->timestamp;

                    foreach ($this->activeConnections as $connectionName => $lastActivity) {
                        if (($now - $lastActivity) > $this->maxIdleTime) {
                            $this->disconnect($connectionName);
                            $disconnected++;
                        }
                    }

                    if ($disconnected > 0) {
                        Log::info("Disconnected {$disconnected} idle DB connections");
                    }

                    return $disconnected;
                }

                /**
                 * Verifica estado de conexiones
                 */
                public function getConnectionStatus(): array
                {
                    $status = [];

                    foreach (DB::getConnections() as $name => $connection) {
                        try {
                            $status[$name] = [
                                'connected' => $connection->getPdo() !== null,
                                'in_transaction' => $connection->transactionLevel() > 0,
                                'last_activity' => $this->activeConnections[$name] ?? null,
                                'idle_seconds' => isset($this->activeConnections[$name])
                                    ? now()->timestamp - $this->activeConnections[$name]
                                    : null,
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
                 * Obtiene el número de conexiones activas
                 */
                public function getActiveConnectionCount(): int
                {
                    return count($this->activeConnections);
                }

                /**
                 * Configura timeouts de conexión
                 */
                public function setTimeouts(int $idleTimeout = 600, int $lifetime = 1800): void
                {
                    $this->maxIdleTime = $idleTimeout;
                    $this->maxLifetime = $lifetime;
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Inicializar monitoreo en producción
        if (app()->environment('production')) {
            $this->initializeConnectionMonitoring();
        }
    }

    /**
     * Inicializa el monitoreo de conexiones
     */
    protected function initializeConnectionMonitoring(): void
    {
        $manager = $this->app->make('db.connection-manager');
        $manager->initialize();

        // Registrar shutdown handler para liberar conexiones
        $this->app->terminating(function () {
            $manager = $this->app->make('db.connection-manager');
            $manager->disconnectIdleConnections();
        });
    }
}
