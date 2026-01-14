<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Exception;

class DatabaseBackupController extends Controller
{
    protected $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Mostrar la página principal de gestión de respaldos
     */
    public function index(Request $request)
    {
        try {
            $backups = $this->backupService->listBackups();

            // Formatear los datos de respaldos para el frontend
            $formattedBackups = collect($backups)->map(function ($backup) {
                $path = $backup['path'];
                $isFull = stripos($path, 'backups/application/') !== false || stripos($path, 'backups\\application\\') !== false;
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                return [
                    'name' => $backup['name'] ?? basename($backup['path']),
                    'path' => $path,
                    'size' => $backup['size'] ?? 0,
                    'created_at' => $backup['created_at'] ?? filemtime($path),
                    'compressed' => $this->isCompressed($path),
                    'kind' => $isFull ? 'full' : 'db',
                    'extension' => $ext,
                ];
            })->values()->toArray();

            // Estadísticas
            $totalBackups = count($formattedBackups);
            $totalSize = $this->calculateTotalSize($formattedBackups);

            // Estadísticas adicionales
            $compressedCount = collect($formattedBackups)->where('compressed', true)->count();
            $uncompressedCount = $totalBackups - $compressedCount;

            // Filtros y búsqueda
            $search = $request->get('search', '');
            if ($search) {
                $formattedBackups = collect($formattedBackups)->filter(function ($backup) use ($search) {
                    return stripos($backup['name'], $search) !== false;
                })->values()->toArray();
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $formattedBackups = collect($formattedBackups)->sortBy($sortBy, SORT_REGULAR, $sortDirection === 'desc')->values()->toArray();

            // Paginación
            $perPage = $request->get('per_page', 10);
            $currentPage = $request->get('page', 1);
            $paginatedBackups = collect($formattedBackups)->forPage($currentPage, $perPage);

            return Inertia::render('DatabaseBackup/Index', [
                'backups' => $paginatedBackups->values()->toArray(),
                'stats' => [
                    'total' => $totalBackups,
                    'total_size' => $totalSize,
                    'compressed' => $compressedCount,
                    'uncompressed' => $uncompressedCount,
                ],
                'filters' => [
                    'search' => $search,
                ],
                'sorting' => [
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection,
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'last_page' => ceil(count($formattedBackups) / $perPage),
                    'per_page' => $perPage,
                    'from' => (($currentPage - 1) * $perPage) + 1,
                    'to' => min($currentPage * $perPage, count($formattedBackups)),
                    'total' => count($formattedBackups),
                ],
                'mysqldump_available' => $this->backupService->isMysqldumpAvailable(),
                'total_backups' => $totalBackups,
                'total_size' => $totalSize
            ]);
        } catch (Exception $e) {
            Log::error('Error loading backup index: ' . $e->getMessage());

            return Inertia::render('DatabaseBackup/Index', [
                'backups' => [],
                'stats' => [
                    'total' => 0,
                    'total_size' => 0,
                    'compressed' => 0,
                    'uncompressed' => 0,
                ],
                'filters' => ['search' => ''],
                'sorting' => ['sort_by' => 'created_at', 'sort_direction' => 'desc'],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 10,
                    'from' => 0,
                    'to' => 0,
                    'total' => 0,
                ],
                'mysqldump_available' => false,
                'total_backups' => 0,
                'total_size' => 0
            ])->with('error', 'Error al cargar la lista de respaldos: ' . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo respaldo de la base de datos
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'compress' => 'boolean',
            'include_structure_only' => 'boolean'
        ], [
            'name.regex' => 'El nombre del respaldo solo puede contener letras, números, guiones, guiones bajos y puntos.',
            'name.max' => 'El nombre del respaldo no puede exceder 255 caracteres.'
        ]);

        try {
            // Generar nombre por defecto si no se proporciona
            $backupName = $validated['name'] ?: $this->generateDefaultBackupName();

            $result = $this->backupService->createBackup([
                'name' => $backupName,
                'compress' => $validated['compress'] ?? true,
                'include_structure_only' => $validated['include_structure_only'] ?? false
            ]);

            if ($result['success']) {
                return redirect()->route('backup.index')
                    ->with('success', $result['message'] . ' Tamaño: ' . $this->formatFileSize($result['size'] ?? 0));
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error creating backup: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al crear el respaldo. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Crear un respaldo completo (BD + archivos: fotos y módulos)
     */
    public function createFull(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'include' => 'array',
            'include.*' => 'string',
            'exclude' => 'array',
            'exclude.*' => 'string',
        ], [
            'name.regex' => 'El nombre del respaldo solo puede contener letras, números, guiones, guiones bajos y puntos.',
            'name.max' => 'El nombre del respaldo no puede exceder 255 caracteres.'
        ]);

        try {
            $backupName = $validated['name'] ?: ('app_backup_' . now()->format('Y-m-d_H-i-s'));

            $result = $this->backupService->createApplicationBackup([
                'name' => $backupName,
                'include_paths' => $validated['include'] ?? null,
                'exclude' => $validated['exclude'] ?? null,
            ]);

            if ($result['success']) {
                return redirect()->route('backup.index')
                    ->with('success', $result['message'] . ' Tamaño: ' . $this->formatFileSize($result['size'] ?? 0));
            }

            return redirect()->route('backup.index')->with('error', $result['message'] ?? 'Error creando respaldo.');
        } catch (Exception $e) {
            Log::error('Error creating full backup: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al crear el respaldo completo.');
        }
    }

    /**
     * Descargar un archivo de respaldo
     */
    public function download($path)
    {
        try {
            // Sanitizar el nombre del archivo
            $path = basename($path);

            // Validar que el archivo existe y es seguro
            if (!$this->backupService->backupExists($path)) {
                return redirect()->route('backup.index')->with('error', 'El archivo de respaldo no existe.');
            }

            $filePath = $this->backupService->getBackupPath($path);

            if (!file_exists($filePath)) {
                return redirect()->route('backup.index')->with('error', 'El archivo físico no se encuentra en el servidor.');
            }

            return response()->download($filePath, $path, [
                'Content-Type' => $this->getContentType($path),
                'Cache-Control' => 'no-cache, must-revalidate',
                'Expires' => 'Sat, 26 Jul 1997 05:00:00 GMT'
            ]);
        } catch (Exception $e) {
            Log::error('Error downloading backup: ' . $e->getMessage(), [
                'filename' => $path,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('backup.index')->with('error', 'Error al descargar el archivo de respaldo.');
        }
    }

    /**
     * Eliminar un archivo de respaldo
     */
    public function delete($path)
    {
        try {
            $path = basename($path);

            if (!$this->backupService->backupExists($path)) {
                return redirect()->route('backup.index')->with('error', 'El archivo de respaldo no existe.');
            }

            $result = $this->backupService->deleteBackup($path);

            if ($result['success']) {
                return redirect()->route('backup.index')
                    ->with('success', 'Respaldo eliminado exitosamente.');
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error deleting backup: ' . $e->getMessage(), [
                'filename' => $path,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('backup.index')->with('error', 'Error al eliminar el archivo de respaldo.');
        }
    }

    /**
     * Eliminar múltiples archivos de respaldo
     */
    public function deleteMultiple(Request $request)
    {
        $validated = $request->validate([
            'filenames' => 'required|array',
            'filenames.*' => 'string'
        ]);

        $filenames = $validated['filenames'];
        $deletedCount = 0;
        $errors = [];

        foreach ($filenames as $filename) {
            try {
                $filename = basename($filename);

                if (!$this->backupService->backupExists($filename)) {
                    $errors[] = "El archivo {$filename} no existe.";
                    continue;
                }

                $result = $this->backupService->deleteBackup($filename);

                if ($result['success']) {
                    $deletedCount++;
                } else {
                    $errors[] = "Error al eliminar {$filename}: " . $result['message'];
                }
            } catch (Exception $e) {
                $errors[] = "Error crítico al eliminar {$filename}: " . $e->getMessage();
                Log::error('Error in bulk delete: ' . $e->getMessage(), [
                    'filename' => $filename,
                    'user_id' => Auth::id()
                ]);
            }
        }

        $message = "Se eliminaron {$deletedCount} respaldo(s) exitosamente.";
        if (!empty($errors)) {
            $message .= " Errores: " . implode(", ", $errors);
        }

        return redirect()->route('backup.index')->with($deletedCount > 0 ? 'success' : 'error', $message);
    }


    /**
     * Restaurar tablas específicas desde un respaldo (granular restore)
     */
    public function granularRestore(Request $request, $filename)
    {
        $validated = $request->validate([
            'tables_to_restore' => 'array',
            'tables_to_restore.*' => 'string',
            'tables_to_exclude' => 'array',
            'tables_to_exclude.*' => 'string',
            'include_structure' => 'boolean',
            'include_data' => 'boolean',
            'dry_run' => 'boolean',
            'remap_table_names' => 'array',
            'where_conditions' => 'array'
        ]);

        try {
            $filename = basename($filename);

            if (!$this->backupService->backupExists($filename)) {
                return redirect()->route('backup.index')->with('error', 'El archivo de respaldo no existe.');
            }

            $result = $this->backupService->granularRestore($filename, [
                'tables_to_restore' => $validated['tables_to_restore'] ?? [],
                'tables_to_exclude' => $validated['tables_to_exclude'] ?? [],
                'include_structure' => $validated['include_structure'] ?? true,
                'include_data' => $validated['include_data'] ?? true,
                'dry_run' => $validated['dry_run'] ?? false,
                'remap_table_names' => $validated['remap_table_names'] ?? [],
                'where_conditions' => $validated['where_conditions'] ?? []
            ]);

            if ($result['success']) {
                $message = 'Restauración granular completada exitosamente.';

                if (isset($result['dry_run']) && $result['dry_run']) {
                    $message = 'Verificación de sintaxis completada (dry run).';
                } else {
                    $message .= ' Tablas procesadas: ' . ($result['tables_processed'] ?? 0);
                    $message .= ', Filas afectadas: ' . ($result['rows_affected'] ?? 0);
                }

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error in granular restore: ' . $e->getMessage(), [
                'filename' => $filename,
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error crítico durante la restauración granular. Verifique el estado de la base de datos.');
        }
    }

    /**
     * Restaurar la base de datos desde un respaldo
     */
    public function restore($path)
    {
        try {
            $path = basename($path);

            if (!$this->backupService->backupExists($path)) {
                return redirect()->route('backup.index')->with('error', 'El archivo de respaldo no existe.');
            }

            // Pre-respaldo lo maneja el servicio internamente
            $result = $this->backupService->restoreBackup($path);

            if ($result['success']) {
                $message = 'Base de datos restaurada exitosamente.';

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error restoring backup: ' . $e->getMessage(), [
                'filename' => $path,
                'user_id' => Auth::id()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error crítico durante la restauración. Verifique el estado de la base de datos.');
        }
    }

    /**
     * Limpiar respaldos antiguos
     */
    public function clean(Request $request)
    {
        $validated = $request->validate([
            'days_old' => 'required|integer|min:1|max:365'
        ], [
            'days_old.required' => 'Debe especificar la antigüedad de los archivos a eliminar.',
            'days_old.integer' => 'La antigüedad debe ser un número entero.',
            'days_old.min' => 'La antigüedad mínima es 1 día.',
            'days_old.max' => 'La antigüedad máxima es 365 días.'
        ]);

        try {
            $daysOld = $validated['days_old'];
            $result = $this->backupService->cleanOldBackups($daysOld);

            if ($result['success']) {
                $deletedCount = $result['deleted_count'] ?? 0;
                $freedSpace = $result['freed_space'] ?? 0;

                $message = "Se eliminaron {$deletedCount} respaldos antiguos";
                if ($freedSpace > 0) {
                    $message .= " liberando " . $this->formatFileSize($freedSpace) . " de espacio";
                }
                $message .= ".";

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error cleaning old backups: ' . $e->getMessage(), [
                'days_old' => $validated['days_old'],
                'user_id' => Auth::id()
            ]);

            return redirect()->route('backup.index')->with('error', 'Error al limpiar los respaldos antiguos.');
        }
    }

    /**
     * Obtener estadísticas de los respaldos (AJAX)
     */
    public function stats()
    {
        try {
            $backups = $this->backupService->listBackups();
            $formattedBackups = collect($backups)->map(function ($backup) {
                return [
                    'name' => $backup['name'] ?? basename($backup['path']),
                    'path' => $backup['path'],
                    'size' => $backup['size'] ?? 0,
                    'created_at' => $backup['created_at'] ?? filemtime($backup['path']),
                ];
            })->values()->toArray();

            $stats = [
                'total_backups' => count($formattedBackups),
                'total_size' => $this->calculateTotalSize($formattedBackups),
                'total_size_formatted' => $this->formatFileSize($this->calculateTotalSize($formattedBackups)),
                'oldest_backup' => $this->getOldestBackup($formattedBackups),
                'newest_backup' => $this->getNewestBackup($formattedBackups),
                'mysqldump_available' => $this->backupService->isMysqldumpAvailable(),
                'disk_space_available' => $this->backupService->getAvailableDiskSpace() ?? 0,
                'disk_space_available_formatted' => $this->formatFileSize($this->backupService->getAvailableDiskSpace() ?? 0)
            ];

            return response()->json($stats);
        } catch (Exception $e) {
            Log::error('Error getting backup stats: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * Verificar el estado de un respaldo específico (AJAX)
     */
    public function verify($filename)
    {
        try {
            $filename = basename($filename);
            $result = $this->backupService->verifyBackup($filename);
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Error verifying backup: ' . $e->getMessage(), ['filename' => $filename]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el respaldo'
            ], 500);
        }
    }

    /**
     * Crear un respaldo incremental basado en cambios detectados
     */
    public function createIncremental(Request $request)
    {
        try {
            $result = $this->backupService->createIncrementalBackup([
                'name' => $request->input('name', 'backup_incremental_' . now()->format('Y-m-d_H-i-s')),
                'compress' => $request->boolean('compress', true),
                'encrypt_sensitive' => $request->boolean('encrypt_sensitive', true),
                'generate_checksum' => $request->boolean('generate_checksum', true)
            ]);

            if (isset($result['skipped']) && $result['skipped']) {
                return redirect()->route('backup.index')
                    ->with('info', $result['message']);
            }

            if ($result['success']) {
                $message = $result['message'] . ' Tamaño: ' . $this->formatFileSize($result['size'] ?? 0);

                if (isset($result['changes_detected'])) {
                    $message .= ' Tablas modificadas: ' . $result['changes_detected'];
                }

                if (isset($result['encrypted']) && $result['encrypted']) {
                    $message .= ' (Datos sensibles encriptados)';
                }

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error creating incremental backup: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al crear el respaldo incremental. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Crear un respaldo seguro con opciones avanzadas
     */
    public function createSecure(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'encrypt_sensitive' => 'boolean',
            'generate_checksum' => 'boolean',
            'include_integrity_check' => 'boolean',
            'require_confirmation' => 'boolean',
            'confirmed' => 'boolean',
            'compress' => 'boolean'
        ], [
            'name.regex' => 'El nombre del respaldo solo puede contener letras, números, guiones, guiones bajos y puntos.',
            'name.max' => 'El nombre del respaldo no puede exceder 255 caracteres.'
        ]);

        try {
            $backupName = $validated['name'] ?: $this->generateDefaultBackupName();

            $result = $this->backupService->createSecureBackup([
                'name' => $backupName,
                'encrypt_sensitive' => $validated['encrypt_sensitive'] ?? true,
                'generate_checksum' => $validated['generate_checksum'] ?? true,
                'include_integrity_check' => $validated['include_integrity_check'] ?? true,
                'require_confirmation' => $validated['require_confirmation'] ?? true,
                'confirmed' => $validated['confirmed'] ?? false,
                'compress' => $validated['compress'] ?? true
            ]);

            if (isset($result['requires_confirmation']) && $result['requires_confirmation']) {
                return redirect()->route('backup.index')
                    ->with('warning', $result['message'])
                    ->with('show_confirmation_modal', true)
                    ->with('backup_options', $validated);
            }

            if ($result['success']) {
                $message = $result['message'] . ' Tamaño: ' . $this->formatFileSize($result['size'] ?? 0);
                if (isset($result['encrypted']) && $result['encrypted']) {
                    $message .= ' (Datos sensibles encriptados)';
                }
                if (isset($result['checksum'])) {
                    $message .= ' Checksum: ' . substr($result['checksum'], 0, 16) . '...';
                }

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error creating secure backup: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al crear el respaldo seguro. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Obtener estadísticas de seguridad del sistema de respaldos
     */
    public function securityStats()
    {
        try {
            $stats = $this->backupService->getSecurityStats();

            return response()->json($stats);
        } catch (Exception $e) {
            Log::error('Error getting security stats: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al obtener estadísticas de seguridad'
            ], 500);
        }
    }

    /**
     * Verificar integridad avanzada de un respaldo específico
     */
    public function verifyAdvanced($filename)
    {
        try {
            $filename = basename($filename);
            $result = $this->backupService->verifyAdvancedIntegrity($filename);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'filename' => $filename,
                        'checksums' => $result['checksums'] ?? null,
                        'verification_level' => $result['verification_level'] ?? 'basic',
                        'algorithms_verified' => $result['algorithms_verified'] ?? []
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UNKNOWN',
                'data' => [
                    'filename' => $filename,
                    'mismatches' => $result['mismatches'] ?? [],
                    'expected_checksums' => $result['expected_checksums'] ?? null,
                    'current_checksums' => $result['current_checksums'] ?? null
                ]
            ], $result['error_code'] === 'FILE_NOT_FOUND' ? 404 : 400);

        } catch (Exception $e) {
            Log::error('Error verifying advanced backup: ' . $e->getMessage(), [
                'filename' => $filename,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al verificar el respaldo'
            ], 500);
        }
    }

    /**
     * Generar reporte de salud completo del sistema de respaldos
     */
    public function healthReport()
    {
        try {
            $report = $this->backupService->generateHealthReport();

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (Exception $e) {
            Log::error('Error generating health report: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error generando reporte de salud',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos avanzados de monitoreo del sistema de respaldos
     */
    public function monitoring()
    {
        try {
            $monitoringData = $this->backupService->getAdvancedMonitoringData();

            return response()->json([
                'success' => true,
                'monitoring' => $monitoringData
            ]);

        } catch (Exception $e) {
            Log::error('Error getting monitoring data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo datos de monitoreo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear backup remoto con almacenamiento en la nube
     */
    public function createRemote(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'provider' => 'required|string|in:s3,gcs,azure',
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'sync_local' => 'boolean'
        ]);

        try {
            $result = $this->backupService->createRemoteBackup([
                'name' => $validated['name'] ?: 'remote_backup_' . now()->format('Y-m-d_H-i-s'),
                'compress' => $validated['compress'] ?? true,
                'encrypt_sensitive' => $validated['encrypt'] ?? false,
                'remote_provider' => $validated['provider'],
                'sync_local_and_remote' => $validated['sync_local'] ?? true
            ]);

            if ($result['success']) {
                $message = 'Backup remoto creado exitosamente.';

                if ($result['remote_path']) {
                    $message .= ' Archivo remoto: ' . $result['remote_path'];
                }

                if ($result['local_path']) {
                    $message .= ' (Copia local mantenida)';
                }

                return redirect()->route('backup.index')->with('success', $message);
            }

            return redirect()->route('backup.index')->with('error', $result['message']);
        } catch (Exception $e) {
            Log::error('Error creating remote backup: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al crear el backup remoto. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Listar backups remotos desde almacenamiento en la nube
     */
    public function listRemote()
    {
        try {
            $remoteBackups = $this->backupService->listRemoteBackups();

            return response()->json([
                'success' => true,
                'remote_backups' => $remoteBackups
            ]);

        } catch (Exception $e) {
            Log::error('Error listing remote backups: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error listando backups remotos'
            ], 500);
        }
    }

    /**
     * Descargar backup desde almacenamiento remoto
     */
    public function downloadRemote($remotePath)
    {
        try {
            $localPath = $this->backupService->downloadFromRemote($remotePath);

            if (!$localPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error descargando archivo remoto'
                ], 404);
            }

            return response()->download($localPath, basename($remotePath));

        } catch (Exception $e) {
            Log::error('Error downloading remote backup: ' . $e->getMessage(), [
                'remote_path' => $remotePath
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error descargando backup remoto'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de compresión del sistema
     */
    public function compressionStats()
    {
        try {
            $stats = $this->backupService->getCompressionStats();

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error getting compression stats: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo estadísticas de compresión'
            ], 500);
        }
    }

    /**
     * Subir un archivo de respaldo
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|file|max:102400', // 100MB máximo, sin restricción de mimes para mayor flexibilidad
            'overwrite' => 'boolean'
        ], [
            'backup_file.required' => 'Debe seleccionar un archivo de respaldo.',
            'backup_file.file' => 'El archivo seleccionado no es válido.',
            'backup_file.max' => 'El archivo no puede ser mayor a 100MB.'
        ]);

        // Validación adicional de extensión para mayor seguridad
        $file = $request->file('backup_file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Extensiones permitidas (más flexibles)
        $allowedExtensions = ['sql', 'zip', 'gz', 'tar', 'dbsql', 'db', 'bak', 'backup'];

        if (!empty($extension) && !in_array($extension, $allowedExtensions)) {
            return redirect()->route('backup.index')->with('error', 'Tipo de archivo no permitido. Extensiones válidas: ' . implode(', ', $allowedExtensions));
        }

        try {
            $file = $request->file('backup_file');
            $originalName = $file->getClientOriginalName();

            // Verificar si ya existe un archivo con el mismo nombre
            if (!$validated['overwrite'] && $this->backupService->backupExists($originalName)) {
                return redirect()->route('backup.index')->with('error', 'Ya existe un archivo con el nombre "' . $originalName . '". Use la opción de sobrescribir si desea reemplazarlo.');
            }

            // Usar el mismo disco y ruta que emplea el servicio de backups
            $disk = config('backup.disk', 'local');
            $path = rtrim(config('backup.path', 'backups/database/'), '/') . '/';

            // Mover el archivo al directorio de respaldos
            Storage::disk($disk)->putFileAs($path, $file, $originalName);

            // Verificar que el archivo se guardó correctamente
            $relativePath = $path . $originalName;
            if (!Storage::disk($disk)->exists($relativePath)) {
                return redirect()->route('backup.index')->with('error', 'Error al guardar el archivo en el servidor.');
            }

            $fileSize = Storage::disk($disk)->size($relativePath);

            return redirect()->route('backup.index')
                ->with('success', 'Archivo de respaldo subido exitosamente: ' . $originalName . ' (' . $this->formatFileSize($fileSize) . ')');

        } catch (Exception $e) {
            Log::error('Error uploading backup file: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'filename' => $request->file('backup_file')?->getClientOriginalName()
            ]);

            return redirect()->route('backup.index')
                ->with('error', 'Error interno al subir el archivo de respaldo. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Obtener información detallada de un respaldo (AJAX)
     */
    public function info($filename)
    {
        try {
            $filename = basename($filename);
            $info = $this->backupService->getBackupInfo($filename);
            return response()->json($info);
        } catch (Exception $e) {
            Log::error('Error getting backup info: ' . $e->getMessage(), ['filename' => $filename]);

            return response()->json([
                'error' => 'Error al obtener información del respaldo'
            ], 500);
        }
    }

    /**
     * Métodos utilitarios privados
     */
    private function generateDefaultBackupName(): string
    {
        return 'backup_' . now()->format('Y-m-d_H-i-s');
    }

    private function calculateTotalSize(array $backups): int
    {
        return array_sum(array_column($backups, 'size'));
    }

    private function getOldestBackup(array $backups): ?array
    {
        if (empty($backups)) return null;

        return collect($backups)->sortBy('created_at')->first();
    }

    private function getNewestBackup(array $backups): ?array
    {
        if (empty($backups)) return null;

        return collect($backups)->sortByDesc('created_at')->first();
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }

    private function getContentType(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'sql' => 'application/sql',
            'zip' => 'application/zip',
            'gz' => 'application/gzip',
            'tar' => 'application/x-tar',
            default => 'application/octet-stream'
        };
    }

    private function isCompressed(string $filename): bool
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['zip', 'gz', 'tar', '7z']);
    }
}
