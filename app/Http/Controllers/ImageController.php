<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\UrlHelper;

class ImageController extends Controller
{
    /**
     * Servir imágenes de perfil con headers específicos para evitar bloqueo
     */
    public function serveProfilePhoto($filename)
    {
        $path = 'profile-photos/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        // Verificar que el archivo existe
        if (!Storage::disk('public')->exists($path)) {
            // Retornar imagen por defecto
            return $this->serveDefaultImage();
        }

        // Obtener información del archivo
        $fileSize = Storage::disk('public')->size($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';

        // Crear respuesta con headers específicos para evitar bloqueo
        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);

        // Headers CORS específicos para desarrollo local
        $origin = request()->header('Origin');
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');

        return $response;
    }

    /**
     * Servir imagen por defecto cuando no existe la imagen solicitada
     */
    private function serveDefaultImage()
    {
        $defaultPath = public_path('images/default-profile.svg');

        if (!file_exists($defaultPath)) {
            // Crear SVG por defecto si no existe
            return response('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400" viewBox="0 0 400 400">
                <rect width="100%" height="100%" fill="#e5e7eb"/>
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
                      font-family="system-ui, -apple-system, sans-serif" font-size="20" fill="#6b7280">
                  Sin imagen
                </text>
              </svg>', 200, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=3600',
                'Access-Control-Allow-Origin' => request()->header('Origin') ?: '*',
                'Access-Control-Allow-Credentials' => 'true',
            ]);
        }

        return response()->file($defaultPath, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => request()->header('Origin') ?: '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);
    }

    /**
     * Listar todas las imágenes de perfil disponibles
     */
    public function listProfilePhotos()
    {
        $files = Storage::disk('public')->files('profile-photos');
        $images = [];

        foreach ($files as $file) {
            $images[] = [
                'filename' => basename($file),
                'url' => route('serve-profile-photo', basename($file)),
                'storage_url' => $this->generateCorrectStorageUrl($file),
                'size' => Storage::disk('public')->size($file),
                'mime_type' => Storage::disk('public')->mimeType($file),
                'exists' => Storage::disk('public')->exists($file),
            ];
        }

        return response()->json([
            'images' => $images,
            'total' => count($images),
            'server_info' => [
                'host' => request()->getHost(),
                'port' => request()->getPort(),
                'origin' => request()->header('Origin'),
                'current_url' => request()->fullUrl(),
                'app_url' => config('app.url'),
                'url_debug' => UrlHelper::getUrlDebugInfo(),
            ]
        ]);
    }

    /**
     * Generar URL correcta independientemente de APP_URL
     */
    private function generateCorrectStorageUrl($file)
    {
        $scheme = request()->isSecure() ? 'https' : 'http';
        $host = request()->getHost();
        $port = request()->getPort();

        // No agregar puerto si es el puerto estándar
        $portString = ( ($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443) ) ? ':' . $port : '';

        return "{$scheme}://{$host}{$portString}/storage/{$file}";
    }
}
