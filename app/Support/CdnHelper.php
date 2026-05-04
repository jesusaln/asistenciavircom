<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class CdnHelper
{
    /**
     * Generar URL para un asset optimizado.
     * Si hay un CDN configurado en .env (CDN_URL), se usará.
     * Si no, se usa la URL local de la aplicación.
     *
     * @param string $path Ruta relativa del archivo (ej: 'img/logo.webp')
     * @return string URL completa
     */
    public static function asset(string $path): string
    {
        // Limpieza básica del path
        $path = ltrim($path, '/');

        // Si es una URL completa externa, devolverla tal cual
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $cdnUrl = config('app.cdn_url');

        // Solo usar CDN en producción o si se fuerza explícitamente (valores en config/app.php)
        if ($cdnUrl && (App::environment('production') || config('app.force_cdn'))) {
            return rtrim($cdnUrl, '/') . '/' . $path;
        }

        // Fallback robusto a asset() local de Laravel
        return asset($path);
    }

    /**
     * Versión para imágenes con caché busting.
     *
     * Prioridad: hash del archivo en disco (no cambia si el modelo se toca sin sustituir archivo),
     * luego $updatedAt, luego $explicitVersion (p. ej. columna image_version).
     */
    public static function img(
        string $path,
        string|\DateTimeInterface|null $updatedAt = null,
        ?string $absoluteFilePath = null,
        ?string $explicitVersion = null
    ): string {
        $url = self::asset($path);

        $version = null;
        if ($explicitVersion !== null && $explicitVersion !== '') {
            $version = $explicitVersion;
        } elseif ($absoluteFilePath && File::isFile($absoluteFilePath) && is_readable($absoluteFilePath)) {
            $hash = @md5_file($absoluteFilePath);
            $version = $hash !== false ? substr($hash, 0, 16) : null;
        } elseif ($updatedAt) {
            $version = (string) (is_string($updatedAt) ? strtotime($updatedAt) : $updatedAt->getTimestamp());
        }

        return $version !== null ? $url . '?v=' . $version : $url;
    }
}
