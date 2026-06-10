<?php

/**
 * Feature flags leídas vía config() (compatibles con `php artisan config:cache`).
 *
 * Opcional: FEATURE_FLAGS_JSON={"nombre_flag":true,"otro":false}
 */
return [

    'flags_json' => json_decode(env('FEATURE_FLAGS_JSON', '{}'), true) ?: [],

    'defaults' => [
    ],

];
