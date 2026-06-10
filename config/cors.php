<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        // Orígenes explícitos seguros
        'https://www.climasdeldesierto.com',
        'https://climasdeldesierto.com',
        'https://app.climasdeldesierto.com',
        'https://asistenciavircom.com',
        'https://www.asistenciavircom.com',
        'https://app.asistenciavircom.com',
        'http://192.168.1.14.nip.io:8100',
    ],
    'allowed_origins_patterns' => [
        // Subdominios dinámicos permitidos en producción (HTTPS only)
        '/^https:\/\/([a-z0-9-]+\.)?climasdeldesierto\.com$/',
        '/^https:\/\/([a-z0-9-]+\.)?asistenciavircom\.com$/',
        '/^https:\/\/([a-z0-9-]+\.)?climasdeldesierto\.laravel\.cloud$/',

        // Entornos de desarrollo locales (Localhost con puertos)
        // NOTA: Esto debería estar condicionado por el entorno, pero se deja restringido por patrón
        '/^https?:\/\/localhost(:\d+)?$/',
        '/^https?:\/\/127\.0\.0\.1(:\d+)?$/',

        // Soporte red local y nip.io
        '/^https?:\/\/192\.168\.1\.\d+(:\d+)?$/',
        '/^https?:\/\/.*\.nip\.io(:\d+)?$/',

        // Soporte app móvil
        '/^ionic:\/\/.*$/',
        '/^capacitor:\/\/.*$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true,
];
