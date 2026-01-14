<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para subir archivos a Backblaze B2.
 * Muy simple: solo necesita Application Key ID y Application Key.
 */
class CloudBackupService
{
    protected $keyId;
    protected $applicationKey;
    protected $bucketId;
    protected $bucketName;
    protected $apiUrl;
    protected $authToken;
    protected $uploadUrl;
    protected $uploadAuthToken;

    public function __construct()
    {
        $this->keyId = config('services.backblaze.key_id');
        $this->applicationKey = config('services.backblaze.application_key');
        $this->bucketId = config('services.backblaze.bucket_id');
        $this->bucketName = config('services.backblaze.bucket_name');
    }

    /**
     * Autorizar con Backblaze B2
     */
    protected function authorize(): bool
    {
        if ($this->authToken && $this->apiUrl) {
            return true;
        }

        $credentials = base64_encode($this->keyId . ':' . $this->applicationKey);

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Basic ' . $credentials])
                ->get('https://api.backblazeb2.com/b2api/v2/b2_authorize_account');

            if ($response->successful()) {
                $data = $response->json();
                $this->authToken = $data['authorizationToken'];
                $this->apiUrl = $data['apiUrl'];
                
                // Si no tenemos bucket_id, intentar obtenerlo
                if (!$this->bucketId && $this->bucketName) {
                    $this->bucketId = $this->getBucketId();
                }
                
                return true;
            }

            Log::error('Backblaze B2: Error de autorización', ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('Backblaze B2: Excepción', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtener bucket ID por nombre
     */
    protected function getBucketId(): ?string
    {
        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => $this->authToken])
            ->post($this->apiUrl . '/b2api/v2/b2_list_buckets', [
                'accountId' => explode(':', base64_decode($this->keyId . ':' . $this->applicationKey))[0] ?? '',
                'bucketName' => $this->bucketName,
            ]);

        if ($response->successful()) {
            $buckets = $response->json()['buckets'] ?? [];
            foreach ($buckets as $bucket) {
                if ($bucket['bucketName'] === $this->bucketName) {
                    return $bucket['bucketId'];
                }
            }
        }

        return null;
    }

    /**
     * Obtener URL de upload
     */
    protected function getUploadUrl(): bool
    {
        if (!$this->authorize()) {
            return false;
        }

        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => $this->authToken])
            ->post($this->apiUrl . '/b2api/v2/b2_get_upload_url', [
                'bucketId' => $this->bucketId,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->uploadUrl = $data['uploadUrl'];
            $this->uploadAuthToken = $data['authorizationToken'];
            return true;
        }

        Log::error('Backblaze B2: Error obteniendo URL de upload', ['response' => $response->body()]);
        return false;
    }

    /**
     * Subir archivo a Backblaze B2
     */
    public function uploadFile(string $filePath, string $fileName = null): array
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Archivo no encontrado: ' . $filePath];
        }

        if (!$this->getUploadUrl()) {
            return ['success' => false, 'message' => 'No se pudo obtener URL de upload'];
        }

        $fileName = $fileName ?? basename($filePath);
        $fileContent = file_get_contents($filePath);
        $sha1 = sha1($fileContent);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => $this->uploadAuthToken,
                    'X-Bz-File-Name' => urlencode('backups/' . $fileName),
                    'Content-Type' => $mimeType,
                    'X-Bz-Content-Sha1' => $sha1,
                ])
                ->withBody($fileContent, $mimeType)
                ->post($this->uploadUrl);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Backblaze B2: Archivo subido exitosamente', [
                    'file_id' => $data['fileId'],
                    'name' => $fileName,
                ]);

                return [
                    'success' => true,
                    'message' => 'Archivo subido exitosamente',
                    'file_id' => $data['fileId'],
                    'name' => $fileName,
                    'size' => $data['contentLength'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Error subiendo archivo: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Backblaze B2: Excepción al subir', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Listar archivos en el bucket
     */
    public function listFiles(): array
    {
        if (!$this->authorize()) {
            return ['success' => false, 'files' => []];
        }

        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => $this->authToken])
            ->post($this->apiUrl . '/b2api/v2/b2_list_file_names', [
                'bucketId' => $this->bucketId,
                'prefix' => 'backups/',
                'maxFileCount' => 100,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'files' => $response->json()['files'] ?? [],
            ];
        }

        return ['success' => false, 'files' => []];
    }

    /**
     * Verificar si la configuración está completa
     */
    public function isConfigured(): bool
    {
        return !empty($this->keyId) && !empty($this->applicationKey) && !empty($this->bucketId);
    }
}
