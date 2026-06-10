<?php

/**
 * CDN Configuration
 *
 * Configuración centralizada para CDN de assets estáticos.
 * Soporta múltiples providers: Cloudflare, AWS CloudFront, Custom URL, etc.
 */

return [
    /**
     * Habilitar/deshabilitar CDN globalmente
     */
    'enabled' => (bool) env('CDN_ENABLED', false),

    /**
     * Provider de CDN
     * Opciones: 'cloudflare', 'cloudfront', 'custom', 'local'
     */
    'provider' => env('CDN_PROVIDER', 'local'),

    /**
     * URL base del CDN
     * Ejemplos:
     *   - Cloudflare: https://<zone>.cloudflare.com/cdn-cgi/imgs
     *   - CloudFront: https://<distribution>.cloudfront.net
     *   - Custom: https://cdn.midominio.com
     */
    'base_url' => env('CDN_BASE_URL', ''),

    /**
     * Paths públicos que se servirán desde CDN
     */
    'paths' => [
        'storage/app/public',
        'vendor',
        'build/assets',
        'images',
        'fonts',
    ],

    /**
     * Exclude paths - Paths que NO se servirán desde CDN
     */
    'exclude' => [
        'storage/app/private',
        'storage/logs',
        '.git',
        '.env',
    ],

    /**
     * Query string para cache busting
     */
    'cache_bust' => [
        'enabled' => true,
        'parameter' => 'v',
        // Usar hash del archivo en lugar de versión simple
        'use_file_hash' => true,
    ],

    /**
     * Configuración por provider
     */
    'providers' => [
        'cloudflare' => [
            'zone_id' => env('CLOUDFLARE_ZONE_ID'),
            'api_token' => env('CLOUDFLARE_API_TOKEN'),
            'purge_cache_on_deploy' => true,
        ],
        'cloudfront' => [
            'distribution_id' => env('AWS_CLOUDFRONT_DISTRIBUTION'),
            'key' => env('AWS_CLOUDFRONT_KEY'),
            'secret' => env('AWS_CLOUDFRONT_SECRET'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
    ],

    /**
     * Headers de cache para assets
     */
    'cache_headers' => [
        'immutable' => ['js', 'css', 'woff2', 'woff'],
        'max_age' => 31536000, // 1 año
        'shared_max_age' => 86400, // 1 día
    ],

    /**
     * Fallback a URL local si CDN falla
     */
    'fallback' => true,

    /**
     * Logging de operaciones CDN
     */
    'logging' => [
        'enabled' => true,
        'channel' => 'daily',
    ],
];
