<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// =====================================================
// TAREAS PROGRAMADAS - Laravel 11
// =====================================================

// Enviar alertas de mantenimiento cada día a las 08:00
Schedule::command('mantenimiento:alertas --dias=30')->dailyAt('08:00');

// Enviar recordatorios de cobranza cada día a las 09:00
Schedule::command('cobranza:enviar-recordatorios')->dailyAt('09:00');

// Generar cobros de pólizas cada día a las 07:00
Schedule::command('polizas:generar-cobros')->dailyAt('07:00');

// Generar mantenimientos de pólizas cada día a las 07:15
Schedule::command('app:process-poliza-maintenance')->dailyAt('07:15');

// Alertas de vencimiento de pólizas y reset mensual a las 07:30
Schedule::command('polizas:alertas-vencimiento')
    ->dailyAt('07:30')
    ->appendOutputTo(storage_path('logs/polizas_alertas.log'));

// Enviar recordatorios por WhatsApp 1 día antes del vencimiento a las 09:00
Schedule::command('whatsapp:enviar-recordatorios --dias=1')
    ->dailyAt('09:00')
    ->appendOutputTo(storage_path('logs/whatsapp_recordatorios.log'));

// Sincronizar stock de series automáticamente cada madrugada (02:00 AM)
Schedule::command('productos:sync-series-stock --auto --notify')
    ->dailyAt('02:00')
    ->appendOutputTo(storage_path('logs/cron_sync.log'));

// Reconciliar inventario diariamente (03:00 AM) - Detecta discrepancias
Schedule::command('inventario:reconciliar')
    ->dailyAt('03:00')
    ->appendOutputTo(storage_path('logs/inventario_reconciliacion.log'));

// Actualizar cuentas por pagar vencidas cada día a las 06:00
Schedule::command('cuentas:actualizar-vencidas')
    ->dailyAt('06:00')
    ->appendOutputTo(storage_path('logs/cuentas_vencidas.log'));

// Enviar reporte de cobranza diario a la hora configurada
Schedule::call(function () {
    try {
        $config = EmpresaConfiguracion::first();

        if (!$config || !$config->cobros_reporte_automatico || empty($config->email_cobros)) {
            return;
        }

        $horaReporte = $config->cobros_hora_reporte ?? '08:00';
        $horaActual = now()->format('H:i');

        // Verificar si ya se envió hoy usando cache
        $cacheKey = 'reporte_cobros_enviado_' . now()->format('Y-m-d');
        if (cache()->has($cacheKey)) {
            return; // Ya se envió hoy
        }

        // Verificar si es la hora correcta (ventana de 10 minutos)
        [$horaConfig, $minConfig] = explode(':', $horaReporte);
        $horaConfigMinutos = (int) $horaConfig * 60 + (int) $minConfig;

        [$horaAct, $minAct] = explode(':', $horaActual);
        $horaActualMinutos = (int) $horaAct * 60 + (int) $minAct;

        $diferencia = $horaActualMinutos - $horaConfigMinutos;

        // Solo ejecutar si estamos entre 0 y 10 minutos después de la hora configurada
        if ($diferencia < 0 || $diferencia > 10) {
            return;
        }

        Artisan::call('cobranza:enviar-reporte');

        // Marcar como enviado hoy (expira a medianoche)
        cache()->put($cacheKey, true, now()->endOfDay());

        \Log::info('Reporte de cobranza diario ejecutado', [
            'hora_configurada' => $horaReporte,
            'hora_actual' => $horaActual,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en reporte de cobranza: ' . $e->getMessage());
    }
})
    ->hourly()
    ->name('reporte-cobranza-diario')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/cobranza_reporte.log'));

// Enviar reporte de pagos a proveedores diario a la hora configurada
Schedule::call(function () {
    try {
        $config = EmpresaConfiguracion::first();

        if (!$config || !$config->pagos_reporte_automatico || empty($config->email_pagos)) {
            return;
        }

        $horaReporte = $config->pagos_hora_reporte ?? '08:00';
        $horaActual = now()->format('H:i');

        // Verificar si ya se envió hoy usando cache
        $cacheKey = 'reporte_pagos_enviado_' . now()->format('Y-m-d');
        if (cache()->has($cacheKey)) {
            return; // Ya se envió hoy
        }

        // Verificar si es la hora correcta (ventana de 10 minutos)
        [$horaConfig, $minConfig] = explode(':', $horaReporte);
        $horaConfigMinutos = (int) $horaConfig * 60 + (int) $minConfig;

        [$horaAct, $minAct] = explode(':', $horaActual);
        $horaActualMinutos = (int) $horaAct * 60 + (int) $minAct;

        $diferencia = $horaActualMinutos - $horaConfigMinutos;

        // Solo ejecutar si estamos entre 0 y 10 minutos después de la hora configurada
        if ($diferencia < 0 || $diferencia > 10) {
            return;
        }

        Artisan::call('pagos:enviar-reporte');

        // Marcar como enviado hoy (expira a medianoche)
        cache()->put($cacheKey, true, now()->endOfDay());

        \Log::info('Reporte de pagos a proveedores diario ejecutado', [
            'hora_configurada' => $horaReporte,
            'hora_actual' => $horaActual,
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en reporte de pagos: ' . $e->getMessage());
    }
})
    ->hourly()
    ->name('reporte-pagos-diario')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pagos_reporte.log'));

// Sincronizar tareas de Microsoft To Do cada 5 minutos
Schedule::job(new \App\Jobs\SyncMicrosoftTasks)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/microsoft_sync.log'));

// =====================================================
// BACKUPS AUTOMÁTICOS - Configuración dinámica
// =====================================================

// Backup SQL cada hora
Schedule::call(function () {
    try {
        $config = EmpresaConfiguracion::first();

        if (!$config || !$config->backup_automatico) {
            return;
        }

        $backupTipo = $config->backup_tipo ?? 'sql';

        // Si el tipo es 'completo', solo el backup diario se encarga
        if ($backupTipo === 'completo') {
            return;
        }

        $retencionDias = $config->retencion_backups ?? 30;

        Artisan::call('db:backup', [
            '--compress' => true,
            '--auto' => true,
            '--clean' => $retencionDias,
        ]);

        \Log::info('Backup SQL automático ejecutado');

    } catch (\Exception $e) {
        \Log::error('Error en backup automático: ' . $e->getMessage());
    }
})
    ->hourly()
    ->name('backup-db-automatico')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database_backup.log'));

// Backup diario a la hora configurada
Schedule::call(function () {
    try {
        $config = EmpresaConfiguracion::first();

        if (!$config || !$config->backup_automatico) {
            return;
        }

        $retencionDias = $config->retencion_backups ?? 30;
        $backupTipo = $config->backup_tipo ?? 'sql';
        $cloudEnabled = $config->backup_cloud_enabled ?? false;

        if ($backupTipo === 'completo') {
            // Backup completo (BD + archivos)
            $backupService = app(\App\Services\DatabaseBackupService::class);
            $result = $backupService->createSecureBackup([
                'name' => 'full_backup_' . now()->format('Y-m-d'),
                'compress' => true,
            ]);

            // Subir a Google Drive si está habilitado
            if (config('services.google_drive.enabled') && $result['success']) {
                try {
                    $driveService = app(\App\Services\GoogleDriveService::class);
                    $fullPath = storage_path('app/private/' . $result['path']);

                    if (file_exists($fullPath)) {
                        $uploadResult = $driveService->upload($fullPath);
                        if ($uploadResult['success']) {
                            \Log::info('Backup subido a Google Drive', [
                                'file_id' => $uploadResult['file_id'],
                                'link' => $uploadResult['web_link'],
                            ]);
                        } else {
                            \Log::warning('Error subiendo a Google Drive: ' . $uploadResult['message']);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('No se pudo subir a Google Drive: ' . $e->getMessage());
                }
            }

            \Log::info('Backup completo diario ejecutado');
        } else {
            // Backup solo SQL con verificación
            Artisan::call('db:backup', [
                '--compress' => true,
                '--auto' => true,
                '--verify' => true,
                '--clean' => $retencionDias,
                '--name' => 'daily_backup_' . now()->format('Y-m-d'),
            ]);

            \Log::info('Backup SQL diario ejecutado');
        }

    } catch (\Exception $e) {
        \Log::error('Error en backup diario: ' . $e->getMessage());
    }
})
    ->dailyAt('03:00')
    ->name('backup-full-diario')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database_backup.log'));

// Sincronizar catálogo de CVA (Marcas y Categorías) diariamente a las 04:00 AM
Schedule::command('cva:sincronizar-datos')
    ->dailyAt('04:00')
    ->appendOutputTo(storage_path('logs/cva_sync.log'));

// Sincronizar catálogo de productos CVA diariamente a las 03:30 AM
Schedule::command('app:sync-cva-catalog --limit=100')
    ->dailyAt('03:30')
    ->appendOutputTo(storage_path('logs/cva_catalog_sync.log'));

