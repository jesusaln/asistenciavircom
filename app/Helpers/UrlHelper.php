<?php

namespace App\Helpers;

class UrlHelper
{
    /**
     * Generar URL de storage correcta usando el dominio actual de la petición
     * Esta función es útil cuando APP_URL está mal configurado en producción
     */
    public static function storageUrl($path = '')
    {
        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Generar URL de asset correcta usando el dominio actual
     */
    public static function assetUrl($path = '')
    {
        return '/' . ltrim($path, '/');
    }

    /**
     * Verificar si estamos en producción y APP_URL parece estar mal configurado
     */
    public static function isAppUrlMisconfigured()
    {
        $appUrl = config('app.url');
        $currentHost = request()->getHost();

        // Si APP_URL contiene localhost pero el host actual no es localhost
        if (str_contains($appUrl, 'localhost') && !str_contains($currentHost, 'localhost')) {
            return true;
        }

        // Si APP_URL es una IP interna pero el host actual es un dominio
        if (preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $appUrl) &&
            !preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $currentHost)) {
            return true;
        }

        return false;
    }

    /**
     * Obtener información de debug sobre URLs
     */
    public static function getUrlDebugInfo()
    {
        return [
            'app_url' => config('app.url'),
            'current_host' => request()->getHost(),
            'current_scheme' => request()->getScheme(),
            'current_port' => request()->getPort(),
            'is_secure' => request()->isSecure(),
            'is_misconfigured' => self::isAppUrlMisconfigured(),
            'recommended_storage_url' => self::storageUrl(),
        ];
    }
}
