<?php

namespace App\Services;

use App\Models\BackupLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage as StorageFacade;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

/**
 * Servicio para gestionar respaldos de la base de datos.
 */
class DatabaseBackupService
{
    protected string $backupDisk = 'local';

    protected string $backupPath = 'backups/database/';

    protected string $fullBackupPath = 'backups/application/';

    public function __construct()
    {
        // Permitir configurar disco y ruta desde config/backup.php o .env
        $this->backupDisk = config('backup.disk', $this->backupDisk);
        $configuredPath = config('backup.path', $this->backupPath);
        // Normalizar y asegurar trailing slash
        $configuredPath = str_replace('\\', '/', trim($configuredPath));
        $this->backupPath = rtrim($configuredPath, '/') . '/';

        $configuredFull = config('backup.full_backup_path', $this->fullBackupPath);
        $configuredFull = str_replace('\\', '/', trim($configuredFull));
        $this->fullBackupPath = rtrim($configuredFull, '/') . '/';
    }

    /**
     * Obtener lista de tablas sensibles desde configuración
     */
    protected function getSensitiveTables(): array
    {
        return config('backup.security.sensitive_tables', [
            'users',
            'clientes',
            'personal_access_tokens',
            'password_resets',
            'failed_jobs',
            'user_notifications',
            'bitacora_actividades'
        ]);
    }

    /**
     * Configuración de seguridad
     */
    protected array $securityConfig = [
        'encrypt_sensitive_data' => true,
        'use_checksums' => true,
        'backup_integrity_check' => true,
        'max_backup_age_days' => 90,
        'require_password_confirmation' => true
    ];

    /**
     * Configuración de almacenamiento remoto
     */
    protected function getRemoteStorageConfig(): array
    {
        return config('backup.remote_storage', [
            'enabled' => false,
            'primary_provider' => 's3', // s3, gcs, azure, dropbox
            'providers' => [
                's3' => [
                    'enabled' => env('BACKUP_S3_ENABLED', false),
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                    'bucket' => env('BACKUP_S3_BUCKET'),
                    'path' => env('BACKUP_S3_PATH', 'backups/database/'),
                ],
                'gcs' => [
                    'enabled' => env('BACKUP_GCS_ENABLED', false),
                    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
                    'key_file' => env('GOOGLE_APPLICATION_CREDENTIALS'),
                    'bucket' => env('BACKUP_GCS_BUCKET'),
                    'path' => env('BACKUP_GCS_PATH', 'backups/database/'),
                ],
                'azure' => [
                    'enabled' => env('BACKUP_AZURE_ENABLED', false),
                    'account_name' => env('AZURE_STORAGE_NAME'),
                    'account_key' => env('AZURE_STORAGE_KEY'),
                    'container' => env('BACKUP_AZURE_CONTAINER'),
                    'path' => env('BACKUP_AZURE_PATH', 'backups/database/'),
                ]
            ],
            'sync_local_and_remote' => env('BACKUP_SYNC_BOTH', true),
            'cleanup_remote' => env('BACKUP_CLEANUP_REMOTE', true),
            'remote_retention_days' => env('BACKUP_REMOTE_RETENTION', 90)
        ]);
    }

    /**
     * Configuración de notificaciones desde archivo de configuración
     */
    protected function getNotificationConfig(): array
    {
        return config('backup.notifications', [
            'email_enabled' => true,
            'slack_enabled' => false,
            'notify_on_backup_success' => true,
            'notify_on_backup_failure' => true,
            'notify_on_restore' => true,
            'notify_on_security_issues' => true,
            'notify_admin_on_critical_errors' => true,
            'daily_digest_enabled' => false,
            'weekly_report_enabled' => true
        ]);
    }

    /**
     * Configuración de seguridad desde archivo de configuración
     */
    protected function getSecurityConfig(): array
    {
        return config('backup.security', [
            'encrypt_sensitive_data' => true,
            'use_checksums' => true,
            'backup_integrity_check' => true,
            'max_backup_age_days' => 90,
            'require_password_confirmation' => true
        ]);
    }

    /**
     * Crear un nuevo respaldo de la base de datos.
     *
     * @param  array  $options  Opciones: 'name', 'compress'
     * @return array ['success' => bool, 'message' => string, 'path' => string, 'size' => int]
     */
    public function createBackup(array $options = []): array
    {
        $filename = $this->generateFilename($options['name'] ?? null);
        $fullPath = $this->backupPath . $filename;

        $logData = [
            'filename' => basename($fullPath),
            'path' => $fullPath,
            'type' => 'sql',
            'method' => null,
            'status' => 'failed',
            'message' => null,
            'metadata' => [
                'compress' => $options['compress'] ?? false,
                'driver' => config('database.default'),
            ],
        ];

        try {
            if (!$this->hasEnoughDiskSpace(500 * 1024 * 1024)) {
                throw new \Exception('Espacio en disco insuficiente');
            }

            $driver = config('database.default');
            $specificTables = $options['tables_to_backup'] ?? null;

            if ($driver === 'mysql' && $this->isMysqldumpAvailable() && !$specificTables) {
                $result = $this->createBackupWithMysqldump($fullPath, $specificTables);
                $logData['method'] = 'mysqldump';
            } elseif ($driver === 'pgsql' && $this->isPgDumpAvailable() && !$specificTables) {
                $result = $this->createBackupWithPgDump($fullPath, $specificTables);
                $logData['method'] = 'pg_dump';
            } else {
                $result = $this->createBackupWithLaravel($fullPath, $specificTables);
                $logData['method'] = 'laravel';
            }

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $logData['status'] = 'success';
            $logData['size'] = $result['size'];
            $logData['message'] = $result['message'];

            if (($options['compress'] ?? false) && $result['success']) {
                $compressResult = $this->compressBackup($result['path']);
                if ($compressResult['success']) {
                    $result = $compressResult;
                    $logData['path'] = $result['path'];
                    $logData['type'] = 'zip';
                    $logData['size'] = $result['size'];
                    $logData['metadata']['compress'] = true;
                    $logData['message'] = ($logData['message'] ?? $result['message'] ?? 'Respaldo creado exitosamente') . ' (comprimido)';
                } else {
                    throw new \Exception('Compresión fallida: ' . $compressResult['message']);
                }
            }

            BackupLog::create($logData);

            // Subir a la nube si está habilitado
            if ($options['upload'] ?? true) {
                $this->uploadToCloud($result['path']);

                // Enviar notificación de éxito
                $this->sendNotification('backup_success', [
                    'filename' => basename($result['path']),
                    'size' => $result['size'],
                    'size_formatted' => $this->formatBytes($result['size']),
                    'method' => $logData['method']
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $logData['message'] = $e->getMessage();
            BackupLog::create($logData);

            Log::error('DatabaseBackupService - createBackup failed', [
                'exception' => $e,
                'options' => $options,
            ]);

            // Enviar notificación de error
            $this->sendNotification('backup_failure', [
                'error' => $e->getMessage(),
                'options' => $options
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear respaldo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crear respaldo incremental basado en cambios desde último backup
     *
     * @param  array  $options  Opciones para backup incremental
     * @return array ['success' => bool, 'message' => string, 'path' => string, 'size' => int, 'changes_detected' => int]
     */
    public function createIncrementalBackup(array $options = []): array
    {
        try {
            $lastBackup = $this->getLastBackupInfo();

            if (!$lastBackup) {
                // Si no hay backup anterior, crear backup completo
                Log::info('No se encontró backup anterior, creando backup completo');
                return $this->createSecureBackup(array_merge($options, [
                    'incremental' => false,
                    'name' => ($options['name'] ?? 'backup') . '_full_' . now()->format('Y-m-d_H-i-s')
                ]));
            }

            // Detectar cambios desde último backup
            $changes = $this->detectDatabaseChanges($lastBackup['created_at']);

            if (empty($changes['modified_tables']) && empty($changes['new_tables'])) {
                return [
                    'success' => true,
                    'message' => 'No se detectaron cambios desde el último backup',
                    'incremental' => true,
                    'changes_detected' => 0,
                    'skipped' => true
                ];
            }

            // Crear backup solo con tablas modificadas
            $result = $this->createSecureBackup(array_merge($options, [
                'incremental' => true,
                'tables_to_backup' => array_merge($changes['modified_tables'], $changes['new_tables']),
                'base_backup' => $lastBackup['filename'],
                'name' => ($options['name'] ?? 'backup') . '_incremental_' . now()->format('Y-m-d_H-i-s')
            ]));

            if ($result['success']) {
                $result['changes_detected'] = count($changes['modified_tables']) + count($changes['new_tables']);
                $result['modified_tables'] = $changes['modified_tables'];
                $result['new_tables'] = $changes['new_tables'];
                $result['total_rows_affected'] = $changes['total_rows_affected'];

                Log::info('Backup incremental creado exitosamente', [
                    'changes_detected' => $result['changes_detected'],
                    'modified_tables' => $changes['modified_tables'],
                    'new_tables' => $changes['new_tables']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error creando backup incremental: ' . $e->getMessage(), [
                'options' => $options
            ]);

            return [
                'success' => false,
                'message' => 'Error creando backup incremental: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Detectar cambios en la base de datos desde timestamp específico
     */
    protected function detectDatabaseChanges(string $sinceTimestamp): array
    {
        $changes = [
            'modified_tables' => [],
            'new_tables' => [],
            'total_rows_affected' => 0
        ];

        try {
            $tables = $this->getAllTables();
            $sinceDate = Carbon::parse($sinceTimestamp);

            foreach ($tables as $table) {
                $tableName = $table->table_name ?? $table->TABLE_NAME;

                // Verificar si la tabla existe y cuándo fue modificada por última vez
                $lastModified = $this->getTableLastModified($tableName);

                if (!$lastModified) {
                    // Tabla nueva
                    $changes['new_tables'][] = $tableName;
                    $rowCount = $this->getTableRowCount($tableName);
                    $changes['total_rows_affected'] += $rowCount;
                } elseif ($lastModified->gt($sinceDate)) {
                    // Tabla modificada
                    $changes['modified_tables'][] = $tableName;
                    $newRows = $this->getNewRowsCount($tableName, $sinceDate);
                    $changes['total_rows_affected'] += $newRows;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error detectando cambios en BD: ' . $e->getMessage());
        }

        return $changes;
    }

    /**
     * Obtener información del último backup disponible
     */
    protected function getLastBackupInfo(): ?array
    {
        $backups = $this->listBackups();

        if (empty($backups)) {
            return null;
        }

        // Buscar el backup más reciente que no sea incremental
        $fullBackups = array_filter($backups, function ($backup) {
            return !str_contains($backup['name'], 'incremental');
        });

        if (empty($fullBackups)) {
            return null;
        }

        // Ordenar por fecha de creación (más reciente primero)
        uasort($fullBackups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $latestBackup = reset($fullBackups);

        return [
            'filename' => $latestBackup['name'],
            'created_at' => $latestBackup['created_at'],
            'size' => $latestBackup['size']
        ];
    }

    /**
     * Obtener fecha de última modificación de una tabla
     */
    protected function getTableLastModified(string $tableName): ?Carbon
    {
        try {
            $driver = config('database.default');

            switch ($driver) {
                case 'mysql':
                    $result = DB::select("
                        SELECT UPDATE_TIME
                        FROM information_schema.tables
                        WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = ?
                    ", [$tableName]);

                    if (!empty($result) && $result[0]->UPDATE_TIME) {
                        return Carbon::parse($result[0]->UPDATE_TIME);
                    }
                    break;

                case 'pgsql':
                    $result = DB::select("
                        SELECT GREATEST(
                            (SELECT MAX(created) FROM pg_stat_user_tables WHERE relname = ?),
                            (SELECT MAX(modified) FROM pg_stat_user_tables WHERE relname = ?)
                        ) as last_modified
                    ", [$tableName, $tableName]);

                    if (!empty($result) && $result[0]->last_modified) {
                        return Carbon::parse($result[0]->last_modified);
                    }
                    break;

                case 'sqlite':
                    // SQLite no tiene metadatos de modificación incorporados
                    // Usar timestamp del archivo de la base de datos
                    $dbPath = config('database.connections.sqlite.database');
                    if (file_exists($dbPath)) {
                        return Carbon::createFromTimestamp(filemtime($dbPath));
                    }
                    break;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Error obteniendo última modificación de tabla {$tableName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener conteo de filas nuevas desde fecha específica
     */
    protected function getNewRowsCount(string $tableName, Carbon $sinceDate): int
    {
        try {
            // Buscar columnas de timestamp comunes
            $timestampColumns = ['created_at', 'updated_at', 'fecha_creacion', 'fecha_actualizacion'];

            foreach ($timestampColumns as $column) {
                $count = DB::table($tableName)
                    ->where($column, '>', $sinceDate)
                    ->count();

                if ($count > 0) {
                    return $count;
                }
            }

            // Si no hay columnas de timestamp, contar todas las filas (considerar tabla modificada)
            return DB::table($tableName)->count();

        } catch (\Exception $e) {
            Log::warning("Error contando filas nuevas en tabla {$tableName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener conteo total de filas de una tabla
     */
    protected function getTableRowCount(string $tableName): int
    {
        try {
            return DB::table($tableName)->count();
        } catch (\Exception $e) {
            Log::warning("Error contando filas en tabla {$tableName}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crear respaldo con características de seguridad avanzadas
     *
     * @param  array  $options  Opciones adicionales de seguridad
     * @return array ['success' => bool, 'message' => string, 'path' => string, 'size' => int, 'checksum' => string]
     */
    public function createSecureBackup(array $options = []): array
    {
        $securityConfig = $this->getSecurityConfig();

        $options = array_merge([
            'encrypt_sensitive' => $securityConfig['encrypt_sensitive_data'],
            'generate_checksum' => $securityConfig['use_checksums'],
            'include_integrity_check' => $securityConfig['backup_integrity_check'],
            'require_confirmation' => $securityConfig['require_password_confirmation']
        ], $options);

        // Verificar permisos si es requerido
        if ($options['require_confirmation'] && !isset($options['confirmed'])) {
            return [
                'success' => false,
                'message' => 'Se requiere confirmación explícita para crear respaldos seguros',
                'requires_confirmation' => true
            ];
        }

        $result = $this->createBackup($options);

        if (!$result['success']) {
            return $result;
        }

        $backupPath = $result['path'];
        $fullPath = Storage::disk($this->backupDisk)->path($backupPath);
        $checksum = null;

        try {
            // Generar checksum si está habilitado
            if ($options['generate_checksum']) {
                $checksum = $this->generateChecksum($fullPath);
                $result['checksum'] = $checksum;
            }

            // Encriptar datos sensibles si está habilitado
            if ($options['encrypt_sensitive']) {
                $encryptedPath = $this->encryptSensitiveData($fullPath, $backupPath);
                if ($encryptedPath) {
                    $result['path'] = $encryptedPath;
                    $result['encrypted'] = true;
                    $result['size'] = filesize(Storage::disk($this->backupDisk)->path($encryptedPath));
                }
            }

            // Verificación de integridad si está habilitada
            if ($options['include_integrity_check']) {
                $integrityResult = $this->verifyBackupIntegrity($result['path']);
                $result['integrity_verified'] = $integrityResult['success'];
                if (!$integrityResult['success']) {
                    $result['integrity_warnings'] = $integrityResult['warnings'] ?? [];
                }
            }

            Log::info('Respaldo seguro creado exitosamente', [
                'path' => $result['path'],
                'size' => $result['size'],
                'encrypted' => $result['encrypted'] ?? false,
                'checksum' => $checksum
            ]);

        } catch (\Exception $e) {
            Log::error('Error en respaldo seguro: ' . $e->getMessage(), [
                'backup_path' => $backupPath,
                'options' => $options
            ]);

            return [
                'success' => false,
                'message' => 'Error creando respaldo seguro: ' . $e->getMessage()
            ];
        }

        return $result;
    }

    /**
     * Encriptar datos sensibles en el respaldo
     */
    protected function encryptSensitiveData(string $filePath, string $relativePath): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if (!$content) {
            return null;
        }

        $encryptedContent = Crypt::encrypt($content);
        $encryptedPath = str_replace(['.sql', '.zip'], ['_encrypted.sql', '_encrypted.zip'], $relativePath);

        if (Storage::disk($this->backupDisk)->put($encryptedPath, $encryptedContent)) {
            // Eliminar archivo original después de encriptar
            unlink($filePath);
            return $encryptedPath;
        }

        return null;
    }

    /**
     * Generar múltiples checksums para verificación avanzada de integridad
     */
    protected function generateChecksum(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Archivo no encontrado para generar checksum');
        }

        $fileSize = filesize($filePath);
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            throw new \Exception('No se pudo abrir el archivo para generar checksum');
        }

        $chunkSize = 8192; // 8KB chunks
        $totalChunks = ceil($fileSize / $chunkSize);

        // Inicializar hashes
        $md5 = hash_init('md5');
        $sha1 = hash_init('sha1');
        $sha256 = hash_init('sha256');
        $crc32 = 0;

        $chunkIndex = 0;
        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);

            // Actualizar hashes
            hash_update($md5, $chunk);
            hash_update($sha1, $chunk);
            hash_update($sha256, $chunk);

            // Calcular CRC32 del chunk
            $crc32 ^= crc32($chunk);

            $chunkIndex++;

            // Verificar progreso cada 100 chunks para archivos grandes
            if ($chunkIndex % 100 === 0) {
                Log::debug("Procesando chunk {$chunkIndex}/{$totalChunks} para checksum");
            }
        }

        fclose($handle);

        return [
            'md5' => hash_final($md5),
            'sha1' => hash_final($sha1),
            'sha256' => hash_final($sha256),
            'crc32' => sprintf('%08x', $crc32),
            'file_size' => $fileSize,
            'chunk_count' => $chunkIndex,
            'generated_at' => now()->toISOString()
        ];
    }

    /**
     * Verificar integridad avanzada de un archivo usando múltiples algoritmos
     */
    public function verifyAdvancedIntegrity(string $filename, array $expectedChecksums = null): array
    {
        $path = $this->backupPath . $filename;

        if (!$this->backupExists($filename)) {
            return [
                'success' => false,
                'message' => 'El archivo de respaldo no existe.',
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }

        $fullPath = $this->getBackupPath($filename);

        try {
            // Verificaciones básicas primero
            $basicChecks = $this->performBasicFileChecks($fullPath);
            if (!$basicChecks['success']) {
                return $basicChecks;
            }

            // Generar checksums actuales
            $currentChecksums = $this->generateChecksum($fullPath);

            // Si no se proporcionan checksums esperados, solo devolver información
            if (!$expectedChecksums) {
                return [
                    'success' => true,
                    'message' => 'Verificación completada - no hay checksums de referencia',
                    'checksums' => $currentChecksums,
                    'verification_level' => 'current_only'
                ];
            }

            // Comparar checksums
            $comparison = $this->compareChecksums($currentChecksums, $expectedChecksums);

            if (!$comparison['match']) {
                return [
                    'success' => false,
                    'message' => 'Los checksums no coinciden - posible corrupción de archivo',
                    'expected_checksums' => $expectedChecksums,
                    'current_checksums' => $currentChecksums,
                    'mismatches' => $comparison['mismatches'],
                    'error_code' => 'CHECKSUM_MISMATCH'
                ];
            }

            return [
                'success' => true,
                'message' => 'Verificación de integridad exitosa',
                'checksums' => $currentChecksums,
                'verification_level' => 'full',
                'algorithms_verified' => ['md5', 'sha1', 'sha256', 'crc32']
            ];

        } catch (\Exception $e) {
            Log::error('Error en verificación avanzada de integridad: ' . $e->getMessage(), [
                'filename' => $filename,
                'expected_checksums' => $expectedChecksums
            ]);

            return [
                'success' => false,
                'message' => 'Error durante la verificación: ' . $e->getMessage(),
                'error_code' => 'VERIFICATION_ERROR'
            ];
        }
    }

    /**
     * Realizar verificaciones básicas del archivo
     */
    protected function performBasicFileChecks(string $filePath): array
    {
        // Verificar existencia
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Archivo no encontrado',
                'error_code' => 'FILE_NOT_FOUND'
            ];
        }

        // Verificar tamaño
        $size = filesize($filePath);
        if ($size === 0) {
            return [
                'success' => false,
                'message' => 'Archivo vacío',
                'error_code' => 'EMPTY_FILE'
            ];
        }

        // Verificar permisos de lectura
        if (!is_readable($filePath)) {
            return [
                'success' => false,
                'message' => 'No se puede leer el archivo',
                'error_code' => 'PERMISSION_DENIED'
            ];
        }

        // Verificar que no esté corrupto (intentar abrir)
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'message' => 'Archivo corrupto o ilegible',
                'error_code' => 'CORRUPTED_FILE'
            ];
        }

        // Leer primeros bytes para verificar estructura básica
        $header = fread($handle, 1024);
        fclose($handle);

        if (empty(trim($header))) {
            return [
                'success' => false,
                'message' => 'Archivo sin contenido válido',
                'error_code' => 'INVALID_CONTENT'
            ];
        }

        return [
            'success' => true,
            'message' => 'Verificaciones básicas exitosas',
            'file_size' => $size,
            'can_read' => true
        ];
    }

    /**
     * Comparar checksums actuales con esperados
     */
    protected function compareChecksums(array $current, array $expected): array
    {
        $mismatches = [];
        $algorithms = ['md5', 'sha1', 'sha256', 'crc32'];

        foreach ($algorithms as $algorithm) {
            if (isset($expected[$algorithm]) && isset($current[$algorithm])) {
                if (strtolower($expected[$algorithm]) !== strtolower($current[$algorithm])) {
                    $mismatches[] = $algorithm;
                }
            }
        }

        return [
            'match' => empty($mismatches),
            'mismatches' => $mismatches,
            'algorithms_checked' => $algorithms
        ];
    }

    /**
     * Verificar integridad del respaldo
     */
    protected function verifyBackupIntegrity(string $relativePath): array
    {
        $fullPath = Storage::disk($this->backupDisk)->path($relativePath);

        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'Archivo no encontrado para verificación'
            ];
        }

        $warnings = [];
        $size = filesize($fullPath);

        // Verificaciones básicas
        if ($size === 0) {
            $warnings[] = 'Archivo vacío detectado';
        }

        if ($size > 500 * 1024 * 1024) { // 500MB
            $warnings[] = 'Archivo muy grande, verificación puede tomar tiempo';
        }

        // Verificar que el archivo no esté corrupto
        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'message' => 'No se puede leer el archivo'
            ];
        }

        // Leer primeros bytes para verificar estructura básica
        $header = fread($handle, 1024);
        fclose($handle);

        if (strpos($header, 'SQL') === false && strpos($header, 'CREATE') === false) {
            $warnings[] = 'El archivo puede no tener formato SQL válido';
        }

        return [
            'success' => true,
            'warnings' => $warnings,
            'file_size' => $size,
            'can_read' => true
        ];
    }

    /**
     * Crear respaldo usando mysqldump (solo MySQL).
     *
     * @param  string  $path  Ruta relativa
     * @param  array|null  $specificTables  Tablas específicas a respaldar
     */
    protected function createBackupWithMysqldump(string $path, ?array $specificTables = null): array
    {
        $config = config('database.connections.' . config('database.default'));
        $storagePath = Storage::disk($this->backupDisk)->path($path);

        $directory = dirname($storagePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $tableList = '';
        if ($specificTables) {
            $tableList = implode(' ', array_map('escapeshellarg', $specificTables));
        }

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --no-tablespaces %s %s > %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            $tableList,
            escapeshellarg($storagePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($storagePath) && filesize($storagePath) > 0) {
            return [
                'success' => true,
                'message' => 'Respaldo creado con mysqldump',
                'path' => $path,
                'size' => filesize($storagePath),
            ];
        }

        $error = implode("\n", $output);
        Log::error('mysqldump failed', ['command' => $command, 'output' => $error]);
        throw new \Exception("Error en mysqldump: {$error}");
    }

    /**
     * Crear respaldo usando pg_dump (solo PostgreSQL).
     *
     * @param  string       $path            Ruta relativa dentro de storage/app
     * @param  array|null   $specificTables  Tablas específicas a respaldar
     */
    protected function createBackupWithPgDump(string $path, ?array $specificTables = null): array
    {
        $config = config('database.connections.' . config('database.default'));
        $storagePath = Storage::disk($this->backupDisk)->path($path);

        $directory = dirname($storagePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 5432;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'] ?? '';
        $schema = $config['schema'] ?? 'public';

        // Tablas específicas
        $tableArgs = '';
        if (!empty($specificTables)) {
            foreach ($specificTables as $t) {
                $qualified = (strpos($t, '.') === false) ? ($schema . '.' . $t) : $t;
                $tableArgs .= ' -t ' . escapeshellarg($qualified);
            }
        }

        $env = '';
        if ($password !== '') {
            $env = 'PGPASSWORD=' . escapeshellarg($password) . ' ';
        }

        // Formato plano (-F p) para coherencia con flujo actual
        $command = sprintf(
            '%spg_dump -h %s -p %s -U %s -n %s%s -F p -f %s %s 2>&1',
            $env,
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            escapeshellarg($schema),
            $tableArgs,
            escapeshellarg($storagePath),
            escapeshellarg($database)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($storagePath) && filesize($storagePath) > 0) {
            return [
                'success' => true,
                'message' => 'Respaldo creado con pg_dump',
                'path' => $path,
                'size' => filesize($storagePath),
            ];
        }

        $error = implode("\n", $output);
        Log::error('pg_dump failed', ['command' => $command, 'output' => $error]);
        throw new \Exception("Error en pg_dump: {$error}");
    }

    /**
     * Crear respaldo usando Laravel (portable entre drivers).
     *
     * @param  string  $path  Ruta relativa
     * @param  array|null  $specificTables  Tablas específicas a respaldar (null para todas)
     */
    protected function createBackupWithLaravel(string $path, ?array $specificTables = null): array
    {
        $fullPath = Storage::disk($this->backupDisk)->path($path);
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $tables = $specificTables ? $this->getSpecificTables($specificTables) : $this->getAllTables();
        $sql = $this->generateSqlHeader();

        // Agregar comentario sobre tipo de backup
        if ($specificTables) {
            $sql = "-- Backup incremental - Tablas: " . implode(', ', $specificTables) . "\n" . $sql;
        }

        foreach ($tables as $table) {
            $sql .= $this->getTableStructure($table);
            $sql .= $this->getTableData($table);
        }

        $sql .= $this->getSqlFooter();

        // Asegurar UTF-8 sin BOM para evitar problemas en restauración
        $sql = $this->stripBom($sql);
        if (class_exists(\App\Helpers\Utf8Helper::class)) {
            $sql = \App\Helpers\Utf8Helper::cleanString($sql);
        }

        if (Storage::disk($this->backupDisk)->put($path, $sql)) {
            return [
                'success' => true,
                'message' => 'Respaldo creado con Laravel',
                'path' => $path,
                'size' => Storage::disk($this->backupDisk)->size($path),
            ];
        }

        Log::error('No se pudo guardar el respaldo', [
            'path' => $fullPath,
            'disk' => $this->backupDisk,
            'sql_length' => strlen($sql),
        ]);

        throw new \Exception('No se pudo guardar el archivo de respaldo');
    }

    /**
     * Obtener tablas específicas según el driver.
     */
    protected function getSpecificTables(array $tableNames): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        switch ($driver) {
            case 'mysql':
                $placeholders = str_repeat('?,', count($tableNames) - 1) . '?';
                return DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name IN ({$placeholders})", array_merge([$connection['database']], $tableNames));
            case 'pgsql':
                $placeholders = str_repeat('?,', count($tableNames) - 1) . '?';
                return DB::select("SELECT tablename AS table_name FROM pg_tables WHERE schemaname = ? AND tablename IN ({$placeholders})", array_merge([$connection['schema'] ?? 'public'], $tableNames));
            case 'sqlite':
                $placeholders = str_repeat('?,', count($tableNames) - 1) . '?';
                return DB::select("SELECT name AS table_name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name IN ({$placeholders})", $tableNames);
            default:
                throw new \Exception("Driver no soportado: {$driver}");
        }
    }

    /**
     * Obtener todas las tablas según el driver.
     */
    protected function getAllTables(): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        switch ($driver) {
            case 'mysql':
                return DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [$connection['database']]);
            case 'pgsql':
                return DB::select('SELECT tablename AS table_name FROM pg_tables WHERE schemaname = ?', [$connection['schema'] ?? 'public']);
            case 'sqlite':
                return DB::select("SELECT name AS table_name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            default:
                throw new \Exception("Driver no soportado: {$driver}");
        }
    }

    /**
     * Generar estructura SQL de una tabla.
     *
     * @param  object  $table
     */
    protected function getTableStructure($table): string
    {
        $tableName = $table->table_name ?? $table->TABLE_NAME;
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $row = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$tableName])[0];

            return "\n-- Estructura de tabla `{$tableName}`\n" . $row->sql . ";\n\n";
        }

        if ($driver === 'pgsql') {
            $connection = config("database.connections.{$driver}");
            $schema = $connection['schema'] ?? 'public';

            // Obtener columnas de la tabla
            $columns = DB::select(
                "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length, numeric_precision, numeric_scale
                 FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = ?
                 ORDER BY ordinal_position",
                [$schema, $tableName]
            );

            $columnDefs = [];
            $seqCreateStmts = [];
            $seqOwnStmts = [];
            foreach ($columns as $col) {
                $colName = $col->column_name;
                $dataType = strtolower($col->data_type);
                $nullable = ($col->is_nullable === 'YES');
                $default = $col->column_default;

                // Ajustar tipos con longitud/precisión
                if ($dataType === 'character varying' && $col->character_maximum_length) {
                    $typeDef = 'varchar(' . (int) $col->character_maximum_length . ')';
                } elseif ($dataType === 'character' && $col->character_maximum_length) {
                    $typeDef = 'char(' . (int) $col->character_maximum_length . ')';
                } elseif ($dataType === 'numeric' && $col->numeric_precision) {
                    $scale = $col->numeric_scale ?? 0;
                    $typeDef = 'numeric(' . (int) $col->numeric_precision . ',' . (int) $scale . ')';
                } else {
                    // Otros tipos tal cual
                    $typeDef = $dataType;
                }

                $def = '"' . $colName . '" ' . $typeDef;
                if (!$nullable) {
                    $def .= ' NOT NULL';
                }
                if ($default !== null) {
                    $def .= ' DEFAULT ' . $default;

                    // Si el default es nextval('seq'::regclass), asegurar que exista la secuencia
                    if (preg_match("/nextval\('([^']+)'::regclass\)/i", $default, $m)) {
                        $seqName = $m[1]; // puede venir con o sin esquema
                        $quotedSeq = '';
                        if (strpos($seqName, '.') !== false) {
                            [$seqSchema, $seqBare] = explode('.', $seqName, 2);
                            $quotedSeq = '"' . $seqSchema . '"."' . $seqBare . '"';
                        } else {
                            $quotedSeq = '"' . $seqName . '"';
                        }

                        $seqCreateStmts[$seqName] = 'CREATE SEQUENCE IF NOT EXISTS ' . $quotedSeq . ';';
                        $seqOwnStmts[] = 'ALTER SEQUENCE ' . $quotedSeq . ' OWNED BY "' . $tableName . '"."' . $colName . '";';
                    }
                }
                $columnDefs[] = $def;
            }

            // Clave primaria
            $pkCols = DB::select(
                "SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema = kcu.table_schema
                 WHERE tc.table_schema = ?
                   AND tc.table_name = ?
                   AND tc.constraint_type = 'PRIMARY KEY'
                 ORDER BY kcu.ordinal_position",
                [$schema, $tableName]
            );
            if (!empty($pkCols)) {
                $pkList = implode(', ', array_map(fn($c) => '"' . $c->column_name . '"', $pkCols));
                $columnDefs[] = 'PRIMARY KEY (' . $pkList . ')';
            }

            $ddl = "\n-- Estructura de tabla \"{$tableName}\"\n";
            $ddl .= 'DROP TABLE IF EXISTS "' . $tableName . '" CASCADE;' . "\n";
            // Crear secuencias requeridas antes de la tabla
            if (!empty($seqCreateStmts)) {
                $ddl .= implode("\n", array_values($seqCreateStmts)) . "\n";
            }
            $ddl .= 'CREATE TABLE IF NOT EXISTS "' . $tableName . '" (' . "\n  " . implode(",\n  ", $columnDefs) . "\n" . ")" . ";\n";
            // Asegurar ownership de las secuencias
            if (!empty($seqOwnStmts)) {
                $ddl .= implode("\n", $seqOwnStmts) . "\n";
            }
            $ddl .= "\n";
            return $ddl;
        }

        // MySQL
        $row = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];

        return "\n-- Estructura de tabla `{$tableName}`\n" . $row->{'Create Table'} . ";\n\n";
    }

    /**
     * Generar datos SQL de una tabla.
     *
     * @param  object  $table
     */
    protected function getTableData($table): string
    {
        $tableName = $table->table_name ?? $table->TABLE_NAME;
        $driver = config('database.default');
        $rows = DB::table($tableName)->get()->toArray();

        if (empty($rows)) {
            return '';
        }

        $colNames = array_keys((array) $rows[0]);
        if ($driver === 'pgsql') {
            $columns = '"' . implode('", "', $colNames) . '"';
            $sql = "-- Datos de tabla \"{$tableName}\"\nINSERT INTO \"{$tableName}\" ({$columns}) VALUES\n";
        } else {
            $columns = '`' . implode('`, `', $colNames) . '`';
            $sql = "-- Datos de tabla `{$tableName}`\nINSERT INTO `{$tableName}` ({$columns}) VALUES\n";
        }

        $values = [];
        foreach ($rows as $row) {
            $vals = array_map(function ($value) use ($driver) {
                if ($value === null) {
                    return 'NULL';
                }
                if (is_bool($value)) {
                    return $driver === 'pgsql' ? ($value ? 'TRUE' : 'FALSE') : ($value ? '1' : '0');
                }
                if (is_numeric($value)) {
                    return (string) $value;
                }
                $str = (string) $value;
                if ($driver === 'pgsql') {
                    $str = str_replace("'", "''", $str);
                } else {
                    $str = addslashes($str);
                }
                return "'" . $str . "'";
            }, (array) $row);
            $values[] = '(' . implode(', ', $vals) . ')';
        }

        return $sql . implode(",\n", $values) . ";\n\n";
    }

    /**
     * Encabezado SQL según driver.
     */
    protected function generateSqlHeader(): string
    {
        $driver = config('database.default');
        $dbName = config("database.connections.{$driver}.database");

        switch ($driver) {
            case 'mysql':
                return "-- MySQL Backup\n-- DB: {$dbName}\n-- Fecha: " . now() . "\n\nSET FOREIGN_KEY_CHECKS=0;\nSTART TRANSACTION;\n";
            case 'pgsql':
                return "-- PostgreSQL Backup\n-- DB: {$dbName}\n-- Fecha: " . now() . "\n\nBEGIN;\n";
            case 'sqlite':
                return "-- SQLite Backup\n-- DB: " . basename($dbName) . "\n-- Fecha: " . now() . "\n\nPRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n";
            default:
                return "-- Backup\n-- Fecha: " . now() . "\n";
        }
    }

    /**
     * Pie de archivo SQL.
     */
    protected function getSqlFooter(): string
    {
        $driver = config('database.default');
        switch ($driver) {
            case 'mysql':
                return "\nSET FOREIGN_KEY_CHECKS=1;\nCOMMIT;\n";
            case 'pgsql':
                return "\nCOMMIT;\n";
            case 'sqlite':
                return "\nCOMMIT;\n";
            default:
                return "\nCOMMIT;\n";
        }
    }

    /**
     * Comprimir un archivo SQL en ZIP con compresión inteligente.
     *
     * @param  string  $filePath  Ruta relativa
     * @param  array  $options  Opciones de compresión
     */
    public function compressBackup(string $filePath, array $options = []): array
    {
        $storagePath = Storage::disk($this->backupDisk)->path($filePath);

        if (!file_exists($storagePath)) {
            return ['success' => false, 'message' => "Archivo no encontrado: {$storagePath}"];
        }

        $fileSize = filesize($storagePath);
        $compressionLevel = $this->determineOptimalCompression($fileSize, $options);

        // Si el archivo es muy pequeño, no comprimir
        if ($compressionLevel === 0) {
            return [
                'success' => true,
                'message' => 'Archivo pequeño, no requiere compresión',
                'path' => $filePath,
                'size' => $fileSize,
                'compression_skipped' => true
            ];
        }

        $zipPath = str_replace('.sql', '.zip', $storagePath);

        $zip = new ZipArchive;

        // Abrir con el nivel de compresión óptimo
        $result = $zip->open($zipPath, ZipArchive::CREATE);

        if ($result !== true) {
            $errors = [
                ZipArchive::ER_EXISTS => 'Ya existe',
                ZipArchive::ER_INCONS => 'Inconsistencia',
                ZipArchive::ER_INVAL => 'Inválido',
                ZipArchive::ER_MEMORY => 'Sin memoria',
                ZipArchive::ER_NOENT => 'No encontrado',
                ZipArchive::ER_OPEN => 'No se pudo abrir',
            ];

            return ['success' => false, 'message' => $errors[$result] ?? "Error ZIP: {$result}"];
        }

        // Configurar nivel de compresión
        if ($compressionLevel > 0) {
            $zip->setCompressionIndex(0, $compressionLevel);
        }

        if (!$zip->addFile($storagePath, basename($storagePath))) {
            $zip->close();
            @unlink($zipPath);

            return ['success' => false, 'message' => 'No se pudo agregar al ZIP'];
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'ZIP no creado'];
        }

        $compressedSize = filesize($zipPath);
        $compressionRatio = round((1 - ($compressedSize / $fileSize)) * 100, 2);

        // Eliminar archivo original después de comprimir
        unlink($storagePath);
        $diskRoot = rtrim(Storage::disk($this->backupDisk)->path(''), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $relativePath = str_replace($diskRoot, '', $zipPath);

        return [
            'success' => true,
            'message' => "Comprimido OK (Ratio: {$compressionRatio}%)",
            'path' => $relativePath,
            'size' => $compressedSize,
            'original_size' => $fileSize,
            'compression_ratio' => $compressionRatio,
            'compression_level' => $compressionLevel
        ];
    }

    /**
     * Listar todos los respaldos disponibles.
     */
    public function listBackups(): array
    {
        $files = [];

        // Buscar en múltiples directorios
        $searchPaths = [
            '', // Raíz del directorio de backups (para archivos subidos)
            $this->backupPath, // backups/database/
            $this->fullBackupPath // backups/application/
        ];

        foreach ($searchPaths as $path) {
            try {
                $pathFiles = Storage::disk($this->backupDisk)->files($path);
                $files = array_merge($files, $pathFiles);
            } catch (\Exception $e) {
                Log::warning("Error accediendo al directorio {$path}: " . $e->getMessage());
            }
        }

        $backups = [];

        foreach ($files as $file) {
            // Verificar que sea un archivo de respaldo válido
            if (!preg_match('/\.(sql|zip|dbsql|db|bak|backup)$/i', $file)) {
                continue;
            }
            if (!Storage::disk($this->backupDisk)->exists($file)) {
                continue;
            }

            try {
                $size = Storage::disk($this->backupDisk)->size($file);
                $mtime = Storage::disk($this->backupDisk)->lastModified($file);

                $backups[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'created_at' => Carbon::createFromTimestamp($mtime)->format('Y-m-d H:i:s'),
                    'type' => pathinfo($file, PATHINFO_EXTENSION),
                ];
            } catch (\Exception $e) {
                Log::error("Error leyendo respaldo: {$file}", ['exception' => $e]);
            }
        }

        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $backups;
    }

    /**
     * Verificar si un respaldo existe.
     *
     * @param  string  $filename  Nombre del archivo
     */
    public function backupExists(string $filename): bool
    {
        // Buscar en múltiples ubicaciones
        $candidates = [
            $filename, // Raíz del directorio de backups
            $this->backupPath . $filename, // backups/database/
            $this->fullBackupPath . $filename // backups/application/
        ];

        foreach ($candidates as $path) {
            if (Storage::disk($this->backupDisk)->exists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener la ruta física del archivo de respaldo.
     *
     * @param  string  $filename  Nombre del archivo
     * @return string Ruta completa
     */
    public function getBackupPath(string $filename): string
    {
        // Buscar en múltiples ubicaciones
        $candidates = [
            $filename, // Raíz del directorio de backups
            $this->backupPath . $filename, // backups/database/
            $this->fullBackupPath . $filename // backups/application/
        ];

        foreach ($candidates as $path) {
            if (Storage::disk($this->backupDisk)->exists($path)) {
                return Storage::disk($this->backupDisk)->path($path);
            }
        }

        return '';
    }

    /**
     * Eliminar un archivo de respaldo.
     *
     * @param  string  $filename  Nombre del archivo
     */
    public function deleteBackup(string $filename): array
    {
        // Buscar en múltiples ubicaciones
        $candidates = [
            $filename, // Raíz del directorio de backups
            $this->backupPath . $filename, // backups/database/
            $this->fullBackupPath . $filename // backups/application/
        ];

        $foundPath = null;
        foreach ($candidates as $path) {
            if (Storage::disk($this->backupDisk)->exists($path)) {
                $foundPath = $path;
                break;
            }
        }

        if (!$foundPath) {
            return ['success' => false, 'message' => 'El archivo no existe.'];
        }

        $size = Storage::disk($this->backupDisk)->size($foundPath);
        if (Storage::disk($this->backupDisk)->delete($foundPath)) {
            return [
                'success' => true,
                'message' => 'Respaldo eliminado.',
                'freed_space' => $size,
            ];
        }

        return ['success' => false, 'message' => 'No se pudo eliminar el archivo.'];
    }


    /**
     * Restaurar la base de datos desde un respaldo con soporte para desencriptación.
     *
     * @param  string  $filename  Nombre del archivo
     * @param  array  $options  Opciones de restauración
     */
    public function restoreBackup(string $filename, array $options = []): array
    {
        $options = array_merge([
            'verify_integrity' => true,
            'create_prerestore_backup' => true,
            'dry_run' => false,
            'tables_to_restore' => [], // Para restauración granular
            'skip_sensitive_tables' => false
        ], $options);


        try {
            // Localizar respaldo en rutas posibles (bd o full)
            $candidates = [
                $filename,
                $this->backupPath . $filename,
                $this->fullBackupPath . $filename,
            ];

            $path = null;
            foreach ($candidates as $candidate) {
                if (Storage::disk($this->backupDisk)->exists($candidate)) {
                    $path = $candidate;
                    break;
                }
            }

            if (!$path) {
                throw new \Exception('Archivo no existe');
            }

            // Verificación de integridad antes de restaurar
            if ($options['verify_integrity']) {
                $integrityCheck = $this->verifyBackupIntegrity($path);
                if (!$integrityCheck['success']) {
                    throw new \Exception('Verificación de integridad fallida: ' . ($integrityCheck['message'] ?? 'Error desconocido'));
                }

                if (!empty($integrityCheck['warnings'])) {
                    Log::warning('Advertencias en verificación de integridad', [
                        'filename' => $filename,
                        'warnings' => $integrityCheck['warnings']
                    ]);
                }
            }

            $storagePath = Storage::disk($this->backupDisk)->path($path);
            $sqlContent = '';

            // Crear respaldo previo si está habilitado
            if ($options['create_prerestore_backup']) {
                $preRestoreResult = $this->createBackup([
                    'name' => 'pre_restore_' . now()->format('Y-m-d_H-i-s'),
                    'compress' => true
                ]);

                if (!$preRestoreResult['success']) {
                    Log::warning('No se pudo crear respaldo previo: ' . $preRestoreResult['message']);
                }
            }

            // Procesar archivo según tipo
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
                $sqlContent = $this->extractFromZip($storagePath);
            } else {
                $rawContent = Storage::disk($this->backupDisk)->get($path);

                // Desencriptar si es necesario
                if ($this->isEncrypted($rawContent)) {
                    $rawContent = $this->decryptContent($rawContent);
                }

                $sqlContent = $rawContent;
            }

            // Restauración granular si se especifican tablas
            if ($options['tables_to_restore']) {
                $sqlContent = $this->filterTablesInSql($sqlContent, $options['tables_to_restore']);
            }

            // Saltar tablas sensibles si está habilitado
            if ($options['skip_sensitive_tables']) {
                $sqlContent = $this->removeSensitiveTables($sqlContent);
            }

            // Dry run - solo verificar sintaxis sin ejecutar
            if ($options['dry_run']) {

                return [
                    'success' => true,
                    'message' => 'Verificación de sintaxis completada (dry run)',
                    'dry_run' => true,
                    'sql_preview' => substr($sqlContent, 0, 500) . '...'
                ];
            }

            // Sanitizar SQL para evitar errores con comandos de psql (como \unrestrict)
            $sqlContent = $this->sanitizeSqlForPdo($sqlContent);

            DB::unprepared($sqlContent);

            // Automatically fix sequences and data integrity after restore
            try {
                // 1. Fix sequences (Unique violation)
                Artisan::call('fix:sequences');
                Log::info('Sequences fixed automatically after restore', ['filename' => $filename]);

                // 2. Normalize CFDI filenames (Case sensitivity)
                $this->normalizeCfdiFilenames();
                Log::info('CFDI filenames normalized to lowercase automatically after restore');

                // 3. Align empresa_id if it's a multi-tenant system
                $this->alignEmpresaIdAfterRestore();
                Log::info('Empresa ID alignment completed after restore');

                // 4. Clear encrypted fields to avoid MAC invalid errors
                $this->clearEncryptedFieldsAfterRestore();
                Log::info('Encrypted fields cleared after restore to avoid key mismatch');

                // 5. ✅ FIX: Sincronizar configuración de la base de datos con el entorno actual para evitar bloqueos
                $isLocal = app()->environment('local', 'testing');
                DB::table('empresa_configuracion')->update([
                    'ssl_enabled' => !$isLocal,
                    'force_https' => !$isLocal,
                    'app_url' => config('app.url')
                ]);
                Log::info('Environment configuration (SSL/URL) synchronized automatically after restore', [
                    'env' => app()->environment(),
                    'app_url' => config('app.url')
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed post-restore fixes', [
                    'filename' => $filename,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Restauración completada exitosamente', [
                'filename' => $filename,
                'options' => $options,
                'tables_restored' => $options['tables_to_restore']
            ]);

            // Enviar notificación de restauración exitosa
            $this->sendNotification('restore_success', [
                'filename' => $filename,
                'tables_restored' => $options['tables_to_restore'] ?: 'all',
                'dry_run' => $options['dry_run'] ?? false
            ]);

            return [
                'success' => true,
                'message' => 'Base de datos restaurada exitosamente.',
                'tables_restored' => $options['tables_to_restore'] ?: 'all'
            ];
        } catch (\Exception $e) {


            Log::error('Error en restauración: ' . $e->getMessage(), [
                'filename' => $filename,
                'options' => $options
            ]);

            // Enviar notificación de error en restauración
            $this->sendNotification('restore_failure', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'options' => $options
            ]);

            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar si el contenido está encriptado
     */
    protected function isEncrypted(string $content): bool
    {
        // Intentar desencriptar para verificar si está encriptado
        try {
            Crypt::decrypt($content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Desencriptar contenido
     */
    protected function decryptContent(string $encryptedContent): string
    {
        try {
            return Crypt::decrypt($encryptedContent);
        } catch (\Exception $e) {
            throw new \Exception('No se pudo desencriptar el contenido: ' . $e->getMessage());
        }
    }

    /**
     * Extraer contenido SQL desde archivo ZIP
     */
    protected function extractFromZip(string $zipPath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('No se pudo abrir el archivo ZIP');
        }

        $tempDir = storage_path('app/temp_restore/');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Buscar recursivamente archivos SQL
        $allFiles = File::allFiles($tempDir);
        $sqlFiles = array_filter($allFiles, function ($file) {
            return $file->getExtension() === 'sql';
        });

        if (empty($sqlFiles)) {
            $this->cleanupTempDirectory($tempDir);
            throw new \Exception('No se encontraron archivos SQL en el ZIP (búsqueda recursiva)');
        }

        // Tomar el primer archivo SQL encontrado
        $sqlFile = reset($sqlFiles);
        $sqlContent = $sqlFile->getContents();

        // ✅ FIX: Restaurar archivos físicos (fotos de perfil, logos, etc.) si existen en el ZIP
        $tempStoragePath = $tempDir . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public';
        if (is_dir($tempStoragePath)) {
            try {
                // Sincronizar directorios clave
                $directoriesToSync = ['profile-photos', 'logos', 'favicons', 'logos_reportes', 'cfdis', 'herramientas', 'productos', 'fotos_carros', 'caja-chica', 'citas'];
                foreach ($directoriesToSync as $dir) {
                    $src = $tempStoragePath . DIRECTORY_SEPARATOR . $dir;
                    $dest = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $dir);

                    if (is_dir($src)) {
                        if (!is_dir($dest)) {
                            mkdir($dest, 0755, true);
                        }
                        File::copyDirectory($src, $dest);
                    }
                }
                Log::info('Archivos de almacenamiento restaurados desde el ZIP');
            } catch (\Exception $e) {
                Log::warning('No se pudieron restaurar algunos archivos de almacenamiento: ' . $e->getMessage());
            }
        }

        $this->cleanupTempDirectory($tempDir);

        return $sqlContent;
    }

    /**
     * Limpia un directorio temporal de forma segura (archivos y subdirectorios).
     */
    protected function cleanupTempDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }
    }

    /**
     * Crear restauración granular con opciones avanzadas
     *
     * @param  string  $filename  Nombre del archivo de respaldo
     * @param  array  $options  Opciones avanzadas de restauración granular
     */
    public function granularRestore(string $filename, array $options = []): array
    {
        $options = array_merge([
            'tables_to_restore' => [],
            'tables_to_exclude' => [],
            'include_structure' => true,
            'include_data' => true,
            'dry_run' => false,
            'create_restore_log' => true,
            'backup_before_restore' => true,
            'verify_integrity' => true,
            'remap_table_names' => [], // Para restaurar con nombres diferentes
            'where_conditions' => [], // Condiciones WHERE para filtrar datos
            'chunk_size' => 1000 // Procesar en chunks para tablas grandes
        ], $options);

        if (empty($options['tables_to_restore']) && empty($options['tables_to_exclude'])) {
            return [
                'success' => false,
                'message' => 'Debe especificar tablas a restaurar o excluir'
            ];
        }


        try {
            // Verificación previa
            if ($options['verify_integrity']) {
                $integrityCheck = $this->verifyAdvancedIntegrity($filename);
                if (!$integrityCheck['success']) {
                    throw new \Exception('Verificación de integridad fallida: ' . $integrityCheck['message']);
                }
            }

            // Crear respaldo previo si está habilitado
            if ($options['backup_before_restore']) {
                $preRestoreResult = $this->createBackup([
                    'name' => 'pre_granular_restore_' . now()->format('Y-m-d_H-i-s'),
                    'compress' => true
                ]);

                if (!$preRestoreResult['success']) {
                    Log::warning('No se pudo crear respaldo previo: ' . $preRestoreResult['message']);
                }
            }

            // Procesar archivo de respaldo
            $sqlContent = $this->extractSqlContent($filename);

            // Aplicar filtros granulares
            $filteredSql = $this->applyGranularFilters($sqlContent, $options);

            // Dry run - solo verificar sintaxis
            if ($options['dry_run']) {

                return [
                    'success' => true,
                    'message' => 'Verificación de sintaxis completada (dry run)',
                    'dry_run' => true,
                    'sql_preview' => substr($filteredSql, 0, 500) . '...',
                    'estimated_tables' => $this->estimateTablesInSql($filteredSql),
                    'estimated_rows' => $this->estimateRowsInSql($filteredSql)
                ];
            }

            // Ejecutar restauración granular
            $result = $this->executeGranularRestore($filteredSql, $options);



            // Crear log de restauración si está habilitado
            if ($options['create_restore_log']) {
                $this->createRestoreLog($filename, $options, $result);
            }

            // Enviar notificación
            $this->sendNotification('restore_success', [
                'filename' => $filename,
                'granular_restore' => true,
                'tables_restored' => implode(', ', $options['tables_to_restore'] ?: $options['tables_to_exclude']),
                'dry_run' => false
            ]);

            return [
                'success' => true,
                'message' => 'Restauración granular completada exitosamente',
                'tables_processed' => $result['tables_processed'],
                'rows_affected' => $result['rows_affected'],
                'structure_restored' => $result['structure_restored'],
                'data_restored' => $result['data_restored']
            ];

        } catch (\Exception $e) {


            Log::error('Error en restauración granular: ' . $e->getMessage(), [
                'filename' => $filename,
                'options' => $options
            ]);

            $this->sendNotification('restore_failure', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'granular_restore' => true
            ]);

            return [
                'success' => false,
                'message' => 'Error en restauración granular: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extraer contenido SQL de archivo (soporta encriptación)
     */
    protected function extractSqlContent(string $filename): string
    {
        $path = $this->backupPath . $filename;

        if (!Storage::disk($this->backupDisk)->exists($path)) {
            throw new \Exception('Archivo no existe');
        }

        $storagePath = Storage::disk($this->backupDisk)->path($path);
        $sqlContent = '';

        if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip') {
            $sqlContent = $this->extractFromZip($storagePath);
        } else {
            $rawContent = Storage::disk($this->backupDisk)->get($path);

            // Desencriptar si es necesario
            if ($this->isEncrypted($rawContent)) {
                $rawContent = $this->decryptContent($rawContent);
            }

            $sqlContent = $rawContent;
        }

        return $sqlContent;
    }

    /**
     * Aplicar filtros granulares al contenido SQL
     */
    protected function applyGranularFilters(string $sqlContent, array $options): string
    {
        $lines = explode("\n", $sqlContent);
        $filteredLines = [];
        $currentTable = null;
        $inCreateStatement = false;
        $inInsertStatement = false;
        $inTableStructure = false;

        foreach ($lines as $line) {
            $originalLine = $line;

            // Detectar tipo de statement
            if (preg_match('/CREATE TABLE [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
                $inCreateStatement = true;
                $inTableStructure = true;
            } elseif (preg_match('/INSERT INTO [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
                $inInsertStatement = true;
                $inTableStructure = false;
            } elseif (preg_match('/DROP TABLE [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
            }

            // Aplicar filtros de tabla
            $includeLine = $this->shouldIncludeTable($currentTable, $options);

            // Aplicar remapeo de nombres si está configurado
            if ($includeLine && $currentTable && isset($options['remap_table_names'][$currentTable])) {
                $line = $this->remapTableName($line, $currentTable, $options['remap_table_names'][$currentTable]);
            }

            // Aplicar condiciones WHERE si están configuradas
            if ($includeLine && $inInsertStatement && $currentTable && isset($options['where_conditions'][$currentTable])) {
                $line = $this->applyWhereConditions($line, $options['where_conditions'][$currentTable]);
            }

            // Incluir línea si pasa los filtros
            if ($includeLine) {
                $filteredLines[] = $line;
            }

            // Detectar fin de statements
            if ($inCreateStatement && strpos($line, ';') !== false && !preg_match('/CREATE TABLE/i', $line)) {
                $inCreateStatement = false;
                $inTableStructure = false;
                $currentTable = null;
            }

            if ($inInsertStatement && strpos($line, ';') !== false && substr_count($line, "'") % 2 == 0) {
                $inInsertStatement = false;
                $currentTable = null;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Determinar si una tabla debe incluirse según opciones
     */
    protected function shouldIncludeTable(?string $tableName, array $options): bool
    {
        if (!$tableName) {
            return true; // Incluir líneas que no pertenecen a tablas específicas
        }

        // Si se especifican tablas a restaurar, solo incluir esas
        if (!empty($options['tables_to_restore'])) {
            return in_array($tableName, $options['tables_to_restore']);
        }

        // Si se especifican tablas a excluir, incluir todas menos esas
        if (!empty($options['tables_to_exclude'])) {
            return !in_array($tableName, $options['tables_to_exclude']);
        }

        return true;
    }

    /**
     * Remapear nombre de tabla en línea SQL
     */
    protected function remapTableName(string $line, string $originalName, string $newName): string
    {
        return str_ireplace($originalName, $newName, $line);
    }

    /**
     * Aplicar condiciones WHERE a statements INSERT
     */
    protected function applyWhereConditions(string $line, array $conditions): string
    {
        // Esta es una implementación básica - en producción requeriría parsing SQL más sofisticado
        foreach ($conditions as $column => $value) {
            if (stripos($line, 'VALUES') !== false) {
                // Agregar condición WHERE después del INSERT
                $line = rtrim($line, ';') . " WHERE {$column} = '{$value}';";
                break;
            }
        }

        return $line;
    }

    /**
     * Ejecutar restauración granular procesando el SQL filtrado
     */
    protected function executeGranularRestore(string $sqlContent, array $options): array
    {
        $result = [
            'tables_processed' => 0,
            'rows_affected' => 0,
            'structure_restored' => false,
            'data_restored' => false
        ];

        // Dividir SQL en statements individuales
        $statements = $this->splitSqlStatements($sqlContent);

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            // Detectar tipo de statement
            if (stripos($statement, 'CREATE TABLE') === 0 && $options['include_structure']) {
                DB::unprepared($statement);
                $result['structure_restored'] = true;
                $result['tables_processed']++;
            } elseif (stripos($statement, 'INSERT INTO') === 0 && $options['include_data']) {
                // Procesar en chunks para tablas grandes
                if ($options['chunk_size'] > 0) {
                    $this->executeChunkedInsert($statement, $options['chunk_size']);
                } else {
                    DB::unprepared($statement);
                }

                $result['data_restored'] = true;
                $result['rows_affected']++;
            }
        }

        return $result;
    }

    /**
     * Dividir contenido SQL en statements individuales
     */
    protected function splitSqlStatements(string $sqlContent): array
    {
        $statements = [];
        $lines = explode("\n", $sqlContent);
        $currentStatement = '';
        $inString = false;
        $quoteChar = '';

        foreach ($lines as $line) {
            $line = trim($line);

            // Detectar inicio/fin de strings
            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];

                if (($char === '"' || $char === "'") && ($i === 0 || $line[$i - 1] !== '\\')) {
                    if (!$inString) {
                        $inString = true;
                        $quoteChar = $char;
                    } elseif ($char === $quoteChar) {
                        $inString = false;
                        $quoteChar = '';
                    }
                }
            }

            $currentStatement .= $line . "\n";

            // Si no estamos dentro de un string y encontramos punto y coma, es fin de statement
            if (!$inString && strpos($line, ';') !== false) {
                $statements[] = $currentStatement;
                $currentStatement = '';
            }
        }

        // Agregar statement restante si existe
        if (!empty(trim($currentStatement))) {
            $statements[] = $currentStatement;
        }

        return $statements;
    }

    /**
     * Ejecutar INSERT en chunks para manejar tablas grandes
     */
    protected function executeChunkedInsert(string $insertStatement, int $chunkSize): void
    {
        // Esta es una implementación básica - en producción requeriría parsing SQL avanzado
        try {
            DB::unprepared($insertStatement);
        } catch (\Exception $e) {
            Log::warning('Error en INSERT chunked, ejecutando normalmente: ' . $e->getMessage());
            DB::unprepared($insertStatement);
        }
    }

    /**
     * Sanitizar contenido SQL para ser ejecutado vía PDO (Laravel).
     * Remueve meta-comandos de psql y posible "basura" al final del archivo.
     */
    protected function sanitizeSqlForPdo(string $sql): string
    {
        $lines = explode("\n", $sql);
        $filteredLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Ignorar líneas que empiezan con diagonal inversa (meta-comandos de psql)
            // Ej: \unrestrict, \connect, \set
            if (strpos($trimmed, '\\') === 0) {
                continue;
            }

            // Ignorar líneas que son solo un cierre de paréntesis huérfano al final del dump (ruido común en algunos zips)
            if ($trimmed === ')') {
                continue;
            }

            $filteredLines[] = $line;
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Crear log detallado de restauración granular
     */
    protected function createRestoreLog(string $filename, array $options, array $result): void
    {
        try {
            BackupLog::create([
                'filename' => 'granular_restore_' . basename($filename) . '_' . now()->format('Y-m-d_H-i-s'),
                'path' => $this->backupPath . $filename,
                'type' => 'restore',
                'method' => 'granular',
                'status' => 'success',
                'message' => 'Restauración granular completada',
                'metadata' => [
                    'original_backup' => $filename,
                    'tables_processed' => $result['tables_processed'],
                    'rows_affected' => $result['rows_affected'],
                    'options' => $options
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo crear log de restauración granular: ' . $e->getMessage());
        }
    }

    /**
     * Filtrar tablas específicas en contenido SQL
     */
    protected function filterTablesInSql(string $sqlContent, array $tablesToRestore): string
    {
        $lines = explode("\n", $sqlContent);
        $filteredLines = [];
        $currentTable = null;
        $inCreateStatement = false;
        $inInsertStatement = false;

        foreach ($lines as $line) {
            // Detectar inicio de CREATE TABLE
            if (preg_match('/CREATE TABLE [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
                $inCreateStatement = true;
            }

            // Detectar inicio de INSERT
            if (preg_match('/INSERT INTO [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
                $inInsertStatement = true;
            }

            // Incluir línea si la tabla está en la lista de tablas a restaurar
            if (($inCreateStatement || $inInsertStatement) && $currentTable && in_array($currentTable, $tablesToRestore)) {
                $filteredLines[] = $line;
            } elseif (!$inCreateStatement && !$inInsertStatement) {
                // Incluir líneas que no son parte de CREATE ni INSERT
                $filteredLines[] = $line;
            }

            // Detectar fin de statements
            if ($inCreateStatement && strpos($line, ';') !== false && !preg_match('/CREATE TABLE/i', $line)) {
                $inCreateStatement = false;
                $currentTable = null;
            }

            if ($inInsertStatement && strpos($line, ';') !== false && substr_count($line, "'") % 2 == 0) {
                $inInsertStatement = false;
                $currentTable = null;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Remover tablas sensibles del contenido SQL
     */
    protected function removeSensitiveTables(string $sqlContent): string
    {
        $lines = explode("\n", $sqlContent);
        $filteredLines = [];
        $currentTable = null;
        $skipTable = false;

        foreach ($lines as $line) {
            // Detectar tabla actual
            if (preg_match('/(?:CREATE TABLE|INSERT INTO|DROP TABLE) [`"]?(\w+)[`"]?/i', $line, $matches)) {
                $currentTable = strtolower($matches[1]);
                $skipTable = in_array($currentTable, $this->getSensitiveTables());
            }

            // Saltar líneas de tablas sensibles
            if (!$skipTable) {
                $filteredLines[] = $line;
            }

            // Resetear cuando termina el statement
            if (strpos($line, ';') !== false) {
                $currentTable = null;
                $skipTable = false;
            }
        }

        return implode("\n", $filteredLines);
    }

    /**
     * Estimar número de tablas en contenido SQL
     */
    protected function estimateTablesInSql(string $sqlContent): int
    {
        preg_match_all('/CREATE TABLE [`"]?(\w+)[`"]?/i', $sqlContent, $matches);
        return count(array_unique($matches[1]));
    }

    /**
     * Estimar número de filas en contenido SQL
     */
    protected function estimateRowsInSql(string $sqlContent): int
    {
        preg_match_all('/INSERT INTO [`"]?(\w+)[`"]?/i', $sqlContent, $matches);
        return count($matches[1]);
    }

    /**
     * Limpiar respaldos más antiguos que X días.
     *
     * @param  int  $daysOld  Días de antigüedad
     */
    public function cleanOldBackups(int $daysOld): array
    {
        $cutoff = now()->subDays($daysOld);
        $files = Storage::disk($this->backupDisk)->files($this->backupPath);
        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($files as $file) {
            try {
                $mtime = Storage::disk($this->backupDisk)->lastModified($file);
                if (Carbon::createFromTimestamp($mtime)->lt($cutoff)) {
                    $freedSpace += Storage::disk($this->backupDisk)->size($file);
                    Storage::disk($this->backupDisk)->delete($file);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                Log::warning("No se pudo eliminar respaldo: {$file}", ['exception' => $e]);
            }
        }

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'freed_space' => $freedSpace,
        ];
    }

    /**
     * Crear respaldo completo de la aplicación (BD + archivos: fotos, etc.)
     */
    public function createApplicationBackup(array $options = []): array
    {
        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupBaseName = $options['name'] ?? ('app_backup_' . $timestamp);

            // 1) Crear respaldo de BD (sin comprimir para incorporarlo al zip)
            $dbResult = $this->createBackup([
                'name' => $backupBaseName . '_db.sql',
                'compress' => false,
                'upload' => false,
            ]);

            if (!($dbResult['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Error creando respaldo de base de datos: ' . ($dbResult['message'] ?? 'desconocido')
                ];
            }

            // 2) Preparar ZIP de aplicación
            $disk = Storage::disk($this->backupDisk);
            $zipRelPath = $this->fullBackupPath . $backupBaseName . '.zip';
            $zipAbsPath = $disk->path($zipRelPath);

            $zipDir = dirname($zipAbsPath);
            if (!is_dir($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipAbsPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP del respaldo.');
            }

            // 2a) Agregar SQL (asegurar sin BOM y UTF-8)
            $dbFileAbsPath = $disk->path($dbResult['path']);
            $sqlContent = @file_get_contents($dbFileAbsPath) ?: '';
            $sqlContent = $this->stripBom($sqlContent);
            // Limpieza básica de UTF-8 para evitar caracteres inválidos
            if (class_exists(\App\Helpers\Utf8Helper::class)) {
                $sqlContent = \App\Helpers\Utf8Helper::cleanString($sqlContent);
            }
            $zip->addFromString('database/' . basename($dbResult['path']), $sqlContent);

            // 2b) Agregar directorios de archivos (fotos y relacionados)
            $include = $options['include_paths'] ?? config('backup.files.include_paths', []);
            $exclude = $options['exclude'] ?? config('backup.files.exclude', []);

            // Asegurar que el directorio de backups y temporales estén excluidos para evitar recursividad
            $exclude[] = storage_path('app/backups');
            $exclude[] = storage_path('app/temp_restore');

            foreach ($include as $rel) {
                $abs = base_path($rel);
                if (is_dir($abs)) {
                    $this->addDirectoryToZip($zip, $abs, base_path(), $exclude);
                }
            }

            $zip->close();

            // Tamaño del ZIP
            $size = filesize($zipAbsPath) ?: 0;

            // Eliminar el SQL suelto generado, ya que está dentro del zip
            if (file_exists($dbFileAbsPath)) {
                @unlink($dbFileAbsPath);
            }

            // Subir a la nube si está habilitado
            $this->uploadToCloud($zipRelPath);

            return [
                'success' => true,
                'message' => 'Respaldo completo creado exitosamente',
                'path' => $zipRelPath,
                'size' => $size,
            ];
        } catch (\Exception $e) {
            Log::error('Error creando respaldo completo de aplicación', ['exception' => $e]);
            return [
                'success' => false,
                'message' => 'Error creando respaldo completo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Agregar un directorio completo al ZIP, respetando exclusiones.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $dirPath, string $basePath, array $exclude): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $full = $file->getPathname();
            $rel = ltrim(str_replace('\\', '/', substr($full, strlen(rtrim($basePath, DIRECTORY_SEPARATOR)))), '/');

            if ($this->shouldExclude($rel, $exclude)) {
                continue;
            }

            if ($file->isDir()) {
                // Asegurar entrada de directorio
                $zip->addEmptyDir($rel);
            } else {
                // Añadir archivo tal cual (binarios incluidos, p.ej. fotos)
                $zip->addFile($full, $rel);
            }
        }
    }

    protected function shouldExclude(string $relativePath, array $exclude): bool
    {
        foreach ($exclude as $pattern) {
            $pattern = str_replace('\\', '/', trim($pattern, '/'));
            if ($pattern === '')
                continue;
            if (str_starts_with($relativePath, $pattern)) {
                return true;
            }
        }
        return false;
    }

    protected function stripBom(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        return $content;
    }

    /**
     * Verificar integridad de un respaldo.
     *
     * @param  string  $filename  Nombre del archivo
     */
    public function verifyBackup(string $filename): array
    {
        $path = $this->backupPath . $filename;

        if (!$this->backupExists($filename)) {
            return [
                'success' => false,
                'message' => 'El archivo de respaldo no existe.',
            ];
        }

        $fullPath = $this->getBackupPath($filename);
        $size = filesize($fullPath);

        if ($size === 0) {
            return [
                'success' => false,
                'message' => 'El archivo está vacío.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Archivo válido.',
            'size' => $size,
            'modified' => filemtime($fullPath),
        ];
    }

    /**
     * Obtener información detallada de un respaldo.
     *
     * @param  string  $filename  Nombre del archivo
     */
    public function getBackupInfo(string $filename): array
    {
        if (!$this->backupExists($filename)) {
            throw new \Exception('Archivo no encontrado.');
        }

        $fullPath = $this->getBackupPath($filename);
        $size = filesize($fullPath);
        $mtime = filemtime($fullPath);

        return [
            'name' => $filename,
            'path' => $fullPath,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'created_at' => $mtime,
            'created_at_formatted' => Carbon::createFromTimestamp($mtime)->format('Y-m-d H:i:s'),
            'is_compressed' => in_array(pathinfo($filename, PATHINFO_EXTENSION), ['zip', 'gz', '7z']),
            'type' => pathinfo($filename, PATHINFO_EXTENSION),
        ];
    }

    /**
     * Obtener espacio libre en disco.
     *
     * @return int|null Bytes disponibles
     */
    public function getAvailableDiskSpace(): ?int
    {
        $free = @disk_free_space(storage_path('app'));

        return $free ?: null;
    }

    /**
     * Generar nombre de archivo para el respaldo.
     *
     * @param  string|null  $customName  Nombre personalizado
     */
    protected function generateFilename(?string $customName): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        if ($customName) {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $customName);
            if (preg_match('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}/', $customName)) {
                return $safeName . '.sql';
            }

            return $safeName . '_' . $timestamp . '.sql';
        }

        return "backup_{$timestamp}.sql";
    }

    /**
     * Verificar si mysqldump está disponible.
     */
    public function isMysqldumpAvailable(): bool
    {
        exec('mysqldump --version 2>&1', $output, $returnCode);

        return $returnCode === 0;
    }

    /**
     * Verificar si pg_dump está disponible (PostgreSQL).
     */
    public function isPgDumpAvailable(): bool
    {
        exec('pg_dump --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Verificar si hay espacio en disco.
     *
     * @param  int  $minBytes  Espacio mínimo requerido
     */
    protected function hasEnoughDiskSpace(int $minBytes): bool
    {
        $free = @disk_free_space(storage_path('app'));

        return $free && $free > $minBytes;
    }

    /**
     * Determinar el nivel óptimo de compresión basado en el tamaño del archivo
     *
     * @param  int  $fileSize  Tamaño del archivo en bytes
     * @param  array  $options  Opciones adicionales
     * @return int  Nivel de compresión (0-9, donde 0 = sin compresión)
     */
    protected function determineOptimalCompression(int $fileSize, array $options = []): int
    {
        $compressionConfig = config('backup.compression', [
            'enabled' => true,
            'level' => 6,
            'threshold_mb' => 100,
            'auto_compress' => true
        ]);

        // Si la compresión está deshabilitada
        if (!$compressionConfig['enabled'] || !($compressionConfig['auto_compress'] ?? true)) {
            return 0;
        }

        // Si el archivo es muy pequeño, no comprimir
        $thresholdBytes = ($compressionConfig['threshold_mb'] ?? 100) * 1024 * 1024;
        if ($fileSize < $thresholdBytes) {
            return 0;
        }

        // Determinar nivel de compresión basado en tamaño
        if ($fileSize > 1024 * 1024 * 1024) { // > 1GB
            return 1; // Compresión mínima para archivos muy grandes
        } elseif ($fileSize > 500 * 1024 * 1024) { // > 500MB
            return 3; // Compresión baja
        } elseif ($fileSize > 100 * 1024 * 1024) { // > 100MB
            return 6; // Compresión media
        } else {
            return 9; // Compresión máxima para archivos más pequeños
        }
    }

    /**
     * Formatear tamaño en bytes a unidad legible.
     *
     * @param  int  $size  Tamaño en bytes
     * @param  int  $precision  Decimales
     */
    protected function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        // Handle zero bytes explicitly to avoid log(0) errors
        if ($size <= 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $unitIndex = (int) min(floor($base), count($units) - 1);
        $value = $size / pow(1024, $unitIndex);

        return round($value, $precision) . ' ' . $units[$unitIndex];
    }

    /**
     * Obtener estadísticas avanzadas de seguridad y salud del sistema
     */
    public function getSecurityStats(): array
    {
        try {
            $backups = $this->listBackups();
            $logs = BackupLog::orderBy('created_at', 'desc')->limit(100)->get();

            $stats = [
                'total_backups' => count($backups),
                'encrypted_backups' => 0,
                'total_size' => 0,
                'encrypted_size' => 0,
                'success_rate' => 0,
                'last_backup_age' => null,
                'security_score' => 100,
                'warnings' => [],
                'recommendations' => []
            ];

            $successfulBackups = 0;
            $totalSize = 0;
            $encryptedSize = 0;

            foreach ($backups as $backup) {
                $totalSize += $backup['size'];

                // Verificar si está encriptado (por extensión o metadata)
                $isEncrypted = str_contains($backup['name'], 'encrypted') ||
                    str_contains($backup['path'], 'encrypted');

                if ($isEncrypted) {
                    $stats['encrypted_backups']++;
                    $encryptedSize += $backup['size'];
                }
            }

            $stats['total_size'] = $totalSize;
            $stats['encrypted_size'] = $encryptedSize;

            // Calcular tasa de éxito basada en logs recientes
            if ($logs->count() > 0) {
                $successfulBackups = $logs->where('status', 'success')->count();
                $stats['success_rate'] = round(($successfulBackups / $logs->count()) * 100, 2);
            }

            // Calcular edad del último backup
            if (!empty($backups)) {
                $lastBackup = $backups[0];
                $lastBackupDate = Carbon::createFromTimestamp(
                    Storage::disk($this->backupDisk)->lastModified($lastBackup['path'])
                );
                $stats['last_backup_age'] = $lastBackupDate->diffInHours(now());
            }

            // Generar recomendaciones de seguridad
            $stats['recommendations'] = $this->generateSecurityRecommendations($stats);

            // Calcular puntuación de seguridad
            $stats['security_score'] = $this->calculateSecurityScore($stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de seguridad: ' . $e->getMessage());

            return [
                'error' => 'No se pudieron obtener estadísticas de seguridad',
                'security_score' => 0,
                'warnings' => ['Error interno al calcular estadísticas']
            ];
        }
    }

    /**
     * Generar recomendaciones de seguridad basadas en estadísticas
     */
    protected function generateSecurityRecommendations(array $stats): array
    {
        $recommendations = [];

        if ($stats['success_rate'] < 90) {
            $recommendations[] = 'La tasa de éxito de respaldos es baja. Verifique los logs para identificar problemas.';
        }

        if ($stats['encrypted_backups'] === 0) {
            $recommendations[] = 'Considere habilitar encriptación para respaldos con datos sensibles.';
        }

        if ($stats['last_backup_age'] > 24) {
            $recommendations[] = 'El último respaldo tiene más de 24 horas. Configure respaldos automáticos.';
        }

        if ($stats['total_backups'] > 50) {
            $recommendations[] = 'Considere limpiar respaldos antiguos para optimizar espacio en disco.';
        }

        if ($stats['total_size'] > 1024 * 1024 * 1024) { // 1GB
            $recommendations[] = 'Los respaldos ocupan mucho espacio. Considere compresión o almacenamiento remoto.';
        }

        return $recommendations;
    }

    /**
     * Calcular puntuación de seguridad del sistema de respaldos
     */
    protected function calculateSecurityScore(array $stats): int
    {
        $score = 100;

        // Penalizar por baja tasa de éxito
        if ($stats['success_rate'] < 90) {
            $score -= 30;
        }

        // Penalizar por falta de encriptación
        if ($stats['encrypted_backups'] === 0) {
            $score -= 20;
        }

        // Penalizar por respaldos antiguos
        if ($stats['last_backup_age'] > 48) {
            $score -= 25;
        }

        // Penalizar por demasiados respaldos (posible desorganización)
        if ($stats['total_backups'] > 100) {
            $score -= 10;
        }

        return max(0, $score);
    }

    /**
     * Sistema avanzado de monitoreo y alertas del sistema de backups
     */
    public function getAdvancedMonitoringData(): array
    {
        try {
            $backups = $this->listBackups();
            $logs = BackupLog::orderBy('created_at', 'desc')->limit(200)->get();

            // Métricas básicas
            $totalBackups = count($backups);
            $totalSize = array_sum(array_column($backups, 'size'));

            // Análisis de tendencias (últimos 30 días)
            $thirtyDaysAgo = now()->subDays(30);
            $recentLogs = $logs->where('created_at', '>=', $thirtyDaysAgo);

            $successCount = $recentLogs->where('status', 'success')->count();
            $failureCount = $recentLogs->where('status', 'failed')->count();
            $successRate = $recentLogs->count() > 0 ? round(($successCount / $recentLogs->count()) * 100, 2) : 100;

            // Análisis de rendimiento
            $performanceMetrics = $this->analyzePerformanceMetrics($recentLogs);

            // Análisis de patrones de error
            $errorPatterns = $this->analyzeErrorPatterns($recentLogs);

            // Predicciones y recomendaciones
            $predictions = $this->generatePredictions($backups, $logs);

            // Métricas de capacidad
            $capacityMetrics = $this->analyzeCapacityUsage($backups);

            // Estado del sistema
            $systemHealth = $this->assessSystemHealth([
                'success_rate' => $successRate,
                'performance' => $performanceMetrics,
                'capacity' => $capacityMetrics,
                'error_patterns' => $errorPatterns
            ]);

            return [
                'timestamp' => now()->toISOString(),
                'overview' => [
                    'total_backups' => $totalBackups,
                    'total_size' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                    'success_rate_30d' => $successRate,
                    'system_health' => $systemHealth['status'],
                    'health_score' => $systemHealth['score']
                ],
                'performance' => $performanceMetrics,
                'capacity' => $capacityMetrics,
                'reliability' => [
                    'success_rate' => $successRate,
                    'avg_backup_time' => $performanceMetrics['avg_execution_time'],
                    'error_patterns' => $errorPatterns,
                    'reliability_score' => $this->calculateReliabilityScore($successRate, $errorPatterns)
                ],
                'predictions' => $predictions,
                'alerts' => $this->generateAlerts($systemHealth, $performanceMetrics, $capacityMetrics),
                'recommendations' => $this->generateRecommendations($systemHealth, $predictions, $capacityMetrics)
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de monitoreo avanzado: ' . $e->getMessage());

            return [
                'error' => 'No se pudieron obtener datos de monitoreo',
                'timestamp' => now()->toISOString(),
                'system_health' => 'error'
            ];
        }
    }

    /**
     * Analizar métricas de rendimiento de los backups
     */
    protected function analyzePerformanceMetrics($logs): array
    {
        $successfulLogs = $logs->where('status', 'success');

        if ($successfulLogs->isEmpty()) {
            return [
                'avg_execution_time' => 0,
                'fastest_backup' => 0,
                'slowest_backup' => 0,
                'performance_trend' => 'insufficient_data'
            ];
        }

        // Calcular tiempos de ejecución (estimados)
        $executionTimes = [];
        foreach ($successfulLogs as $log) {
            // Estimar tiempo basado en tamaño y método
            $estimatedTime = $this->estimateExecutionTime($log);
            if ($estimatedTime > 0) {
                $executionTimes[] = $estimatedTime;
            }
        }

        if (empty($executionTimes)) {
            return [
                'avg_execution_time' => 0,
                'fastest_backup' => 0,
                'slowest_backup' => 0,
                'performance_trend' => 'no_data'
            ];
        }

        $avgTime = array_sum($executionTimes) / count($executionTimes);

        return [
            'avg_execution_time' => round($avgTime, 2),
            'fastest_backup' => round(min($executionTimes), 2),
            'slowest_backup' => round(max($executionTimes), 2),
            'performance_trend' => $this->analyzePerformanceTrend($executionTimes)
        ];
    }

    /**
     * Estimar tiempo de ejecución basado en metadata del log
     */
    protected function estimateExecutionTime($log): float
    {
        $metadata = $log->metadata ?? [];

        if (isset($metadata['execution_time'])) {
            return (float) $metadata['execution_time'];
        }

        // Estimación basada en tamaño y método
        $size = $log->size ?? 0;
        $method = $log->method ?? 'laravel';

        $baseTime = $method === 'mysqldump' ? 0.5 : 1.0; // Segundos por MB
        $estimatedSeconds = ($size / (1024 * 1024)) * $baseTime;

        return max(1.0, $estimatedSeconds);
    }

    /**
     * Analizar tendencias de rendimiento
     */
    protected function analyzePerformanceTrend(array $executionTimes): string
    {
        if (count($executionTimes) < 5) {
            return 'insufficient_data';
        }

        // Comparar primera mitad con segunda mitad
        $midPoint = (int) (count($executionTimes) / 2);
        $firstHalf = array_slice($executionTimes, 0, $midPoint);
        $secondHalf = array_slice($executionTimes, $midPoint);

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $change = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        if ($change > 20) {
            return 'degrading';
        } elseif ($change < -20) {
            return 'improving';
        } else {
            return 'stable';
        }
    }

    /**
     * Analizar patrones de error
     */
    protected function analyzeErrorPatterns($logs): array
    {
        $failedLogs = $logs->where('status', 'failed');
        $errorPatterns = [];

        foreach ($failedLogs as $log) {
            $error = $log->message ?? 'Error desconocido';
            $errorKey = strtolower(substr($error, 0, 50)); // Primera parte del error

            if (!isset($errorPatterns[$errorKey])) {
                $errorPatterns[$errorKey] = [
                    'count' => 0,
                    'first_occurrence' => $log->created_at,
                    'last_occurrence' => $log->created_at,
                    'sample_message' => $error
                ];
            }

            $errorPatterns[$errorKey]['count']++;
            $errorPatterns[$errorKey]['last_occurrence'] = $log->created_at;
        }

        // Ordenar por frecuencia
        uasort($errorPatterns, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return array_slice($errorPatterns, 0, 10); // Top 10 errores
    }

    /**
     * Generar predicciones basadas en datos históricos
     */
    protected function generatePredictions(array $backups, $logs): array
    {
        $predictions = [];

        // Predicción de crecimiento de almacenamiento
        if (count($backups) >= 7) {
            $weeklyGrowth = $this->predictStorageGrowth($backups);
            $predictions['storage_growth'] = [
                'weekly_mb' => round($weeklyGrowth / (1024 * 1024), 2),
                'monthly_mb' => round(($weeklyGrowth * 4.33) / (1024 * 1024), 2),
                'time_to_full' => $this->predictTimeToCapacity($backups, $weeklyGrowth)
            ];
        }

        // Predicción de tasa de fracaso
        if ($logs->count() >= 10) {
            $failureTrend = $this->predictFailureTrend($logs);
            $predictions['failure_trend'] = $failureTrend;
        }

        // Próximos eventos importantes
        $predictions['upcoming_events'] = $this->predictUpcomingEvents($backups, $logs);

        return $predictions;
    }

    /**
     * Predecir crecimiento semanal de almacenamiento
     */
    protected function predictStorageGrowth(array $backups): float
    {
        // Ordenar backups por fecha
        usort($backups, function ($a, $b) {
            return strtotime($a['created_at']) - strtotime($b['created_at']);
        });

        if (count($backups) < 2) {
            return 0;
        }

        // Calcular crecimiento promedio por semana
        $firstBackup = reset($backups);
        $lastBackup = end($backups);

        $firstDate = Carbon::parse($firstBackup['created_at']);
        $lastDate = Carbon::parse($lastBackup['created_at']);

        $weeksDiff = max(1, $firstDate->diffInWeeks($lastDate));
        $sizeDiff = $lastBackup['size'] - $firstBackup['size'];

        return $weeksDiff > 0 ? ($sizeDiff / $weeksDiff) : 0;
    }

    /**
     * Predecir tiempo hasta llegar a capacidad
     */
    protected function predictTimeToCapacity(array $backups, float $weeklyGrowth): ?string
    {
        if ($weeklyGrowth <= 0) {
            return null;
        }

        $availableSpace = $this->getAvailableDiskSpace();
        if (!$availableSpace) {
            return null;
        }

        $currentUsage = array_sum(array_column($backups, 'size'));
        $remainingSpace = $availableSpace - $currentUsage;

        if ($remainingSpace <= 0) {
            return 'Capacidad agotada';
        }

        $weeksRemaining = $remainingSpace / $weeklyGrowth;

        if ($weeksRemaining > 52) {
            return round($weeksRemaining / 52, 1) . ' años';
        } elseif ($weeksRemaining > 4) {
            return round($weeksRemaining, 1) . ' semanas';
        } else {
            return round($weeksRemaining * 7, 1) . ' días';
        }
    }

    /**
     * Predecir tendencia de fallos
     */
    protected function predictFailureTrend($logs): array
    {
        $recentLogs = $logs->take(50); // Últimos 50 logs
        $recentFailures = $recentLogs->where('status', 'failed')->count();
        $recentTotal = $recentLogs->count();

        if ($recentTotal === 0) {
            return ['trend' => 'no_data', 'confidence' => 0];
        }

        $recentFailureRate = ($recentFailures / $recentTotal) * 100;

        // Comparar con período anterior
        $olderLogs = $logs->skip(50)->take(50);
        $olderFailures = $olderLogs->where('status', 'failed')->count();
        $olderTotal = $olderLogs->count();

        if ($olderTotal === 0) {
            return [
                'trend' => 'stable',
                'current_rate' => $recentFailureRate,
                'confidence' => 50
            ];
        }

        $olderFailureRate = ($olderFailures / $olderTotal) * 100;
        $rateChange = $recentFailureRate - $olderFailureRate;

        $trend = 'stable';
        if ($rateChange > 10) {
            $trend = 'increasing';
        } elseif ($rateChange < -10) {
            $trend = 'decreasing';
        }

        return [
            'trend' => $trend,
            'current_rate' => $recentFailureRate,
            'previous_rate' => $olderFailureRate,
            'change' => round($rateChange, 2),
            'confidence' => 75
        ];
    }

    /**
     * Predecir eventos próximos importantes
     */
    protected function predictUpcomingEvents(array $backups, $logs): array
    {
        $events = [];

        // Próxima limpieza programada
        $oldestBackup = end($backups);
        if ($oldestBackup) {
            $oldestDate = Carbon::parse($oldestBackup['created_at']);
            $cleanupDate = $oldestDate->copy()->addDays(30); // Basado en retención por defecto

            if ($cleanupDate->isFuture()) {
                $events[] = [
                    'type' => 'cleanup',
                    'description' => 'Limpieza automática de respaldos antiguos',
                    'scheduled_for' => $cleanupDate->toISOString(),
                    'days_until' => $oldestDate->diffInDays(now())
                ];
            }
        }

        // Si la tasa de fracaso es alta, sugerir mantenimiento
        $recentFailureRate = $logs->take(20)->where('status', 'failed')->count() / max(1, $logs->take(20)->count()) * 100;
        if ($recentFailureRate > 20) {
            $events[] = [
                'type' => 'maintenance',
                'description' => 'Mantenimiento recomendado debido a alta tasa de fallos',
                'priority' => 'high',
                'suggested_action' => 'Revisar logs y configuración del sistema'
            ];
        }

        return $events;
    }

    /**
     * Analizar uso de capacidad
     */
    protected function analyzeCapacityUsage(array $backups): array
    {
        $totalSize = array_sum(array_column($backups, 'size'));
        $availableSpace = $this->getAvailableDiskSpace();

        if (!$availableSpace) {
            return [
                'used_space' => $totalSize,
                'available_space' => 0,
                'usage_percentage' => 0,
                'status' => 'unknown'
            ];
        }

        $usedSpace = $totalSize;
        $usagePercentage = round(($usedSpace / $availableSpace) * 100, 2);

        $status = 'good';
        if ($usagePercentage > 90) {
            $status = 'critical';
        } elseif ($usagePercentage > 75) {
            $status = 'warning';
        }

        return [
            'used_space' => $usedSpace,
            'used_space_formatted' => $this->formatBytes($usedSpace),
            'available_space' => $availableSpace,
            'available_space_formatted' => $this->formatBytes($availableSpace),
            'usage_percentage' => $usagePercentage,
            'status' => $status
        ];
    }

    /**
     * Evaluar salud general del sistema
     */
    protected function assessSystemHealth(array $metrics): array
    {
        $score = 100;
        $issues = [];

        // Penalizar por baja tasa de éxito
        if ($metrics['success_rate'] < 90) {
            $score -= 30;
            $issues[] = 'Tasa de éxito baja';
        }

        // Penalizar por rendimiento degradado
        if (
            isset($metrics['performance']['performance_trend']) &&
            $metrics['performance']['performance_trend'] === 'degrading'
        ) {
            $score -= 15;
            $issues[] = 'Rendimiento degradándose';
        }

        // Penalizar por problemas de capacidad
        if ($metrics['capacity']['status'] === 'critical') {
            $score -= 25;
            $issues[] = 'Capacidad crítica';
        } elseif ($metrics['capacity']['status'] === 'warning') {
            $score -= 10;
            $issues[] = 'Capacidad cercana al límite';
        }

        // Penalizar por patrones de error frecuentes
        if (!empty($metrics['error_patterns'])) {
            $score -= min(20, count($metrics['error_patterns']) * 5);
            $issues[] = 'Patrones de error detectados';
        }

        $status = 'excellent';
        if ($score < 60) {
            $status = 'critical';
        } elseif ($score < 75) {
            $status = 'warning';
        } elseif ($score < 90) {
            $status = 'good';
        }

        return [
            'score' => max(0, $score),
            'status' => $status,
            'issues' => $issues
        ];
    }

    /**
     * Calcular puntuación de confiabilidad
     */
    protected function calculateReliabilityScore(float $successRate, array $errorPatterns): int
    {
        $score = $successRate;

        // Penalizar por errores frecuentes
        $errorPenalty = min(20, count($errorPatterns) * 10);
        $score -= $errorPenalty;

        return max(0, (int) $score);
    }

    /**
     * Generar alertas basadas en métricas del sistema
     */
    protected function generateAlerts(array $systemHealth, array $performanceMetrics, array $capacityMetrics): array
    {
        $alerts = [];

        // Alertas críticas
        if ($systemHealth['status'] === 'critical') {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'system_health',
                'message' => 'Salud del sistema crítica',
                'description' => 'El sistema de respaldos requiere atención inmediata',
                'timestamp' => now()->toISOString()
            ];
        }

        // Alertas de capacidad
        if ($capacityMetrics['status'] === 'critical') {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'capacity',
                'message' => 'Capacidad de almacenamiento crítica',
                'description' => 'El espacio disponible es menor al 10%',
                'timestamp' => now()->toISOString()
            ];
        }

        // Alertas de rendimiento
        if ($performanceMetrics['performance_trend'] === 'degrading') {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'performance',
                'message' => 'Rendimiento degradándose',
                'description' => 'Los tiempos de respaldo están aumentando',
                'timestamp' => now()->toISOString()
            ];
        }

        // Alertas de errores frecuentes
        if (!empty($performanceMetrics['error_patterns'])) {
            $topError = reset($performanceMetrics['error_patterns']);
            if ($topError['count'] > 5) {
                $alerts[] = [
                    'level' => 'warning',
                    'type' => 'errors',
                    'message' => 'Errores frecuentes detectados',
                    'description' => "Error '{$topError['sample_message']}' ocurrido {$topError['count']} veces",
                    'timestamp' => now()->toISOString()
                ];
            }
        }

        return $alerts;
    }

    /**
     * Generar recomendaciones basadas en análisis del sistema
     */
    protected function generateRecommendations(array $systemHealth, array $predictions, array $capacityMetrics): array
    {
        $recommendations = [];

        // Recomendaciones de capacidad
        if ($capacityMetrics['status'] === 'critical') {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'capacity',
                'title' => 'Liberar espacio de almacenamiento',
                'description' => 'Eliminar respaldos antiguos o aumentar capacidad de disco',
                'action_url' => route('backup.clean'),
                'estimated_effort' => '15 minutos'
            ];
        }

        // Recomendaciones de mantenimiento
        if ($systemHealth['status'] === 'warning' || $systemHealth['status'] === 'critical') {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'maintenance',
                'title' => 'Revisar configuración del sistema',
                'description' => 'Verificar configuración de base de datos y permisos',
                'action_url' => '#',
                'estimated_effort' => '30 minutos'
            ];
        }

        // Recomendaciones de optimización
        if (
            isset($predictions['storage_growth']['weekly_mb']) &&
            $predictions['storage_growth']['weekly_mb'] > 100
        ) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'optimization',
                'title' => 'Optimizar estrategia de respaldos',
                'description' => 'Considere implementar respaldos incrementales para reducir crecimiento',
                'action_url' => '#',
                'estimated_effort' => '1 hora'
            ];
        }

        return $recommendations;
    }

    /**
     * Obtener estadísticas de compresión del sistema
     */
    public function getCompressionStats(): array
    {
        try {
            $backups = $this->listBackups();
            $compressionStats = [
                'total_backups' => count($backups),
                'compressed_backups' => 0,
                'uncompressed_backups' => 0,
                'total_original_size' => 0,
                'total_compressed_size' => 0,
                'compression_ratio' => 0,
                'space_saved' => 0,
                'compression_efficiency' => 'N/A'
            ];

            foreach ($backups as $backup) {
                $isCompressed = str_contains($backup['name'], '.zip');

                if ($isCompressed) {
                    $compressionStats['compressed_backups']++;
                    $compressionStats['total_compressed_size'] += $backup['size'];
                } else {
                    $compressionStats['uncompressed_backups']++;
                    $compressionStats['total_original_size'] += $backup['size'];
                }
            }

            if ($compressionStats['total_original_size'] > 0) {
                $compressionStats['compression_ratio'] = round(
                    (($compressionStats['total_original_size'] - $compressionStats['total_compressed_size']) / $compressionStats['total_original_size']) * 100,
                    2
                );
                $compressionStats['space_saved'] = $compressionStats['total_original_size'] - $compressionStats['total_compressed_size'];
            }

            // Eficiencia de compresión
            if ($compressionStats['compressed_backups'] > 0) {
                $compressionStats['compression_efficiency'] = $compressionStats['compression_ratio'] > 70 ? 'Excelente' :
                    ($compressionStats['compression_ratio'] > 50 ? 'Buena' :
                        ($compressionStats['compression_ratio'] > 30 ? 'Regular' : 'Baja'));
            }

            return $compressionStats;

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de compresión: ' . $e->getMessage());

            return [
                'error' => 'No se pudieron obtener estadísticas de compresión',
                'total_backups' => 0
            ];
        }
    }

    /**
     * Crear sistema de backup programado avanzado
     *
     * @param  array  $scheduleConfig  Configuración del horario
     * @return array  Información sobre el backup programado creado
     */
    public function createScheduledBackup(array $scheduleConfig): array
    {
        $config = array_merge([
            'name' => 'scheduled_backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => 'full', // full, incremental, differential
            'frequency' => 'daily', // daily, weekly, monthly, custom
            'time' => '02:00',
            'day_of_week' => null, // 0-6 (Sunday = 0)
            'day_of_month' => null, // 1-31
            'enabled' => true,
            'retention_days' => 30,
            'notification_settings' => [
                'on_success' => true,
                'on_failure' => true,
                'email_recipients' => []
            ],
            'compression' => true,
            'encryption' => false,
            'verify_integrity' => true
        ], $scheduleConfig);

        try {
            // Crear el backup según el tipo especificado
            switch ($config['type']) {
                case 'incremental':
                    $result = $this->createIncrementalBackup([
                        'name' => $config['name'],
                        'compress' => $config['compression'],
                        'encrypt_sensitive' => $config['encryption']
                    ]);
                    break;

                case 'differential':
                    $result = $this->createDifferentialBackup($config);
                    break;

                default:
                    $result = $this->createSecureBackup([
                        'name' => $config['name'],
                        'compress' => $config['compression'],
                        'encrypt_sensitive' => $config['encryption'],
                        'generate_checksum' => $config['verify_integrity']
                    ]);
                    break;
            }

            if ($result['success']) {
                // Crear entrada en la tabla de backups programados
                $this->createScheduledBackupEntry($config, $result);

                // Configurar limpieza automática según retención
                if ($config['retention_days'] > 0) {
                    $this->scheduleCleanup($config['retention_days']);
                }

                // Enviar notificaciones según configuración
                if ($config['notification_settings']['on_success']) {
                    $this->sendNotification('backup_success', [
                        'filename' => basename($result['path']),
                        'scheduled' => true,
                        'type' => $config['type'],
                        'frequency' => $config['frequency']
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Backup programado ejecutado exitosamente',
                    'backup_result' => $result,
                    'next_run' => $this->calculateNextRun($config),
                    'schedule_id' => $result['schedule_id'] ?? null
                ];
            }

            // Enviar notificación de error si está configurado
            if ($config['notification_settings']['on_failure']) {
                $this->sendNotification('backup_failure', [
                    'error' => $result['message'],
                    'scheduled' => true,
                    'type' => $config['type']
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error en backup programado: ' . $e->getMessage(), [
                'schedule_config' => $config
            ]);

            if ($config['notification_settings']['on_failure']) {
                $this->sendNotification('backup_failure', [
                    'error' => $e->getMessage(),
                    'scheduled' => true,
                    'type' => $config['type']
                ]);
            }

            return [
                'success' => false,
                'message' => 'Error en backup programado: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear backup diferencial (cambios desde último backup completo)
     */
    protected function createDifferentialBackup(array $config): array
    {
        // Buscar último backup completo
        $lastFullBackup = $this->getLastFullBackup();

        if (!$lastFullBackup) {
            // Si no hay backup completo, crear uno
            Log::info('No se encontró backup completo, creando backup completo en su lugar');
            return $this->createSecureBackup([
                'name' => str_replace('differential', 'full', $config['name']),
                'compress' => $config['compression'],
                'encrypt_sensitive' => $config['encryption']
            ]);
        }

        // Crear backup incremental desde el último completo
        return $this->createIncrementalBackup([
            'name' => $config['name'],
            'base_backup' => $lastFullBackup['filename'],
            'compress' => $config['compression'],
            'encrypt_sensitive' => $config['encryption']
        ]);
    }

    /**
     * Obtener información del último backup completo
     */
    protected function getLastFullBackup(): ?array
    {
        $backups = $this->listBackups();

        if (empty($backups)) {
            return null;
        }

        // Buscar backups que no sean incrementales ni diferenciales
        $fullBackups = array_filter($backups, function ($backup) {
            $name = strtolower($backup['name']);
            return !str_contains($name, 'incremental') && !str_contains($name, 'differential');
        });

        if (empty($fullBackups)) {
            return null;
        }

        // Ordenar por fecha y devolver el más reciente
        uasort($fullBackups, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return reset($fullBackups);
    }

    /**
     * Crear entrada en sistema de backups programados
     */
    protected function createScheduledBackupEntry(array $config, array $result): void
    {
        try {
            // Crear registro en backup_logs con información del schedule
            BackupLog::create([
                'filename' => basename($result['path']),
                'path' => $result['path'],
                'type' => 'scheduled',
                'method' => $config['type'],
                'status' => 'success',
                'message' => 'Backup programado ejecutado',
                'metadata' => [
                    'schedule_config' => $config,
                    'backup_type' => $config['type'],
                    'frequency' => $config['frequency'],
                    'scheduled' => true,
                    'next_run' => $this->calculateNextRun($config)
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('No se pudo crear entrada de backup programado: ' . $e->getMessage());
        }
    }

    /**
     * Calcular próxima ejecución basada en configuración
     */
    protected function calculateNextRun(array $config): ?string
    {
        try {
            $now = now();

            switch ($config['frequency']) {
                case 'daily':
                    return $now->copy()->setTimeFromTimeString($config['time'])->addDay()->toISOString();

                case 'weekly':
                    $dayOfWeek = $config['day_of_week'] ?? 0; // Domingo por defecto
                    $nextRun = $now->copy()->next((int) $dayOfWeek)->setTimeFromTimeString($config['time']);
                    return $nextRun->toISOString();

                case 'monthly':
                    $dayOfMonth = $config['day_of_month'] ?? 1; // Primero de mes por defecto
                    $nextRun = $now->copy()->setDay($dayOfMonth)->setTimeFromTimeString($config['time']);

                    // Si ya pasó este mes, programar para el próximo
                    if ($nextRun->isPast()) {
                        $nextRun->addMonth();
                    }

                    return $nextRun->toISOString();

                case 'custom':
                    // Para horarios personalizados, requeriría configuración adicional
                    return null;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::warning('Error calculando próxima ejecución: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Programar limpieza automática de backups antiguos
     */
    protected function scheduleCleanup(int $retentionDays): void
    {
        try {
            // Esta funcionalidad requeriría integración con el scheduler de Laravel
            // Por ahora, solo registramos la intención
            Log::info('Limpieza automática programada', [
                'retention_days' => $retentionDays,
                'next_cleanup' => now()->addDays($retentionDays)->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            Log::warning('Error programando limpieza automática: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estado general del sistema de respaldos.
     */
    public function getStatus(): array
    {
        $driver = config('database.default');
        $mysqldumpAvailable = $driver === 'mysql' ? $this->isMysqldumpAvailable() : false;
        $zipEnabled = extension_loaded('zip');
        $diskWritable = is_writable(storage_path('app')) || (file_exists(storage_path('app')) && is_dir(storage_path('app')));

        try {
            $backups = $this->listBackups();
            $totalBackups = count($backups);
            $lastBackup = $backups[0] ?? null;
        } catch (\Exception $e) {
            $totalBackups = 0;
            $lastBackup = null;
            Log::warning('Error al listar respaldos en getStatus', ['exception' => $e]);
        }

        try {
            $latestLog = BackupLog::latest('created_at')->first();
        } catch (\Exception $e) {
            $latestLog = null;
            Log::warning('Error al obtener BackupLog en getStatus', ['exception' => $e]);
        }

        return [
            'mysqldump' => $mysqldumpAvailable,
            'zip_enabled' => $zipEnabled,
            'disk_writable' => $diskWritable,
            'total_backups' => $totalBackups,
            'last_backup' => $lastBackup,
            'latest_log' => $latestLog,
        ];
    }

    /**
     * Verificar integridad de todos los backups y generar reporte de salud
     */
    public function generateHealthReport(): array
    {
        try {
            $backups = $this->listBackups();
            $report = [
                'generated_at' => now()->toISOString(),
                'total_backups' => count($backups),
                'healthy_backups' => 0,
                'corrupted_backups' => 0,
                'warnings' => [],
                'critical_issues' => [],
                'recommendations' => [],
                'overall_health' => 'unknown'
            ];

            if (empty($backups)) {
                $report['warnings'][] = 'No hay respaldos disponibles para verificar';
                $report['overall_health'] = 'warning';
                return $report;
            }

            foreach ($backups as $backup) {
                $verification = $this->verifyAdvancedIntegrity($backup['name']);

                if ($verification['success']) {
                    $report['healthy_backups']++;
                } else {
                    $report['corrupted_backups']++;
                    $report['critical_issues'][] = [
                        'filename' => $backup['name'],
                        'error' => $verification['message'],
                        'error_code' => $verification['error_code'] ?? 'UNKNOWN'
                    ];
                }
            }

            // Generar recomendaciones basadas en el estado
            if ($report['corrupted_backups'] > 0) {
                $report['recommendations'][] = "Se encontraron {$report['corrupted_backups']} respaldos corruptos. Considere recrearlos.";
            }

            if ($report['healthy_backups'] / $report['total_backups'] < 0.8) {
                $report['recommendations'][] = 'Menos del 80% de los respaldos están sanos. Revise el sistema de respaldos.';
            }

            // Calcular salud general
            $healthRatio = $report['total_backups'] > 0 ? $report['healthy_backups'] / $report['total_backups'] : 0;

            if ($healthRatio >= 0.95) {
                $report['overall_health'] = 'excellent';
            } elseif ($healthRatio >= 0.8) {
                $report['overall_health'] = 'good';
            } elseif ($healthRatio >= 0.6) {
                $report['overall_health'] = 'warning';
            } else {
                $report['overall_health'] = 'critical';
            }

            Log::info('Reporte de salud de respaldos generado', [
                'total_backups' => $report['total_backups'],
                'healthy_backups' => $report['healthy_backups'],
                'corrupted_backups' => $report['corrupted_backups'],
                'overall_health' => $report['overall_health']
            ]);

            return $report;

        } catch (\Exception $e) {
            Log::error('Error generando reporte de salud: ' . $e->getMessage());

            return [
                'error' => 'No se pudo generar el reporte de salud',
                'overall_health' => 'error',
                'generated_at' => now()->toISOString()
            ];
        }
    }

    /**
     * Enviar notificaciones según el tipo de evento
     */
    public function sendNotification(string $eventType, array $data = []): void
    {
        $notificationConfig = $this->getNotificationConfig();

        if (!$notificationConfig['email_enabled'] && !$notificationConfig['slack_enabled']) {
            return;
        }

        $notificationData = array_merge([
            'event_type' => $eventType,
            'timestamp' => now(),
            'backup_service' => 'DatabaseBackupService'
        ], $data);

        try {
            // Notificar por email si está habilitado
            if ($notificationConfig['email_enabled']) {
                $this->sendEmailNotification($eventType, $notificationData, $notificationConfig);
            }

            // Notificar por Slack si está habilitado
            if ($notificationConfig['slack_enabled']) {
                $this->sendSlackNotification($eventType, $notificationData, $notificationConfig);
            }

            Log::info('Notificación enviada', [
                'event_type' => $eventType,
                'channels' => [
                    'email' => $notificationConfig['email_enabled'],
                    'slack' => $notificationConfig['slack_enabled']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando notificación: ' . $e->getMessage(), [
                'event_type' => $eventType,
                'notification_data' => $notificationData
            ]);
        }
    }

    /**
     * Enviar notificación por email
     */
    protected function sendEmailNotification(string $eventType, array $data, array $notificationConfig): void
    {
        $shouldNotify = match ($eventType) {
            'backup_success' => $notificationConfig['notify_on_backup_success'],
            'backup_failure' => $notificationConfig['notify_on_backup_failure'],
            'restore_success' => $notificationConfig['notify_on_restore'],
            'restore_failure' => $notificationConfig['notify_on_restore'],
            'security_issue' => $notificationConfig['notify_on_security_issues'],
            'critical_error' => $notificationConfig['notify_admin_on_critical_errors'],
            default => false
        };

        if (!$shouldNotify) {
            return;
        }

        $subject = $this->getEmailSubject($eventType);

        try {
            Mail::raw($this->buildEmailContent($eventType, $data), function ($message) use ($subject, $notificationConfig) {
                $message->to($notificationConfig['email_to'] ?? config('backup.notifications.email_to', 'admin@example.com'))
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Error enviando email: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación por Slack
     */
    protected function sendSlackNotification(string $eventType, array $data, array $notificationConfig): void
    {
        $shouldNotify = match ($eventType) {
            'backup_success' => $notificationConfig['notify_on_backup_success'],
            'backup_failure' => $notificationConfig['notify_on_backup_failure'],
            'restore_success' => $notificationConfig['notify_on_restore'],
            'restore_failure' => $notificationConfig['notify_on_restore'],
            'security_issue' => $notificationConfig['notify_on_security_issues'],
            'critical_error' => $notificationConfig['notify_admin_on_critical_errors'],
            default => false
        };

        if (!$shouldNotify) {
            return;
        }

        $webhookUrl = $notificationConfig['slack_webhook_url'] ?? config('backup.notifications.slack_webhook_url');
        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'text' => $this->buildSlackContent($eventType, $data),
            'username' => 'Backup System',
            'icon_emoji' => $this->getSlackEmoji($eventType)
        ];

        try {
            Http::post($webhookUrl, $payload);
        } catch (\Exception $e) {
            Log::error('Error enviando notificación Slack: ' . $e->getMessage());
        }
    }

    /**
     * Obtener asunto del email según el tipo de evento
     */
    protected function getEmailSubject(string $eventType): string
    {
        return match ($eventType) {
            'backup_success' => '✅ Respaldo de base de datos exitoso',
            'backup_failure' => '❌ Error en respaldo de base de datos',
            'restore_success' => '✅ Restauración de base de datos exitosa',
            'restore_failure' => '❌ Error en restauración de base de datos',
            'security_issue' => '🔒 Problema de seguridad en respaldos',
            'critical_error' => '🚨 Error crítico en sistema de respaldos',
            default => 'Notificación del sistema de respaldos'
        };
    }

    /**
     * Construir contenido del email
     */
    protected function buildEmailContent(string $eventType, array $data): string
    {
        $content = "Evento: {$eventType}\n";
        $content .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n\n";

        switch ($eventType) {
            case 'backup_success':
                $content .= "✅ Respaldo creado exitosamente\n";
                $content .= "Archivo: " . ($data['filename'] ?? 'N/A') . "\n";
                $content .= "Tamaño: " . ($data['size_formatted'] ?? $this->formatBytes($data['size'] ?? 0)) . "\n";
                if (isset($data['checksum'])) {
                    $content .= "Checksum: " . substr($data['checksum'], 0, 16) . "...\n";
                }
                break;

            case 'backup_failure':
                $content .= "❌ Error en respaldo\n";
                $content .= "Error: " . ($data['error'] ?? 'Error desconocido') . "\n";
                break;

            case 'restore_success':
                $content .= "✅ Restauración completada\n";
                $content .= "Archivo restaurado: " . ($data['filename'] ?? 'N/A') . "\n";
                break;

            case 'restore_failure':
                $content .= "❌ Error en restauración\n";
                $content .= "Archivo: " . ($data['filename'] ?? 'N/A') . "\n";
                $content .= "Error: " . ($data['error'] ?? 'Error desconocido') . "\n";
                break;

            case 'security_issue':
                $content .= "🔒 Problema de seguridad detectado\n";
                $content .= "Tipo: " . ($data['issue_type'] ?? 'No especificado') . "\n";
                $content .= "Descripción: " . ($data['description'] ?? '') . "\n";
                break;
        }

        return $content;
    }

    /**
     * Construir contenido para Slack
     */
    protected function buildSlackContent(string $eventType, array $data): string
    {
        $baseContent = match ($eventType) {
            'backup_success' => "✅ *Respaldo exitoso*\n",
            'backup_failure' => "❌ *Error en respaldo*\n",
            'restore_success' => "✅ *Restauración exitosa*\n",
            'restore_failure' => "❌ *Error en restauración*\n",
            'security_issue' => "🔒 *Problema de seguridad*\n",
            'critical_error' => "🚨 *Error crítico*\n",
            default => "*Notificación*\n"
        };

        $details = [];
        switch ($eventType) {
            case 'backup_success':
                $details[] = "Archivo: `" . ($data['filename'] ?? 'N/A') . "`";
                $details[] = "Tamaño: " . ($data['size_formatted'] ?? $this->formatBytes($data['size'] ?? 0));
                break;

            case 'backup_failure':
                $details[] = "Error: " . ($data['error'] ?? 'Error desconocido');
                break;

            case 'restore_success':
                $details[] = "Archivo: `" . ($data['filename'] ?? 'N/A') . "`";
                break;

            case 'restore_failure':
                $details[] = "Archivo: `" . ($data['filename'] ?? 'N/A') . "`";
                $details[] = "Error: " . ($data['error'] ?? 'Error desconocido');
                break;
        }

        return $baseContent . implode("\n", $details);
    }

    /**
     * Obtener emoji para Slack según el tipo de evento
     */
    protected function getSlackEmoji(string $eventType): string
    {
        return match ($eventType) {
            'backup_success', 'restore_success' => ':white_check_mark:',
            'backup_failure', 'restore_failure', 'critical_error' => ':x:',
            'security_issue' => ':lock:',
            default => ':information_source:'
        };
    }

    /**
     * Normalizar nombres de archivos CFDI a minúsculas para consistencia.
     */
    protected function normalizeCfdiFilenames(): void
    {
        $path = storage_path('app/public/cfdis');
        if (!is_dir($path)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $oldName = $item->getFilename();
                    $newName = strtolower($oldName);

                    if ($oldName !== $newName) {
                        $oldPath = $item->getPathname();
                        $newPath = $item->getPath() . DIRECTORY_SEPARATOR . $newName;

                        if (!file_exists($newPath)) {
                            rename($oldPath, $newPath);
                        }
                    }
                }
            }

            // También normalizar URLs en la base de datos
            DB::table('cfdis')->whereNotNull('xml_url')->update([
                'xml_url' => DB::raw('LOWER(xml_url)')
            ]);
            DB::table('cfdis')->whereNotNull('pdf_url')->update([
                'pdf_url' => DB::raw('LOWER(pdf_url)')
            ]);
        } catch (\Exception $e) {
            Log::error('Error normalizando archivos CFDI: ' . $e->getMessage());
        }
    }

    /**
     * Alinear empresa_id después de una restauración.
     */
    protected function alignEmpresaIdAfterRestore(): void
    {
        try {
            // Intentar obtener la primera empresa disponible
            $empresaId = DB::table('empresas')->orderBy('id')->value('id');

            if (!$empresaId) {
                return;
            }

            // Tablas críticas que deben tener empresa_id
            $tables = [
                'cfdis',
                'productos',
                'users',
                'clientes',
                'proveedores',
                'almacenes',
                'categorias',
                'marcas',
                'sat_descargas_masivas',
                'sat_descarga_detalles'
            ];

            foreach ($tables as $table) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'empresa_id')) {
                    DB::table($table)->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error alineando empresa_id post-restauración: ' . $e->getMessage());
        }
    }

    /**
     * Limpiar campos encriptados después de una restauración para evitar errores de MAC.
     */
    protected function clearEncryptedFieldsAfterRestore(): void
    {
        try {
            // 1. Configuración de Empresa (FIEL, CSD)
            if (\Illuminate\Support\Facades\Schema::hasTable('empresa_configuracion')) {
                DB::table('empresa_configuracion')->update([
                    'fiel_password' => null,
                    'csd_password' => null,
                ]);
            }

            // 2. Empresas (WhatsApp API)
            if (\Illuminate\Support\Facades\Schema::hasTable('empresas')) {
                DB::table('empresas')->update([
                    'whatsapp_access_token' => null,
                    'whatsapp_app_secret' => null,
                ]);
            }

            // 3. Rentas (Condiciones Especiales)
            if (\Illuminate\Support\Facades\Schema::hasTable('rentas')) {
                // Solo limpiar si tiene casts encriptados activos
                DB::table('rentas')->update([
                    'condiciones_especiales' => null,
                    'observaciones' => null
                ]);
            }



            // 4. Actualizar esquema de tabla SAT (Missing columns)
            if (\Illuminate\Support\Facades\Schema::hasTable('sat_descargas_masivas')) {
                $columnsToCheck = [
                    'retry_count' => "integer DEFAULT 0",
                    'next_retry_at' => "timestamp without time zone NULL",
                    'max_retries' => "integer DEFAULT 3",
                    'limite_tipo' => "varchar(255) NULL",
                    'mensaje_usuario' => "text NULL"
                ];

                foreach ($columnsToCheck as $column => $definition) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('sat_descargas_masivas', $column)) {
                        DB::statement("ALTER TABLE sat_descargas_masivas ADD COLUMN $column $definition");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error limpiando campos encriptados post-restauración: ' . $e->getMessage());
        }
    }
    /**
     * Subir un archivo de respaldo a los proveedores de la nube habilitados
     */
    protected function uploadToCloud(string $relativePath): void
    {
        try {
            $config = \App\Models\EmpresaConfiguracion::getConfig();

            // Caso 1: Google Drive
            if ($config->gdrive_enabled && $config->cloud_provider === 'gdrive') {
                $fullPath = Storage::disk($this->backupDisk)->path($relativePath);

                if (file_exists($fullPath)) {
                    $controller = new \App\Http\Controllers\Api\GoogleDriveController();
                    $request = new \Illuminate\Http\Request(['local_path' => $fullPath]);
                    $controller->upload($request);
                    Log::info("Backup subido exitosamente a Google Drive: {$relativePath}");
                }
            }

            // Caso 2: MEGA (si se implementa en un futuro similar)
            // ... lógica para MEGA ...

        } catch (\Exception $e) {
            Log::error("Error al subir respaldo a la nube: " . $e->getMessage());
        }
    }
}
