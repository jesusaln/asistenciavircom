<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class SistemaConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:edit configuracion_empresa');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mantenimiento' => 'nullable|boolean',
            'mensaje_mantenimiento' => 'nullable|string|max:500',
            'registro_usuarios' => 'nullable|boolean',
            'notificaciones_email' => 'nullable|boolean',
            'backup_automatico' => 'nullable|boolean',
            'frecuencia_backup' => 'nullable|integer|min:1|max:365',
            'retencion_backups' => 'nullable|integer|min:1|max:365',
            'backup_cloud_enabled' => 'nullable|boolean',
            'backup_tipo' => 'nullable|string|max:20',
            'backup_hora_completo' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion sistema', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $booleanos = ['mantenimiento', 'registro_usuarios', 'notificaciones_email', 'backup_automatico', 'backup_cloud_enabled'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración del sistema actualizada correctamente.');
    }

    /**
     * Actualizar configuración de respaldos cloud (MEGA)
     */
    public function updateRespaldos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mega_enabled' => 'nullable|boolean',
            'mega_email' => 'nullable|email|max:255',
            'mega_password' => 'nullable|string|max:255',
            'mega_folder' => 'nullable|string|max:255',
            'mega_auto_backup' => 'nullable|boolean',
            'mega_retention_days' => 'nullable|integer|min:1|max:365',
            'gdrive_enabled' => 'nullable|boolean',
            'gdrive_client_id' => 'nullable|string|max:255',
            'gdrive_client_secret' => 'nullable|string|max:255',
            'gdrive_folder_name' => 'nullable|string|max:255',
            'gdrive_auto_backup' => 'nullable|boolean',
            'cloud_provider' => 'nullable|string|in:none,gdrive,mega',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion respaldos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Convertir booleanos
        $booleanos = ['mega_enabled', 'mega_auto_backup', 'gdrive_enabled', 'gdrive_auto_backup'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        // Encriptar passwords si se proporcionan
        if (!empty($data['mega_password'])) {
            $data['mega_password'] = \Illuminate\Support\Facades\Crypt::encryptString($data['mega_password']);
        } else {
            unset($data['mega_password']);
        }

        if (!empty($data['gdrive_client_secret'])) {
            $data['gdrive_client_secret'] = \Illuminate\Support\Facades\Crypt::encryptString($data['gdrive_client_secret']);
        } else {
            unset($data['gdrive_client_secret']);
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de respaldos actualizada correctamente.');
    }

    /**
     * Obtener los logs del sistema para la Bitácora General
     */
    public function getLogs()
    {
        $logPath = storage_path('logs/laravel.log');

        // Si no existe laravel.log y el canal es daily, buscar el log de hoy
        if (!File::exists($logPath)) {
            $todayLog = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
            if (File::exists($todayLog)) {
                $logPath = $todayLog;
            } else {
                // Buscar el archivo más reciente en la carpeta de logs
                $files = File::glob(storage_path('logs/*.log'));
                if (!empty($files)) {
                    usort($files, function($a, $b) {
                        return filemtime($b) - filemtime($a);
                    });
                    $logPath = $files[0];
                }
            }
        }

        if (!File::exists($logPath)) {
            return response()->json(['logs' => 'No hay registros de bitácora disponibles (archivo no encontrado).']);
        }

        // Leer las últimas 500 líneas del log para no saturar
        // Usar tail si está disponible es más eficiente, pero file es más portable
        try {
            $lines = 500;
            $data = file($logPath);
            if ($data === false) {
                 return response()->json(['logs' => 'Error al leer el archivo de registros.']);
            }
            $logs = array_slice($data, -$lines);

            return response()->json([
                'logs' => implode("", array_reverse($logs))
            ]);
        } catch (\Exception $e) {
            return response()->json(['logs' => 'Error procesando bitácora: ' . $e->getMessage()]);
        }
    }

    /**
     * Limpiar los logs del sistema
     */
    public function clearLogs()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
            Log::info('Bitácora del sistema limpiada por el usuario: ' . auth()->user()->email);
            return response()->json(['success' => true, 'message' => 'Bitácora limpiada correctamente.']);
        }

        return response()->json(['success' => false, 'message' => 'No se encontró el archivo de bitácora.']);
    }
}
