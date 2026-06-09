<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Validation\Rules;
use App\Traits\ImageOptimizerTrait;

class SetupController extends Controller
{
    use ImageOptimizerTrait;
    /**
     * Muestra la pantalla de bienvenida e instalación.
     */
    public function index()
    {
        // Si ya existe un super-admin, no debería estar aquí
        $isInstalled = false;
        try {
            if (\Schema::hasTable('roles')) {
                $isInstalled = User::role('super-admin')->exists();
            }
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        if ($isInstalled) {
            return redirect()->route('login');
        }

        return Inertia::render('Setup/Index');
    }

    /**
     * Procesa la creación del primer Super Admin.
     */
    /**
     * Procesa la instalación completa del sistema.
     */
    public function store(Request $request)
    {
        // 1. Verificar Installation
        $isInstalled = false;
        try {
            if (\Schema::hasTable('roles')) {
                $isInstalled = User::role('super-admin')->exists();
            }
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        if ($isInstalled) {
            abort(403, 'El sistema ya está instalado.');
        }

        // 2. Validación
        $validated = $request->validate([
            // Usuario
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Empresa
            'empresa_nombre' => 'required|string|max:255',
            'empresa_rfc' => 'nullable|string|max:13',
            'empresa_cp' => 'nullable|string|max:10',
            'empresa_regimen' => 'nullable|string|max:255',
            'empresa_uso_cfdi' => 'nullable|string|max:5',
            'empresa_direccion' => 'required|string|max:500', // Calle
            'empresa_numero_exterior' => 'required|string|max:20',
            'empresa_colonia' => 'required|string|max:255',
            'empresa_municipio' => 'required|string|max:255',
            'empresa_estado' => 'required|string|max:255',
            'empresa_logo' => 'nullable|image|max:2048', // 2MB Max

            // Fiscal
            'iva_porcentaje' => 'required|numeric|min:0|max:100',
            'enable_isr' => 'boolean',
            'enable_retencion_iva' => 'boolean',
            'enable_retencion_isr' => 'boolean',
            'retencion_iva_default' => 'nullable|numeric|min:0|max:100',
            'retencion_isr_default' => 'nullable|numeric|min:0|max:100',

            // Almacén
            'almacen_nombre' => 'required|string|max:255',
        ]);

        try {
            \DB::beginTransaction();

            // A. Crear Super Admin
            $user = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['password']),
                'activo' => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            $user->assignRole($role);

            // Asegurar que todos los permisos existan y estén asignados
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'RolesAndPermissionsSeeder',
                '--force' => true
            ]);

            // B. Crear Empresa (Modelo)
            $empresa = \App\Models\Empresa::create([
                'nombre_razon_social' => $validated['empresa_nombre'],
                'rfc' => $validated['empresa_rfc'] ?? 'XAXX010101000',
                'regimen_fiscal' => $validated['empresa_regimen'] ?? '601',
                'uso_cfdi' => $validated['empresa_uso_cfdi'] ?? 'G03',
                'codigo_postal' => $validated['empresa_cp'] ?? '00000',
                'calle' => $validated['empresa_direccion'],
                'numero_exterior' => $validated['empresa_numero_exterior'],
                'colonia' => $validated['empresa_colonia'],
                'municipio' => $validated['empresa_municipio'],
                'estado' => $validated['empresa_estado'],
                'tipo_persona' => (strlen($validated['empresa_rfc'] ?? '') === 13) ? 'fisica' : 'moral',
                'pais' => 'México',
                'email' => $validated['admin_email'],
            ]);

            // Asignar empresa al usuario
            $user->empresa_id = $empresa->id;

            // C. Configurar EmpresaConfiguracion
            $config = new \App\Models\EmpresaConfiguracion();
            $config->empresa_id = $empresa->id;

            // Asignación directa para campos críticos
            $config->nombre_empresa = $validated['empresa_nombre'];

            $configData = [
                'rfc' => $validated['empresa_rfc'] ?? 'XAXX010101000',
                'calle' => $validated['empresa_direccion'],
                'iva_porcentaje' => $validated['iva_porcentaje'],
                'enable_isr' => $validated['enable_isr'] ?? false,
                'enable_retencion_iva' => $validated['enable_retencion_iva'] ?? false,
                'enable_retencion_isr' => $validated['enable_retencion_isr'] ?? false,
                'retencion_iva' => $validated['retencion_iva_default'] ?? 0,
                'retencion_isr' => $validated['retencion_isr_default'] ?? 0,
            ];

            // Manejo de Logo
            if ($request->hasFile('empresa_logo')) {
                $path = $this->saveImageAsWebP($request->file('empresa_logo'), 'logos');
                $configData['logo_path'] = $path;
            }

            $config->forceFill($configData);
            $config->save();

            // D. Crear o Actualizar Almacén Principal
            $almacen = \App\Models\Almacen::create([
                'empresa_id' => $empresa->id,
                'nombre' => $validated['almacen_nombre'],
                'ubicacion' => $validated['empresa_direccion'] ?? 'Matriz',
                'estado' => 'activo',
                'es_principal' => true,
            ]);

            // Asignar almacén al usuario
            $user->almacen_venta_id = $almacen->id;
            $user->almacen_compra_id = $almacen->id;
            $user->save();

            \DB::commit();

            // Login automático
            Auth::login($user);

            return redirect()->route('panel')->with('success', '¡Sistema instalado correctamente! Bienvenido.');

        } catch (\Exception $e) {
            \DB::rollBack();

            $errorMessage = 'Ocurrió un problema técnico al guardar la configuración. ';

            if (str_contains($e->getMessage(), 'SQLSTATE[23502]')) {
                // Extraer el nombre de la columna del mensaje de error
                preg_match('/column "(.*?)"/', $e->getMessage(), $matches);
                $column = $matches[1] ?? 'desconocida';
                $errorMessage = "Faltan datos obligatorios. El campo '{$column}' es requerido.";
            } elseif (str_contains($e->getMessage(), 'unique constraint') && str_contains($e->getMessage(), 'users_email_unique')) {
                $errorMessage = 'El correo electrónico ya está registrado por otro administrador.';
            } elseif (str_contains($e->getMessage(), 'unique constraint') && str_contains($e->getMessage(), 'almacenes_nombre_unique')) {
                $errorMessage = 'El nombre del almacén ya existe. Por favor utiliza un nombre diferente.';
            } else {
                \Log::error('Error de instalación: ' . $e->getMessage());
                $errorMessage .= 'Detalle técnico: ' . $e->getMessage();
            }

            return back()->withErrors(['message' => $errorMessage]);
        }
    }

    /**
     * Restaura el sistema desde un archivo de respaldo .zip
     */
    public function restoreBackup(Request $request)
    {
        // Verificar que el sistema no esté instalado
        $isInstalled = false;
        try {
            if (\Schema::hasTable('roles')) {
                $isInstalled = User::role('super-admin')->exists();
            }
        } catch (\Exception $e) {
            $isInstalled = false;
        }

        if ($isInstalled) {
            return response()->json([
                'success' => false,
                'message' => 'El sistema ya está instalado. No se puede restaurar un respaldo.'
            ], 403);
        }

        // Validar archivo
        $request->validate([
            'backup' => 'required|file|mimes:zip|max:102400', // 100MB max
        ]);

        $zipFile = $request->file('backup');
        $extractPath = storage_path('app/restore_temp_' . time());

        try {
            // Crear directorio temporal
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            // Extraer ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipFile->getRealPath()) !== true) {
                throw new \Exception('No se pudo abrir el archivo ZIP');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // Buscar archivo SQL (puede estar en raíz o en subcarpeta 'database/')
            $sqlFile = null;

            // Función recursiva para buscar archivos SQL
            $findSqlFile = function ($dir) use (&$findSqlFile) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..')
                        continue;
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $found = $findSqlFile($path);
                        if ($found)
                            return $found;
                    } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                        return $path;
                    }
                }
                return null;
            };

            $sqlFile = $findSqlFile($extractPath);

            if (!$sqlFile || !file_exists($sqlFile)) {
                throw new \Exception('No se encontró archivo SQL en el respaldo');
            }

            \Log::info('Archivo SQL encontrado: ' . $sqlFile);

            // Leer contenido para determinar el tipo de dump
            $sqlContent = file_get_contents($sqlFile);
            \Log::info('Tamaño del archivo SQL: ' . strlen($sqlContent) . ' bytes');

            // VALIDACIÓN: Verificar que el SQL parece ser un backup válido
            $isValidBackup = str_contains($sqlContent, 'PostgreSQL database dump') ||
                str_contains($sqlContent, 'CREATE TABLE') ||
                str_contains($sqlContent, 'INSERT INTO') ||
                str_contains($sqlContent, 'COPY public.');

            if (!$isValidBackup) {
                throw new \Exception('El archivo SQL no parece ser un backup válido de PostgreSQL');
            }

            \Log::info('Archivo SQL validado correctamente');

            // Detectar si es un dump nativo de pg_dump (contiene COPY ... FROM stdin)
            $isPgDumpNative = str_contains($sqlContent, 'COPY public.') && str_contains($sqlContent, 'FROM stdin');

            if ($isPgDumpNative) {
                \Log::info('Detectado formato pg_dump nativo, usando psql para restaurar...');

                // Obtener configuración de la BD
                $dbHost = config('database.connections.pgsql.host');
                $dbPort = config('database.connections.pgsql.port', 5432);
                $dbName = config('database.connections.pgsql.database');
                $dbUser = config('database.connections.pgsql.username');
                $dbPass = config('database.connections.pgsql.password');

                // PASO 0: Crear respaldo previo antes de destruir datos
                $preRestoreBackupPath = storage_path('app/private/backups/database');
                if (!is_dir($preRestoreBackupPath)) {
                    mkdir($preRestoreBackupPath, 0755, true);
                }
                $preRestoreFile = $preRestoreBackupPath . '/pre_restore_' . date('Y-m-d_H-i-s') . '.sql';

                \Log::info('Creando respaldo previo en: ' . $preRestoreFile);

                // Crear archivo temporal para password (más seguro que PGPASSWORD en comando)
                $pgpassFile = tempnam(sys_get_temp_dir(), 'pgpass');
                file_put_contents($pgpassFile, "{$dbHost}:{$dbPort}:{$dbName}:{$dbUser}:{$dbPass}");
                chmod($pgpassFile, 0600);

                $backupCommand = sprintf(
                    'PGPASSFILE=%s pg_dump -h %s -p %s -U %s %s > %s 2>&1',
                    escapeshellarg($pgpassFile),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbName),
                    escapeshellarg($preRestoreFile)
                );

                exec($backupCommand, $backupOutput, $backupReturnCode);
                unlink($pgpassFile); // Limpiar archivo de password

                if ($backupReturnCode === 0 && file_exists($preRestoreFile) && filesize($preRestoreFile) > 0) {
                    \Log::info('Respaldo previo creado exitosamente: ' . filesize($preRestoreFile) . ' bytes');
                } else {
                    \Log::warning('No se pudo crear respaldo previo, continuando de todas formas...');
                }

                // PASO 1: Limpiar la base de datos existente (DROP ALL TABLES)
                \Log::info('Limpiando base de datos existente...');
                try {
                    // Desactivar verificaciones de FK
                    \DB::statement('SET session_replication_role = replica;');
                } catch (\Exception $e) {
                    // Continuar sin ella
                }

                // Obtener todas las tablas y hacer DROP
                $tables = \DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                foreach ($tables as $table) {
                    try {
                        \DB::statement("DROP TABLE IF EXISTS public.\"{$table->tablename}\" CASCADE");
                    } catch (\Exception $e) {
                        \Log::warning("No se pudo dropear tabla {$table->tablename}: " . $e->getMessage());
                    }
                }

                // Dropear secuencias
                $sequences = \DB::select("SELECT sequencename FROM pg_sequences WHERE schemaname = 'public'");
                foreach ($sequences as $seq) {
                    try {
                        \DB::statement("DROP SEQUENCE IF EXISTS public.\"{$seq->sequencename}\" CASCADE");
                    } catch (\Exception $e) {
                        // Ignorar
                    }
                }

                \Log::info('Base de datos limpiada, ' . count($tables) . ' tablas eliminadas');

                // PASO 2: Ejecutar psql para restaurar (usando archivo temporal para password)
                $pgpassFile = tempnam(sys_get_temp_dir(), 'pgpass');
                file_put_contents($pgpassFile, "{$dbHost}:{$dbPort}:{$dbName}:{$dbUser}:{$dbPass}");
                chmod($pgpassFile, 0600);

                $command = sprintf(
                    'PGPASSFILE=%s psql -h %s -p %s -U %s -d %s -f %s 2>&1',
                    escapeshellarg($pgpassFile),
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbName),
                    escapeshellarg($sqlFile)
                );

                $output = [];
                $returnCode = 0;
                exec($command, $output, $returnCode);

                unlink($pgpassFile); // Limpiar archivo de password inmediatamente

                $outputStr = implode("\n", array_slice($output, -20)); // Últimas 20 líneas
                \Log::info("psql output (últimas líneas): $outputStr");

                if ($returnCode !== 0) {
                    \Log::warning("psql terminó con código $returnCode, algunos errores pueden haber ocurrido");
                }

                \Log::info('Restauración con psql completada');

                // PASO 3: Verificación post-restauración
                $userCount = User::count();
                $rolesCount = Role::count();

                \Log::info("Verificación post-restauración: $userCount usuarios, $rolesCount roles");

                if ($userCount === 0 && $rolesCount === 0) {
                    \Log::error('¡ALERTA! La restauración parece haber fallado - no hay usuarios ni roles');
                    throw new \Exception('La restauración falló - la base de datos quedó vacía. Revise el respaldo previo en storage/app/private/backups/database/');
                }
            } else {
                \Log::info('Formato SQL estándar detectado, ejecutando statements individualmente...');

                // Intentar desactivar verificaciones de foreign keys
                $replicationRoleSet = false;
                try {
                    \DB::statement('SET session_replication_role = replica;');
                    $replicationRoleSet = true;
                } catch (\Exception $e) {
                    \Log::warning('No se pudo desactivar replication_role: ' . $e->getMessage());
                    try {
                        \DB::statement('SET CONSTRAINTS ALL DEFERRED;');
                    } catch (\Exception $e2) {
                        \Log::warning('No se pudo diferir constraints: ' . $e2->getMessage());
                    }
                }

                // Dividir por statements y ejecutar
                $statements = array_filter(
                    array_map('trim', explode(";\n", $sqlContent)),
                    fn($s) => !empty($s) && $s !== '--' && !str_starts_with(trim($s), '--')
                );

                $successCount = 0;
                $errorCount = 0;

                foreach ($statements as $statement) {
                    $trimmed = trim($statement);
                    if (!empty($trimmed) && !str_starts_with($trimmed, '--')) {
                        try {
                            \DB::unprepared($trimmed);
                            $successCount++;
                        } catch (\Exception $stmtError) {
                            $errorCount++;
                            if (
                                !str_contains($stmtError->getMessage(), 'already exists') &&
                                !str_contains($stmtError->getMessage(), 'duplicate key')
                            ) {
                                \Log::warning('Statement fallido: ' . substr($trimmed, 0, 100) . '...');
                            }
                        }
                    }
                }

                \Log::info("Restauración SQL completada: $successCount éxitos, $errorCount errores");

                // Reactivar verificaciones
                if ($replicationRoleSet) {
                    try {
                        \DB::statement('SET session_replication_role = DEFAULT;');
                    } catch (\Exception $e) {
                        // Ignorar
                    }
                }
            }

            // Restaurar archivos de storage si existen
            // El backup contiene: storage/app/public/...
            // Debemos copiar ESA subcarpeta a storage/app/public/
            $storageBackupPath = $extractPath . '/storage/app/public';
            if (is_dir($storageBackupPath)) {
                $this->recursiveCopy($storageBackupPath, storage_path('app/public'));
            }

            // Limpiar directorio temporal
            $this->deleteDirectory($extractPath);

            \Log::info('Respaldo restaurado correctamente desde: ' . $zipFile->getClientOriginalName());

            return response()->json([
                'success' => true,
                'message' => '¡Respaldo restaurado correctamente! Redirigiendo al login...'
            ]);

        } catch (\Exception $e) {
            // Limpiar en caso de error
            if (is_dir($extractPath)) {
                $this->deleteDirectory($extractPath);
            }

            \Log::error('Error al restaurar respaldo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copia recursiva de directorios
     */
    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcPath = $src . '/' . $file;
                $dstPath = $dst . '/' . $file;

                if (is_dir($srcPath)) {
                    $this->recursiveCopy($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Elimina un directorio y todo su contenido
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
