<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\EmpresaConfiguracion;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Enviar alertas de mantenimiento cada día a las 08:00
        $schedule->command('mantenimiento:alertas --dias=30')->dailyAt('08:00');

        // Enviar recordatorios de cobranza cada día a las 09:00
        // ❌ DESACTIVADO: El usuario prefiere enviar correos manualmente
        // $schedule->command('cobranza:enviar-recordatorios')->dailyAt('09:00');

        // Enviar recordatorios por WhatsApp 1 día antes del vencimiento a las 09:00
        $schedule->command('whatsapp:enviar-recordatorios --dias=1')
            ->dailyAt('09:00')
            ->appendOutputTo(storage_path('logs/whatsapp_recordatorios.log'));

        // Sincronizar stock de series automáticamente cada madrugada (02:00 AM)
        $schedule->command('productos:sync-series-stock --auto --notify')
            ->dailyAt('02:00')
            ->appendOutputTo(storage_path('logs/cron_sync.log'));

        // Sincronizar catálogo CVA automáticamente cada madrugada (02:00 AM)
        $schedule->command('app:sync-cva-catalog --limit=500')
            ->dailyAt('02:05')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cva_sync_daily.log'));

        // Sincronizar estatus y guías de pedidos CVA cada hora
        $schedule->command('cva:sync-orders')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cva_orders_sync.log'));

        // Actualizar cuentas por pagar vencidas cada día a las 06:00
        $schedule->command('cuentas:actualizar-vencidas')
            ->dailyAt('06:00')
            ->appendOutputTo(storage_path('logs/cuentas_vencidas.log'));

        // =====================================================
        // BACKUPS AUTOMÁTICOS - Configuración dinámica desde empresa
        // =====================================================
        $this->scheduleBackups($schedule);
    }

    /**
     * Programar backups según configuración de empresa
     */
    protected function scheduleBackups(Schedule $schedule): void
    {
        // Skip scheduling if table doesn't exist (during migrations)
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('empresa_configuracion')) {
                return;
            }
        } catch (\Exception $e) {
            return;
        }

        // Backup SQL cada hora (según frecuencia configurada)
        $schedule->call(function () {
            try {
                $config = EmpresaConfiguracion::getConfig();

                if (!$config || !$config->backup_automatico) {
                    return;
                }

                $retencionDias = $config->retencion_backups ?? 30;
                $backupTipo = $config->backup_tipo ?? 'sql';
                $cloudEnabled = $config->backup_cloud_enabled ?? false;

                // Si el tipo es 'completo', no ejecutar backup SQL cada hora
                // Solo el backup diario se encargará
                if ($backupTipo === 'completo') {
                    return;
                }

                // Ejecutar el backup SQL
                \Artisan::call('db:backup', [
                    '--compress' => true,
                    '--auto' => true,
                    '--clean' => $retencionDias,
                ]);

                \Log::info('Backup SQL automático ejecutado', [
                    'retencion_dias' => $retencionDias,
                    'cloud_enabled' => $cloudEnabled,
                ]);

            } catch (\Exception $e) {
                \Log::error('Error en backup automático: ' . $e->getMessage());
            }
        })
            ->hourly()
            ->name('backup-db-automatico')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/database_backup.log'));

        // Backup diario a la hora configurada
        $schedule->call(function () {
            try {
                $config = EmpresaConfiguracion::getConfig();

                if (!$config || !$config->backup_automatico) {
                    return;
                }

                $retencionDias = $config->retencion_backups ?? 30;
                $backupTipo = $config->backup_tipo ?? 'sql';
                $cloudEnabled = $config->backup_cloud_enabled ?? false;

                // Determinar qué tipo de backup ejecutar
                if ($backupTipo === 'completo') {
                    // Backup completo (BD + archivos)
                    $backupService = app(\App\Services\DatabaseBackupService::class);
                    $result = $backupService->createApplicationBackup([
                        'name' => 'full_backup_' . now()->format('Y-m-d'),
                        'compress' => true,
                    ]);

                    if ($cloudEnabled && $result['success']) {
                        // Intentar subir a Google Cloud si está habilitado
                        // TODO: Implementar método público uploadToCloud en DatabaseBackupService
                        /*
                        try {
                            $backupService->uploadToGoogleCloud($result['path']);
                            \Log::info('Backup subido a Google Cloud', ['path' => $result['path']]);
                        } catch (\Exception $e) {
                            \Log::warning('No se pudo subir a Google Cloud: ' . $e->getMessage());
                        }
                        */
                    }

                    \Log::info('Backup completo diario ejecutado', ['tipo' => 'completo']);
                } else {
                    // Backup solo SQL con verificación
                    \Artisan::call('db:backup', [
                        '--compress' => true,
                        '--auto' => true,
                        '--verify' => true,
                        '--clean' => $retencionDias,
                        '--name' => 'daily_backup_' . now()->format('Y-m-d'),
                    ]);

                    \Log::info('Backup SQL diario ejecutado', ['tipo' => 'sql']);
                }

            } catch (\Exception $e) {
                \Log::error('Error en backup diario: ' . $e->getMessage());
            }
        })
            ->dailyAt($this->getBackupTime())
            ->name('backup-full-diario')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/database_backup.log'));
    }

    /**
     * Obtener la hora configurada para el backup diario
     */
    protected function getBackupTime(): string
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('empresa_configuracion')) {
                return '03:00';
            }
            $config = EmpresaConfiguracion::getConfig();
            return $config->backup_hora_completo ?? '03:00';
        } catch (\Exception $e) {
            return '03:00';
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
