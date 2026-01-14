<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleDriveService;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    /**
     * Obtener credenciales OAuth de la aplicación
     */
    private function getAppCredentials(): array
    {
        return [
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => url('/api/gdrive/callback')
        ];
    }

    /**
     * Iniciar flujo de autorización OAuth
     */
    public function auth()
    {
        $credentials = $this->getAppCredentials();

        if (!$credentials['client_id'] || !$credentials['client_secret']) {
            return view('gdrive-callback', [
                'success' => false,
                'message' => 'Google Drive no está configurado en el servidor. Contacta al administrador.'
            ]);
        }

        $service = new GoogleDriveService();

        // Generar estado con empresa_id para persistencia
        $config = EmpresaConfiguracion::getConfig();
        $state = base64_encode(json_encode(['empresa_id' => $config->empresa_id]));

        $authUrl = $service->getAuthUrl(
            $credentials['client_id'],
            $credentials['client_secret'],
            $credentials['redirect_uri'],
            $state
        );

        return redirect($authUrl);
    }

    /**
     * Callback de OAuth
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $error = $request->input('error');

        if ($error) {
            return view('gdrive-callback', ['success' => false, 'message' => $error]);
        }

        if (!$code) {
            return view('gdrive-callback', ['success' => false, 'message' => 'No se recibió código de autorización']);
        }

        $credentials = $this->getAppCredentials();

        if (!$credentials['client_id'] || !$credentials['client_secret']) {
            return view('gdrive-callback', ['success' => false, 'message' => 'Credenciales no configuradas']);
        }

        $service = new GoogleDriveService();
        $result = $service->exchangeCode(
            $credentials['client_id'],
            $credentials['client_secret'],
            $credentials['redirect_uri'],
            $code
        );

        if (!$result['success']) {
            return view('gdrive-callback', ['success' => false, 'message' => $result['message']]);
        }

        // Guardar tokens en la configuración
        $config = EmpresaConfiguracion::getConfig();
        Log::info('GDrive Callback - Empresa ID Resolved: ' . $config->empresa_id);
        $config->update([
            'gdrive_enabled' => true,
            'gdrive_access_token' => Crypt::encryptString($result['access_token']),
            'gdrive_refresh_token' => $result['refresh_token'] ? Crypt::encryptString($result['refresh_token']) : null,
            'gdrive_token_expires_at' => now()->addSeconds($result['expires_in'] ?? 3600),
            'cloud_provider' => 'gdrive'
        ]);

        EmpresaConfiguracion::clearCache();

        Log::info('Google Drive: Autorización completada exitosamente');

        return view('gdrive-callback', ['success' => true, 'message' => '¡Conectado! Puedes cerrar esta ventana.']);
    }

    /**
     * Desconectar Google Drive
     */
    public function disconnect()
    {
        $config = EmpresaConfiguracion::getConfig();
        $config->update([
            'gdrive_enabled' => false,
            'gdrive_access_token' => null,
            'gdrive_refresh_token' => null,
            'gdrive_folder_id' => null,
            'gdrive_token_expires_at' => null,
            'cloud_provider' => 'none'
        ]);

        EmpresaConfiguracion::clearCache();

        return response()->json(['success' => true, 'message' => 'Desconectado']);
    }

    /**
     * Inicializar servicio con tokens guardados
     */
    private function initializeService(): ?GoogleDriveService
    {
        $config = EmpresaConfiguracion::getConfig();

        if (!$config || !$config->gdrive_enabled || !$config->gdrive_access_token) {
            return null;
        }

        try {
            $accessToken = Crypt::decryptString($config->gdrive_access_token);
            $refreshToken = $config->gdrive_refresh_token ? Crypt::decryptString($config->gdrive_refresh_token) : null;

            $service = new GoogleDriveService();
            $credentials = $this->getAppCredentials();

            // Verificar si necesita refresh (hacerlo 5 minutos antes para mayor seguridad)
            if ($config->gdrive_token_expires_at && now()->addMinutes(5)->gte($config->gdrive_token_expires_at) && $refreshToken) {
                Log::info('Google Drive: Intentando refrescar token expirado o próximo a expirar.');
                $newToken = $service->refreshAccessToken(
                    $credentials['client_id'],
                    $credentials['client_secret'],
                    $refreshToken
                );

                if ($newToken['success']) {
                    $updateFields = [
                        'gdrive_access_token' => Crypt::encryptString($newToken['access_token']),
                        'gdrive_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600)
                    ];

                    if (!empty($newToken['refresh_token'])) {
                        $updateFields['gdrive_refresh_token'] = Crypt::encryptString($newToken['refresh_token']);
                    }

                    $config->update($updateFields);
                    $accessToken = $newToken['access_token'];
                    Log::info('Google Drive: Token refrescado exitosamente.');
                } else {
                    Log::error('Google Drive: Fallo al refrescar token - ' . ($newToken['message'] ?? 'Error desconocido'));
                }
            }

            $service->initialize(null, $accessToken, $refreshToken, $credentials['client_id'], $credentials['client_secret']);
            return $service;

        } catch (\Exception $e) {
            Log::error('Google Drive init error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Probar conexión
     */
    public function test()
    {
        $service = $this->initializeService();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Google Drive no está conectado'
            ]);
        }

        $result = $service->testConnection();

        if ($result['success']) {
            $config = EmpresaConfiguracion::getConfig();
            $config->update(['gdrive_last_sync' => now()]);
        }

        return response()->json($result);
    }

    /**
     * Listar archivos
     */
    public function list()
    {
        $service = $this->initializeService();

        if (!$service) {
            return response()->json(['success' => false, 'files' => []]);
        }

        $config = EmpresaConfiguracion::getConfig();
        return response()->json($service->listFiles($config->gdrive_folder_id));
    }

    /**
     * Subir archivo
     */
    public function upload(Request $request)
    {
        $localPath = $request->input('local_path');

        if (!$localPath || !file_exists($localPath)) {
            return response()->json(['success' => false, 'message' => 'Archivo no existe']);
        }

        $service = $this->initializeService();

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Google Drive no conectado']);
        }

        $config = EmpresaConfiguracion::getConfig();

        // Crear/obtener carpeta
        $folderId = $config->gdrive_folder_id;
        if (!$folderId) {
            $folderId = $service->getOrCreateFolder($config->gdrive_folder_name ?? 'CDD_Backups');
            $config->update(['gdrive_folder_id' => $folderId]);
        }

        $result = $service->upload($localPath, $folderId);

        if ($result['success']) {
            $config->update(['gdrive_last_sync' => now()]);

            // --- LIMPIEZA AUTOMÁTICA: Mantener solo los 10 más recientes ---
            try {
                $service->cleanupOldBackups(10, $folderId);
            } catch (\Exception $e) {
                Log::warning("Error en limpieza automática de GDrive: " . $e->getMessage());
            }
            // ---------------------------------------------------------------
        }

        return response()->json($result);
    }

    /**
     * Descargar archivo
     */
    public function download(Request $request)
    {
        $fileId = $request->input('file_id');

        if (!$fileId) {
            return response()->json(['success' => false, 'message' => 'File ID requerido']);
        }

        $service = $this->initializeService();

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Google Drive no conectado']);
        }

        $localPath = storage_path('app/temp/' . $fileId . '.zip');
        $result = $service->download($fileId, $localPath);

        if ($result['success'] && file_exists($localPath)) {
            return response()->download($localPath)->deleteFileAfterSend(true);
        }

        return response()->json($result);
    }

    /**
     * Eliminar archivo
     */
    public function delete(Request $request)
    {
        $fileId = $request->input('file_id');

        if (!$fileId) {
            return response()->json(['success' => false, 'message' => 'File ID requerido']);
        }

        $service = $this->initializeService();

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Google Drive no conectado']);
        }

        // --- REGLA DE PROTECCIÓN: No eliminar los 3 más recientes ---
        try {
            $listRes = $service->listFiles();
            if ($listRes['success']) {
                $files = $listRes['files'] ?? [];
                // Filtrar solo archivos (no carpetas) y ordenar por creación descendente
                $files = array_filter($files, fn($f) => !($f['is_folder'] ?? false));
                usort($files, fn($a, $b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));

                // Tomar los 3 primeros (más recientes)
                $recentIds = array_slice(array_map(fn($f) => $f['id'], $files), 0, 3);

                if (in_array($fileId, $recentIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Protección activada: No se pueden eliminar los 3 respaldos más recientes para garantizar la seguridad de tus datos.'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error al verificar protección de archivos en GDrive: " . $e->getMessage());
        }
        // -----------------------------------------------------------

        return response()->json($service->delete($fileId));
    }
}
