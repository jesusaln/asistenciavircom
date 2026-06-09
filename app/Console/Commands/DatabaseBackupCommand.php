<?php

// app/Console/Commands/DatabaseBackupCommand.php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DatabaseBackupCommand extends Command
{
    /**
     * El nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'db:backup
                             {--name= : Nombre personalizado para el respaldo}
                             {--compress : Comprimir el respaldo en ZIP}
                             {--clean= : Eliminar respaldos con más de N días (opcional)}
                             {--type= : Tipo de respaldo (full, incremental, differential)}
                             {--encrypt : Encriptar datos sensibles}
                             {--verify : Verificar integridad del respaldo}
                             {--auto : Ejecutar sin confirmación (para tareas programadas)}
                             {--no-upload : No subir a la nube (solo local)}
                             {--frequency= : Frecuencia para respaldo programado (daily, weekly, monthly)}
                             {--time= : Hora específica para respaldo programado (HH:MM)}
                             {--day-of-week= : Día de la semana (0-6, Domingo=0)}
                             {--day-of-month= : Día del mes (1-31)}
                             {--retention= : Días de retención para limpieza automática}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Crea un respaldo avanzado de la base de datos con opciones de seguridad, compresión y programación';

    /**
     * Ejecuta el comando.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->components->info('🚀 Iniciando respaldo avanzado de la base de datos...');

        // Construir opciones avanzadas
        $options = [
            'name' => $this->option('name'),
            'compress' => $this->option('compress'),
            'encrypt_sensitive' => $this->option('encrypt'),
            'generate_checksum' => $this->option('verify'),
            'confirmed' => $this->option('auto'), // Para ejecución automática sin confirmación
            'upload' => !$this->option('no-upload'),
        ];

        // Determinar tipo de backup
        $backupType = $this->option('type') ?? 'full';

        // Si se especifica configuración de horario, crear backup programado
        if ($this->option('frequency')) {
            $scheduleConfig = [
                'name' => $options['name'] ?? 'scheduled_backup_' . now()->format('Y-m-d_H-i-s'),
                'type' => $backupType,
                'frequency' => $this->option('frequency'),
                'time' => $this->option('time') ?? '02:00',
                'day_of_week' => $this->option('day-of-week'),
                'day_of_month' => $this->option('day-of-month'),
                'retention_days' => $this->option('retention') ?? 30,
                'compression' => $options['compress'],
                'encryption' => $options['encrypt_sensitive'],
                'verify_integrity' => $options['generate_checksum']
            ];

            $result = $backupService->createScheduledBackup($scheduleConfig);

            if (!$result['success']) {
                $this->components->error($result['message']);
                return self::FAILURE;
            }

            $this->components->info('✅ Backup programado ejecutado exitosamente');
            $this->newLine();

            if (isset($result['next_run'])) {
                $this->info('📅 Próxima ejecución: ' . Carbon::parse($result['next_run'])->format('Y-m-d H:i:s'));
            }

        } else {
            // Backup único con opciones avanzadas
            switch ($backupType) {
                case 'incremental':
                    $result = $backupService->createIncrementalBackup($options);
                    break;
                case 'differential':
                    $result = $backupService->createSecureBackup(array_merge($options, ['differential' => true]));
                    break;
                default:
                    $result = $backupService->createSecureBackup($options);
                    break;
            }

            if (!$result['success']) {
                $this->components->error($result['message']);
                return self::FAILURE;
            }
        }

        // Mostrar detalles avanzados del respaldo
        $this->info('✅ Respaldo creado exitosamente');
        $this->newLine();

        $tableData = [
            ['Nombre del archivo', $result['path']],
            ['Tamaño', $this->formatBytes($result['size'])],
            ['Tipo', ucfirst($backupType)],
            ['Compresión', $options['compress'] ? 'Sí (ZIP)' : 'No'],
            ['Encriptación', $options['encrypt_sensitive'] ? 'Sí' : 'No'],
        ];

        // Información adicional según el tipo de backup
        if (isset($result['changes_detected'])) {
            $tableData[] = ['Cambios detectados', $result['changes_detected'] . ' tablas'];
        }

        if (isset($result['encrypted']) && $result['encrypted']) {
            $tableData[] = ['Estado de seguridad', 'Datos sensibles encriptados'];
        }

        if (isset($result['checksum'])) {
            $checksum = is_string($result['checksum']) ? $result['checksum'] : ($result['checksum']['sha256'] ?? json_encode($result['checksum']));
            $tableData[] = ['Checksum (SHA-256)', substr($checksum, 0, 16) . '...'];
        }

        if (isset($result['compression_ratio'])) {
            $tableData[] = ['Ratio de compresión', $result['compression_ratio'] . '%'];
        }

        $this->table(['Descripción', 'Valor'], $tableData);

        // Limpiar respaldos antiguos
        if ($cleanDays = $this->option('clean')) {
            $deleted = $backupService->cleanOldBackups((int) $cleanDays);
            $this->newLine();
            $this->components->info("🧹 Se eliminaron {$deleted} respaldos con más de {$cleanDays} días.");
        }

        // Mostrar estadísticas de seguridad si están disponibles
        if ($this->option('verify')) {
            $this->newLine();
            $this->components->info('🔒 Estadísticas de seguridad:');
            $securityStats = $backupService->getSecurityStats();

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Puntuación de seguridad', $securityStats['security_score'] . '/100'],
                    ['Tasa de éxito', $securityStats['success_rate'] . '%'],
                    ['Respaldos encriptados', $securityStats['encrypted_backups']],
                    ['Edad último respaldo', $securityStats['last_backup_age'] . ' horas'],
                ]
            );
        }

        $this->newLine();
        $this->components->info('🎉 Respaldo completado exitosamente.');

        return self::SUCCESS;
    }

    /**
     * Formatea bytes a una unidad legible.
     */
    protected function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = $size > 0 ? floor(log($size, 1024)) : 0;
        $base = min($base, count($units) - 1);

        $value = $size / pow(1024, $base);

        return round($value, $precision) . ' ' . $units[$base];
    }
}
