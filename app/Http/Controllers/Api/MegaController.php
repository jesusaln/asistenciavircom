<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MegaService;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class MegaController extends Controller
{
    /**
     * Probar conexión con MEGA
     */
    public function testConnection(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'Email y password son requeridos'
            ]);
        }

        $mega = new MegaService($email, $password);
        $result = $mega->testConnection($email, $password);

        return response()->json($result);
    }

    /**
     * Listar archivos en MEGA
     */
    public function list(Request $request)
    {
        $folder = $request->input('folder', '/Backups');

        $config = EmpresaConfiguracion::getConfig();

        if (!$config || !$config->mega_enabled || !$config->mega_email) {
            return response()->json([
                'success' => false,
                'message' => 'MEGA no está configurado',
                'files' => []
            ]);
        }

        $password = $config->mega_password ? Crypt::decryptString($config->mega_password) : null;

        $mega = new MegaService($config->mega_email, $password);
        $result = $mega->list($folder);

        return response()->json($result);
    }

    /**
     * Descargar archivo de MEGA
     */
    public function download(Request $request)
    {
        $file = $request->input('file');

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Archivo no especificado']);
        }

        $config = EmpresaConfiguracion::getConfig();

        if (!$config || !$config->mega_enabled) {
            return response()->json(['success' => false, 'message' => 'MEGA no está configurado']);
        }

        $password = $config->mega_password ? Crypt::decryptString($config->mega_password) : null;

        $mega = new MegaService($config->mega_email, $password);

        // Descargar a directorio temporal
        $localPath = storage_path('app/temp/' . basename($file));
        $result = $mega->download($file, $localPath);

        if ($result['success'] && file_exists($localPath)) {
            return response()->download($localPath)->deleteFileAfterSend(true);
        }

        return response()->json($result);
    }

    /**
     * Eliminar archivo de MEGA
     */
    public function delete(Request $request)
    {
        $file = $request->input('file');

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'Archivo no especificado']);
        }

        $config = EmpresaConfiguracion::getConfig();

        if (!$config || !$config->mega_enabled) {
            return response()->json(['success' => false, 'message' => 'MEGA no está configurado']);
        }

        $password = $config->mega_password ? Crypt::decryptString($config->mega_password) : null;

        $mega = new MegaService($config->mega_email, $password);
        $result = $mega->delete($file);

        return response()->json($result);
    }

    /**
     * Subir respaldo a MEGA (llamado después de crear un backup)
     */
    public function upload(Request $request)
    {
        $localPath = $request->input('local_path');

        if (!$localPath || !file_exists($localPath)) {
            return response()->json(['success' => false, 'message' => 'Archivo local no existe']);
        }

        $config = EmpresaConfiguracion::getConfig();

        if (!$config || !$config->mega_enabled || !$config->mega_auto_backup) {
            return response()->json(['success' => false, 'message' => 'Auto-backup a MEGA no está habilitado']);
        }

        $password = $config->mega_password ? Crypt::decryptString($config->mega_password) : null;

        $mega = new MegaService($config->mega_email, $password);

        $remotePath = rtrim($config->mega_folder, '/') . '/' . basename($localPath);
        $result = $mega->upload($localPath, $remotePath);

        // Actualizar última sincronización
        if ($result['success']) {
            $config->mega_last_sync = now();
            $config->save();
        }

        return response()->json($result);
    }
}
