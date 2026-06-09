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
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion respaldos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Convertir booleanos
        $booleanos = ['mega_enabled', 'mega_auto_backup'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        // Encriptar password si se proporciona
        if (!empty($data['mega_password'])) {
            $data['mega_password'] = \Illuminate\Support\Facades\Crypt::encryptString($data['mega_password']);
        } else {
            unset($data['mega_password']); // No sobrescribir si está vacío
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

        if (!File::exists($logPath)) {
            return response()->json(['logs' => 'No hay registros de bitácora disponibles.']);
        }

        // Leer las últimas 500 líneas del log para no saturar
        $lines = 500;
        $data = file($logPath);
        $logs = array_slice($data, -$lines);

        // Unir y devolver
        return response()->json([
            'logs' => implode("", array_reverse($logs))
        ]);
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
