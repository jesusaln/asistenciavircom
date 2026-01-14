<?php

namespace App\Services;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para manejar configuración segura de WhatsApp
 *
 * Prioridad de carga de configuración:
 * 1. Variables de entorno (.env)
 * 2. Base de datos (Empresa model - encriptada)
 * 3. Archivo de desarrollo (solo en local)
 */
class WhatsAppConfigService
{
    /**
     * Obtener configuración completa de WhatsApp para una empresa
     */
    public static function getConfig(?int $empresaId = null): array
    {
        $resolvedId = $empresaId ?: EmpresaResolver::resolveId();
        $empresa = $resolvedId ? Empresa::find($resolvedId) : null;

        if (!$empresa) {
            throw new \Exception('Empresa no encontrada');
        }

        // Cargar configuración desde múltiples fuentes
        $config = self::loadConfigFromEnv();
        $config = array_merge($config, self::loadConfigFromDatabase($empresa));
        $config = array_merge($config, self::loadConfigFromDevFile());

        // Validar configuración mínima requerida
        self::validateConfig($config);

        return $config;
    }

    /**
     * Cargar configuración desde variables de entorno
     */
    private static function loadConfigFromEnv(): array
    {
        return [
            'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
            'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'es_MX'),
            'request_timeout' => env('WHATSAPP_REQUEST_TIMEOUT', 30),
            'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'default_template' => env('WHATSAPP_DEFAULT_TEMPLATE', 'saludo'),
        ];
    }

    /**
     * Cargar configuración desde base de datos
     */
    private static function loadConfigFromDatabase(Empresa $empresa): array
    {
        $config = [];

        // Solo usar valores de BD si no están en env (prioridad a env)
        if (empty(env('WHATSAPP_BUSINESS_ACCOUNT_ID')) && $empresa->whatsapp_business_account_id) {
            $config['business_account_id'] = $empresa->whatsapp_business_account_id;
        }

        if (empty(env('WHATSAPP_PHONE_NUMBER_ID')) && $empresa->whatsapp_phone_number_id) {
            $config['phone_number_id'] = $empresa->whatsapp_phone_number_id;
        }

        if (empty(env('WHATSAPP_ACCESS_TOKEN')) && $empresa->whatsapp_access_token) {
            // Desencriptar token si es necesario
            try {
                $config['access_token'] = decrypt($empresa->whatsapp_access_token);
            } catch (\Exception $e) {
                // Si falla la desencriptación, usar el token tal cual
                $config['access_token'] = $empresa->whatsapp_access_token;
                Log::info('Usando token sin encriptar desde BD', [
                    'empresa_id' => $empresa->id,
                ]);
            }
        }

        if (empty(env('WHATSAPP_DEFAULT_TEMPLATE')) && $empresa->whatsapp_template_payment_reminder) {
            $config['default_template'] = $empresa->whatsapp_template_payment_reminder;
        }

        return $config;
    }

    /**
     * Cargar configuración desde archivo de desarrollo (solo en local)
     */
    private static function loadConfigFromDevFile(): array
    {
        // Solo cargar en desarrollo y si no hay configuración en env o BD
        if (env('APP_ENV') !== 'local') {
            return [];
        }

        $configFile = env('WHATSAPP_DEV_CONFIG_FILE', 'whatsapp.dev.json');

        if (!file_exists($configFile)) {
            return [];
        }

        try {
            $devConfig = json_decode(file_get_contents($configFile), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Error al leer archivo de configuración de desarrollo', [
                    'file' => $configFile,
                    'error' => json_last_error_msg(),
                ]);
                return [];
            }

            // Usar configuración de desarrollo solo si no está en env o BD
            $config = [];

            if (isset($devConfig['empresa_1'])) {
                $empresa1 = $devConfig['empresa_1'];
                $empresaId = EmpresaResolver::resolveId();
                $empresa = $empresaId ? Empresa::find($empresaId) : null;
                $empresa = $empresa ?: new Empresa();

                if (
                    empty(env('WHATSAPP_BUSINESS_ACCOUNT_ID')) &&
                    empty($empresa->whatsapp_business_account_id) &&
                    isset($empresa1['business_account_id'])
                ) {
                    $config['business_account_id'] = $empresa1['business_account_id'];
                }

                if (
                    empty(env('WHATSAPP_PHONE_NUMBER_ID')) &&
                    empty($empresa->whatsapp_phone_number_id) &&
                    isset($empresa1['phone_number_id'])
                ) {
                    $config['phone_number_id'] = $empresa1['phone_number_id'];
                }

                if (
                    empty(env('WHATSAPP_ACCESS_TOKEN')) &&
                    empty($empresa->whatsapp_access_token) &&
                    isset($empresa1['access_token'])
                ) {
                    $config['access_token'] = $empresa1['access_token'];
                }

                if (
                    empty(env('WHATSAPP_DEFAULT_TEMPLATE')) &&
                    empty($empresa->whatsapp_template_payment_reminder) &&
                    isset($empresa1['default_template'])
                ) {
                    $config['default_template'] = $empresa1['default_template'];
                }
            }

            return $config;

        } catch (\Exception $e) {
            Log::warning('Error al cargar configuración de desarrollo', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Validar que la configuración sea completa y válida
     */
    private static function validateConfig(array $config): void
    {
        $required = [
            'business_account_id',
            'phone_number_id',
            'access_token',
        ];

        $missing = [];

        foreach ($required as $field) {
            if (empty($config[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \Exception(
                'Configuración de WhatsApp incompleta. Faltan campos: ' .
                implode(', ', $missing) .
                '. Configure en .env o en la base de datos.'
            );
        }

        // Validar formato del token
        if (!preg_match('/^EA[A-Za-z0-9]{200,}$/', $config['access_token'])) {
            Log::warning('Token de WhatsApp podría tener formato inválido', [
                'token_length' => strlen($config['access_token']),
                'token_prefix' => substr($config['access_token'], 0, 10),
            ]);
        }
    }

    /**
     * Obtener configuración con caché para mejor rendimiento
     */
    public static function getCachedConfig(?int $empresaId = null): array
    {
        $cacheKey = 'whatsapp_config_' . ($empresaId ?: 'default');

        return Cache::remember($cacheKey, now()->addHour(), function () use ($empresaId) {
            return self::getConfig($empresaId);
        });
    }

    /**
     * Limpiar caché de configuración
     */
    public static function clearConfigCache(?int $empresaId = null): void
    {
        $cacheKey = 'whatsapp_config_' . ($empresaId ?: 'default');
        Cache::forget($cacheKey);

        Log::info('Caché de configuración de WhatsApp limpiado', [
            'empresa_id' => $empresaId,
            'cache_key' => $cacheKey,
        ]);
    }

    /**
     * Obtener fuente de configuración para debugging
     */
    public static function getConfigSources(): array
    {
        return [
            'env_file' => app()->environmentFilePath(),
            'database' => 'empresa.whatsapp_* fields',
            'dev_file' => env('WHATSAPP_DEV_CONFIG_FILE', 'whatsapp.dev.json'),
            'cache_enabled' => true,
            'cache_ttl' => config('whatsapp.security.token_cache_ttl', 3600),
        ];
    }
}
