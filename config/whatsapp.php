<?php

/**
 * Configuración de WhatsApp Business API
 *
 * Esta configuración se carga desde variables de entorno para mayor seguridad.
 * Nunca comprometas información sensible en el código fuente.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración por Entorno
    |--------------------------------------------------------------------------
    |
    | Los valores se cargan desde variables de entorno (.env) o desde
    | la configuración de la empresa en la base de datos.
    |
    */

    'defaults' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
        'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'es_MX'),
        'request_timeout' => env('WHATSAPP_REQUEST_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    |
    | Controla cómo se manejan los tokens y la información sensible.
    |
    */

    'security' => [
        'encrypt_tokens' => env('WHATSAPP_ENCRYPT_TOKENS', true),
        'token_cache_ttl' => env('WHATSAPP_TOKEN_CACHE_TTL', 3600), // 1 hora
        'validate_token_on_use' => env('WHATSAPP_VALIDATE_TOKEN_ON_USE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Desarrollo
    |--------------------------------------------------------------------------
    |
    | Solo para entorno de desarrollo. En producción, usa variables de entorno.
    |
    */

    'development' => [
        'enabled' => env('APP_ENV') === 'local',
        'config_file' => env('WHATSAPP_DEV_CONFIG_FILE', 'whatsapp.dev.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Logs
    |--------------------------------------------------------------------------
    |
    | Controla qué información se registra en los logs.
    |
    */

    'logging' => [
        'log_requests' => env('WHATSAPP_LOG_REQUESTS', true),
        'log_responses' => env('WHATSAPP_LOG_RESPONSES', false), // Cuidado con información sensible
        'log_phone_numbers' => env('WHATSAPP_LOG_PHONE_NUMBERS', false),
        'log_tokens' => env('WHATSAPP_LOG_TOKENS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Plantillas
    |--------------------------------------------------------------------------
    |
    | Define plantillas por defecto y sus parámetros.
    |
    */

    'templates' => [
        'default_reminder' => env('WHATSAPP_DEFAULT_TEMPLATE', 'saludo'),
        'payment_reminder' => env('WHATSAPP_PAYMENT_REMINDER_TEMPLATE', 'saludo'),
        'welcome_message' => env('WHATSAPP_WELCOME_TEMPLATE', 'mensaje_de_bienvenida_climas_del_desierto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Validación
    |--------------------------------------------------------------------------
    |
    | Reglas para validar números de teléfono y otros datos.
    |
    */

    'validation' => [
        'phone_regex' => '/^\+[1-9]\d{1,14}$/',
        'allowed_countries' => ['MX', 'US'], // Solo México y Estados Unidos
        'max_phone_length' => 15,
        'min_phone_length' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limita el envío de mensajes para evitar bloqueos de WhatsApp.
    |
    */

    'rate_limiting' => [
        'enabled' => env('WHATSAPP_RATE_LIMITING', true),
        'max_per_minute' => env('WHATSAPP_MAX_PER_MINUTE', 100),
        'max_per_hour' => env('WHATSAPP_MAX_PER_HOUR', 1000),
        'max_per_day' => env('WHATSAPP_MAX_PER_DAY', 10000),
    ],

];
