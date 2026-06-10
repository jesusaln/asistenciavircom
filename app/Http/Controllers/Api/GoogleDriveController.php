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
        // Primero intentamos obtener de la configuración (config/services.php -> .env)
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');

        // Si no están en .env, intentamos obtener de la base de datos
        if (empty($clientId) || empty($clientSecret)) {
            $config = EmpresaConfiguracion::getConfig();
            $clientId = $clientId ?: ($config->gdrive_client_id ?? null);

            if (empty($clientSecret) && !empty($config->gdrive_client_secret)) {
                try {
                    // Intentamos desencriptar (para mayor seguridad)
                    $clientSecret = \Illuminate\Support\Facades\Crypt::decryptString($config->gdrive_client_secret);
                } catch (\Exception $e) {
                    // Si falla, tomamos el valor tal cual (compatibilidad con texto plano)
                    $clientSecret = $config->gdrive_client_secret;
                }
            }
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
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

        // Generar estado seguro con token CSRF y empresa_id
        $config = EmpresaConfiguracion::getConfig();
        $csrfToken = \Illuminate\Support\Str::random(40);
        
        // Guardar en sesión para verificar en el callback
        session(['gdrive_oauth_state' => $csrfToken]);

        $state = base64_encode(json_encode([
            'empresa_id' => $config->empresa_id,
            'state_token' => $csrfToken
        ]));

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
        $state = $request->input('state');

        if ($error) {
            return view('gdrive-callback', ['success' => false, 'message' => $error]);
        }

        if (!$code) {
            return view('gdrive-callback', ['success' => false, 'message' => 'No se recibió código de autorización']);
        }

        // VALIDACIÓN DE STATE (CSRF Protection)
        $decodedState = json_decode(base64_decode($state), true);
        $savedState = session('gdrive_oauth_state');

        if (!$decodedState || !isset($decodedState['state_token']) || $decodedState['state_token'] !== $savedState) {
            Log::error('Google Drive: Intento de OAuth con state inválido o caducado');
            return view('gdrive-callback', ['success' => false, 'message' => 'Sesión de autorización inválida o expirada.']);
        }

        $empresaId = $decodedState['empresa_id'] ?? null;
        if (!$empresaId) {
            return view('gdrive-callback', ['success' => false, 'message' => 'No se pudo determinar la empresa.']);
        }

        // Limpiar state de la sesión
        session()->forget('gdrive_oauth_state');

        // Establecer contexto de empresa para la operación
        \App\Support\EmpresaResolver::setContext($empresaId);

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

        // Guardar tokens en la configuración de la empresa correcta
        $config = EmpresaConfiguracion::getConfig($empresaId);
        Log::info('GDrive Callback - Empresa ID Resolved: ' . $empresaId);
        $config->update([
            'gdrive_enabled' => true,
            'gdrive_access_token' => Crypt::encryptString($result['access_token']),
            'gdrive_refresh_token' => $result['refresh_token'] ? Crypt::encryptString($result['refresh_token']) : null,
            'gdrive_token_expires_at' => now()->addSeconds($result['expires_in'] ?? 3600),
            'cloud_provider' => 'gdrive'
        ]);

        EmpresaConfiguracion::clearCache($empresaId);

        Log::info("Google Drive: Autorización completada exitosamente para empresa $empresaId");

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

            // Validar que tenemos las credenciales de la app (CLIENT_ID y CLIENT_SECRET)
            if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
                Log::warning('Google Drive: Credenciales de la aplicación (Client ID/Secret) no configuradas en el servidor.');
                return null;
            }

            // Verificar si necesita refresh (hacerlo 5 minutos antes para mayor seguridad)
            if ($config->gdrive_token_expires_at && now()->addMinutes(5)->gte($config->gdrive_token_expires_at) && $refreshToken) {
                Log::info('Google Drive: Intentando refrescar token expirado o próximo a expirar.');

                $newToken = $service->refreshAccessToken(
                    (string) $credentials['client_id'],
                    (string) $credentials['client_secret'],
                    (string) $refreshToken
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
                } else {
                    Log::error('Google Drive: Error al refrescar token: ' . ($newToken['message'] ?? 'Error desconocido'));
                    return null;
                }
            }

            $service->initialize(null, $accessToken, $refreshToken, $credentials['client_id'], $credentials['client_secret']);
            return $service;

        } catch (\Exception $e) {
            Log::error('Google Drive: Error de inicialización - ' . $e->getMessage());
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

        if (!$localPath) {
            return response()->json(['success' => false, 'message' => 'Archivo local no especificado']);
        }

        // VALIDACIÓN DE SEGURIDAD: Prevenir Path Traversal y exfiltración de archivos sensibles
        $realPath = realpath($localPath);
        $storagePath = storage_path();

        if (!$realPath || !str_starts_with($realPath, $storagePath)) {
            \Log::error("SECURITY ALERT: Intento de subida de archivo a GDrive fuera de storage: " . $localPath, [
                'user_id' => auth()->id(),
                'ip' => $request->ip()
            ]);
            return response()->json(['success' => false, 'message' => 'Acceso denegado a la ruta especificada']);
        }

        if (!file_exists($realPath)) {
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
