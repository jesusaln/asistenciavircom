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
     * Token de Graph API para descargar medios (imagen, audio, etc.).
     * No exige business_account_id ni phone_number_id.
     *
     * Orden: WHATSAPP_ACCESS_TOKEN (config) → columna empresa (encriptada) → whatsapp.dev.json (solo entorno local).
     */
    public static function resolveGraphAccessToken(?Empresa $empresa): ?string
    {
        $fromConfig = config('whatsapp.access_token');
        if (is_string($fromConfig) && trim($fromConfig) !== '') {
            return trim($fromConfig);
        }

        if ($empresa) {
            try {
                $fromDb = $empresa->whatsapp_access_token;
                if (is_string($fromDb) && trim($fromDb) !== '') {
                    return trim($fromDb);
                }
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->warning('WhatsApp token: error al leer o desencriptar', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $fromDev = self::readDevFileAccessToken($empresa);
        if ($fromDev !== null) {
            return $fromDev;
        }

        Log::channel('whatsapp')->warning('WhatsApp resolveGraphAccessToken: sin token (configura WHATSAPP_ACCESS_TOKEN o token en empresa)', [
            'empresa_id' => $empresa?->id,
        ]);

        return null;
    }

    /**
     * Misma fuente que loadConfigFromDevFile: empresa_1.access_token si env y BD están vacíos.
     */
    private static function readDevFileAccessToken(?Empresa $empresa): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        if (! empty(config('whatsapp.access_token'))) {
            return null;
        }

        $empresaTokenEmpty = true;
        if ($empresa) {
            try {
                $empresaTokenEmpty = empty(trim((string) $empresa->whatsapp_access_token));
            } catch (\Throwable $e) {
                $empresaTokenEmpty = true;
            }
        }

        if (! $empresaTokenEmpty) {
            return null;
        }

        $relative = config('whatsapp.dev_config_file', 'whatsapp.dev.json');
        $paths = array_unique(array_filter([
            base_path($relative),
            $relative,
        ]));

        $contents = null;
        foreach ($paths as $path) {
            if ($path && is_readable($path)) {
                $contents = @file_get_contents($path);
                break;
            }
        }

        if ($contents === false || $contents === null) {
            return null;
        }

        $devConfig = json_decode($contents, true);
        if (! is_array($devConfig) || ! isset($devConfig['empresa_1']['access_token'])) {
            return null;
        }

        $tok = $devConfig['empresa_1']['access_token'];

        return is_string($tok) && trim($tok) !== '' ? trim($tok) : null;
    }

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
            'graph_version' => config('whatsapp.graph_version', 'v20.0'),
            'default_language' => config('whatsapp.default_language', 'es_MX'),
            'request_timeout' => config('whatsapp.request_timeout', 30),
            'business_account_id' => config('whatsapp.business_account_id'),
            'phone_number_id' => config('whatsapp.phone_number_id'),
            'access_token' => config('whatsapp.access_token'),
            'default_template' => config('whatsapp.default_template', 'saludo'),
        ];
    }

    /**
     * Cargar configuración desde base de datos
     */
    private static function loadConfigFromDatabase(Empresa $empresa): array
    {
        $config = [];

        // Solo usar valores de BD si no están en env (prioridad a env)
        if (empty(config('whatsapp.business_account_id')) && $empresa->whatsapp_business_account_id) {
            $config['business_account_id'] = $empresa->whatsapp_business_account_id;
        }

        if (empty(config('whatsapp.phone_number_id')) && $empresa->whatsapp_phone_number_id) {
            $config['phone_number_id'] = $empresa->whatsapp_phone_number_id;
        }

        if (empty(config('whatsapp.access_token')) && $empresa->whatsapp_access_token) {
            $raw = $empresa->whatsapp_access_token;
            $config['access_token'] = is_string($raw) ? trim($raw) : (string) $raw;
        }

        if (empty(config('whatsapp.default_template')) && $empresa->whatsapp_template_payment_reminder) {
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
        if (app()->environment('local')) {
            $configFile = config('whatsapp.dev_config_file', 'whatsapp.dev.json');

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
                        empty(config('whatsapp.business_account_id')) &&
                        empty($empresa->whatsapp_business_account_id) &&
                        isset($empresa1['business_account_id'])
                    ) {
                        $config['business_account_id'] = $empresa1['business_account_id'];
                    }

                    if (
                        empty(config('whatsapp.phone_number_id')) &&
                        empty($empresa->whatsapp_phone_number_id) &&
                        isset($empresa1['phone_number_id'])
                    ) {
                        $config['phone_number_id'] = $empresa1['phone_number_id'];
                    }

                    if (
                        empty(config('whatsapp.access_token')) &&
                        empty($empresa->whatsapp_access_token) &&
                        isset($empresa1['access_token'])
                    ) {
                        $config['access_token'] = $empresa1['access_token'];
                    }

                    if (
                        empty(config('whatsapp.default_template')) &&
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
            }
        }

        return [];
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
            'dev_file' => config('whatsapp.dev_config_file', 'whatsapp.dev.json'),
            'cache_enabled' => true,
            'cache_ttl' => config('whatsapp.security.token_cache_ttl', 3600),
        ];
    }
}
