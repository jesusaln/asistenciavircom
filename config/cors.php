<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        'https://admin.climasdeldesierto.com',
        'https://climasdeldesierto.com',
        'https://climasdeldesierto.laravel.cloud',
        'https://cdd.local',
        'https://app.cdd.local',
        // Orígenes específicos para desarrollo local
        'ionic://localhost',
        'http://localhost',
        'https://localhost',
        'http://localhost:8000',
        'https://localhost:8000',
        'http://127.0.0.1:8000',
        'https://127.0.0.1:8000',
        'http://0.0.0.0:8000',
        'https://0.0.0.0:8000',
        'http://localhost:8010',
        'http://127.0.0.1:8010',
        'capacitor://localhost',
        // Permitir cualquier origen localhost con puerto
        'http://localhost:*',
        'https://localhost:*',
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.climasdeldesierto\.com$/',
        '/^https:\/\/.*\.climasdeldesierto\.laravel\.cloud$/',
        '/^https:\/\/.*\.asistenciavircom\.com$/',
        '/^http:\/\/191\.101\.233\.82(:\d+)?$/',
        '/^https:\/\/cdd\.local$/',
        // Patrones específicos para Ionic
        '/^ionic:\/\//',
        '/^capacitor:\/\//',
        '/^http:\/\/localhost(:\d+)?$/',
        '/^https:\/\/localhost(:\d+)?$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 86400,
    'supports_credentials' => true, // Cambiado a true para apps móviles
];
