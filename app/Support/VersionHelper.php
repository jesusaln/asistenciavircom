<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class VersionHelper
{
    /**
     * Obtiene la versión actual de la aplicación basada en el hash del build.
     * Se cachea para evitar lectura de disco constante.
     */
    public static function getVersion(): string
    {
        return Cache::remember('app_version_hash', 60, function () {
            $manifestPath = public_path('build/manifest.json');

            if (!File::exists($manifestPath)) {
                return 'dev-' . time(); // Fallback para desarrollo sin build
            }

            return md5_file($manifestPath);
        });
    }
}
