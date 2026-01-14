<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio para interactuar con Google Drive API
 */
class GoogleDriveService
{
    private ?Client $client = null;
    private ?Drive $driveService = null;
    private ?string $folderId = null;

    /**
     * Inicializar cliente de Google con credenciales
     */
    public function initialize(?string $credentialsJson = null, ?string $accessToken = null, ?string $refreshToken = null, ?string $clientId = null, ?string $clientSecret = null): bool
    {
        try {
            $this->client = new Client();
            $this->client->setApplicationName('CDD Backups');
            $this->client->setScopes([Drive::DRIVE_FILE]);

            // Configurar credenciales individuales si se proporcionan
            if ($clientId) {
                $this->client->setClientId($clientId);
            }
            if ($clientSecret) {
                $this->client->setClientSecret($clientSecret);
            }

            // Si tenemos credenciales JSON (service account o OAuth)
            if ($credentialsJson) {
                $credentials = json_decode($credentialsJson, true);
                if (isset($credentials['type']) && $credentials['type'] === 'service_account') {
                    $this->client->setAuthConfig($credentials);
                } else {
                    $this->client->setClientId($credentials['client_id'] ?? $clientId ?? '');
                    $this->client->setClientSecret($credentials['client_secret'] ?? $clientSecret ?? '');
                }
            }

            // Si tenemos access token
            if ($accessToken) {
                $this->client->setAccessToken([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_in' => 3600
                ]);

                // Refrescar token si está expirado
                if ($this->client->isAccessTokenExpired() && $refreshToken) {
                    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                }
            }

            $this->driveService = new Drive($this->client);
            return true;

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al inicializar - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar URL de autorización OAuth
     */
    public function getAuthUrl(string $clientId, string $clientSecret, string $redirectUri, ?string $state = null): string
    {
        $client = new Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($state) {
            $client->setState($state);
        }

        return $client->createAuthUrl();
    }

    /**
     * Intercambiar código de autorización por tokens
     */
    public function exchangeCode(string $clientId, string $clientSecret, string $redirectUri, string $code): array
    {
        try {
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                return ['success' => false, 'message' => $token['error_description'] ?? $token['error']];
            }

            return [
                'success' => true,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in' => $token['expires_in'] ?? 3600
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verificar conexión
     */
    public function testConnection(): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'message' => 'Servicio no inicializado'];
        }

        try {
            $about = $this->driveService->about->get(['fields' => 'user,storageQuota']);

            $quota = $about->getStorageQuota();
            $user = $about->getUser();

            return [
                'success' => true,
                'message' => 'Conexión exitosa con Google Drive',
                'user' => [
                    'email' => $user->getEmailAddress(),
                    'name' => $user->getDisplayName()
                ],
                'space' => [
                    'total' => $this->formatBytes((int) $quota->getLimit()),
                    'used' => $this->formatBytes((int) $quota->getUsage()),
                    'free' => $this->formatBytes((int) $quota->getLimit() - (int) $quota->getUsage())
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error en test - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Crear o encontrar carpeta para backups
     */
    public function getOrCreateFolder(string $folderName = 'CDD_Backups'): ?string
    {
        if (!$this->driveService)
            return null;

        try {
            // Buscar carpeta existente
            $response = $this->driveService->files->listFiles([
                'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false",
                'spaces' => 'drive',
                'fields' => 'files(id, name)'
            ]);

            $files = $response->getFiles();
            if (count($files) > 0) {
                $this->folderId = $files[0]->getId();
                return $this->folderId;
            }

            // Crear carpeta
            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $this->driveService->files->create($folderMetadata, ['fields' => 'id']);
            $this->folderId = $folder->getId();

            Log::info('Google Drive: Carpeta creada - ' . $folderName);
            return $this->folderId;

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al crear carpeta - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Subir archivo a Google Drive
     */
    public function upload(string $localPath, ?string $folderId = null): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'message' => 'Servicio no inicializado'];
        }

        if (!file_exists($localPath)) {
            return ['success' => false, 'message' => 'Archivo local no existe'];
        }

        try {
            $folderId = $folderId ?? $this->folderId ?? $this->getOrCreateFolder();

            $fileMetadata = new DriveFile([
                'name' => basename($localPath),
                'parents' => $folderId ? [$folderId] : []
            ]);

            $content = file_get_contents($localPath);

            $file = $this->driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => mime_content_type($localPath),
                'uploadType' => 'multipart',
                'fields' => 'id, name, size, webViewLink'
            ]);

            Log::info('Google Drive: Archivo subido - ' . $file->getName() . ' (ID: ' . $file->getId() . ')');

            return [
                'success' => true,
                'message' => 'Archivo subido correctamente',
                'file' => [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'link' => $file->getWebViewLink()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al subir - ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al subir: ' . $e->getMessage()];
        }
    }

    /**
     * Listar archivos en carpeta
     */
    public function listFiles(?string $folderId = null): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'files' => []];
        }

        try {
            $folderId = $folderId ?? $this->folderId ?? $this->getOrCreateFolder();

            $query = $folderId
                ? "'{$folderId}' in parents and trashed=false"
                : "trashed=false";

            $response = $this->driveService->files->listFiles([
                'q' => $query,
                'spaces' => 'drive',
                'fields' => 'files(id, name, size, createdTime, modifiedTime, mimeType)',
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 50
            ]);

            $files = [];
            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'size' => (int) $file->getSize(),
                    'created' => $file->getCreatedTime(),
                    'modified' => $file->getModifiedTime(),
                    'is_folder' => $file->getMimeType() === 'application/vnd.google-apps.folder'
                ];
            }

            return ['success' => true, 'files' => $files];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al listar - ' . $e->getMessage());
            return ['success' => false, 'files' => [], 'message' => $e->getMessage()];
        }
    }

    /**
     * Descargar archivo
     */
    public function download(string $fileId, string $localPath): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'message' => 'Servicio no inicializado'];
        }

        try {
            $response = $this->driveService->files->get($fileId, ['alt' => 'media']);
            $content = $response->getBody()->getContents();

            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($localPath, $content);

            Log::info('Google Drive: Archivo descargado - ' . $localPath);
            return ['success' => true, 'path' => $localPath];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al descargar - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Eliminar archivo
     */
    public function delete(string $fileId): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'message' => 'Servicio no inicializado'];
        }

        try {
            $this->driveService->files->delete($fileId);
            Log::info('Google Drive: Archivo eliminado - ' . $fileId);
            return ['success' => true, 'message' => 'Archivo eliminado'];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error al eliminar - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtener nuevo access token usando refresh token
     */
    public function refreshAccessToken(string $clientId, string $clientSecret, string $refreshToken): array
    {
        try {
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);

            $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (isset($token['error'])) {
                return ['success' => false, 'message' => $token['error']];
            }

            return [
                'success' => true,
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_in' => $token['expires_in'] ?? 3600
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Limpiar respaldos antiguos, manteniendo solo los más recientes
     */
    public function cleanupOldBackups(int $keep = 10, ?string $folderId = null): array
    {
        if (!$this->driveService) {
            return ['success' => false, 'message' => 'Servicio no inicializado'];
        }

        try {
            $listRes = $this->listFiles($folderId);
            if (!$listRes['success']) {
                return $listRes;
            }

            $files = $listRes['files'] ?? [];
            // Filtrar solo archivos (no carpetas)
            $files = array_filter($files, fn($f) => !($f['is_folder'] ?? false));

            // Ordenar por creación descendente (ya vienen así por modifiedTime, pero aseguramos por created)
            usort($files, fn($a, $b) => strcmp($b['created'] ?? '', $a['created'] ?? ''));

            if (count($files) <= $keep) {
                return ['success' => true, 'message' => 'No es necesario limpiar', 'deleted' => 0];
            }

            // Archivos a eliminar (los que sobran después de los primeros $keep)
            $toDelete = array_slice($files, $keep);
            $deletedCount = 0;

            foreach ($toDelete as $file) {
                $this->delete($file['id']);
                $deletedCount++;
            }

            Log::info("Google Drive Cleanup: Se eliminaron {$deletedCount} respaldos antiguos. Se mantienen {$keep}.");

            return [
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} respaldos antiguos",
                'deleted' => $deletedCount
            ];

        } catch (\Exception $e) {
            Log::error('Google Drive: Error en cleanup - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Formatear bytes a human readable
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)
            return $bytes . ' B';
        if ($bytes < 1024 * 1024)
            return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024)
            return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }
}
