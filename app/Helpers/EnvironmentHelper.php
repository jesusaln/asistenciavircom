<?php

namespace App\Helpers;

class EnvironmentHelper
{
    /**
     * Verificar si la aplicación está en entorno de producción
     */
    public static function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    /**
     * Verificar si la aplicación está en entorno de desarrollo
     */
    public static function isDevelopment(): bool
    {
        return in_array(config('app.env'), ['local', 'development', 'dev']);
    }

    /**
     * Prevenir acciones de desarrollo en producción
     * Lanza una excepción si se intenta ejecutar algo de desarrollo en producción
     */
    public static function preventDevelopmentAction(string $action = 'esta acción'): void
    {
        if (self::isProduction()) {
            throw new \Exception("❌ ERROR: No se puede ejecutar {$action} en entorno de producción. APP_ENV=" . config('app.env'));
        }
    }

    /**
     * Prevenir acciones de producción en desarrollo (opcional)
     */
    public static function preventProductionAction(string $action = 'esta acción'): void
    {
        if (self::isDevelopment()) {
            throw new \Exception("❌ ERROR: No se puede ejecutar {$action} en entorno de desarrollo. APP_ENV=" . config('app.env'));
        }
    }

    /**
     * Obtener información del entorno actual
     */
    public static function getEnvironmentInfo(): array
    {
        return [
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'is_production' => self::isProduction(),
            'is_development' => self::isDevelopment(),
        ];
    }

    /**
     * Log de advertencia para acciones riesgosas en producción
     */
    public static function logProductionWarning(string $message): void
    {
        if (self::isProduction()) {
            \Log::warning("PRODUCCIÓN WARNING: {$message}");
        }
    }
}
