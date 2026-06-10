<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziggy Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the Ziggy route generator. You can specify which
    | route groups should be included or excluded, and you can also specify
    | the URL to use for the generated routes.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'port' => env('APP_PORT', 8000),

    'defaults' => [],

    'groups' => [
        'auth' => [
            'login',
            'logout',
            'register',
            'password.*',
            'verification.*',
            'user.*',
            'two-factor.*',
            'sanctum.*',
        ],
        'guest' => [
            'login',
            'register',
            'password.*',
            'verification.*',
        ],
    ],

    // 'whitelist' removed to prevent silent failures. Using blacklist/except strategy instead.
    'except' => [
        'debugbar.*',
        'clockwork.*',
        'telescope.*',
        'horizon.*',
        'sanctum.*',
        'ignition.*',
    ],
];
