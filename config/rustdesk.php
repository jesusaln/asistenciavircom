<?php

return [
    'api_url' => env('RUSTDESK_API_URL', ''),
    'panel_url' => env('RUSTDESK_PANEL_URL', 'https://remoto.asistenciavircom.com'),
    'api_token' => env('RUSTDESK_API_TOKEN', ''),
    'server_address' => env('RUSTDESK_SERVER_ADDRESS', ''),
    'relay_server' => env('RUSTDESK_RELAY_SERVER', ''),
    'public_key' => env('RUSTDESK_PUBLIC_KEY', ''),

    'timeout' => (int) env('RUSTDESK_TIMEOUT', 8),
    'retry_times' => (int) env('RUSTDESK_RETRY_TIMES', 2),
    'retry_sleep_ms' => (int) env('RUSTDESK_RETRY_SLEEP_MS', 250),

    'auth' => [
        'header' => env('RUSTDESK_AUTH_HEADER', 'Authorization'),
        'prefix' => env('RUSTDESK_AUTH_PREFIX', 'Bearer'),
    ],

    'endpoints' => [
        'device_status' => env('RUSTDESK_ENDPOINT_DEVICE_STATUS', '/api/devices/{id}'),
        'devices' => env('RUSTDESK_ENDPOINT_DEVICES', '/api/devices'),
        'sync_alias' => env('RUSTDESK_ENDPOINT_SYNC_ALIAS', '/api/devices/{id}/alias'),
    ],

    'devices_search_key' => env('RUSTDESK_DEVICES_SEARCH_KEY', 'search'),
    'alias_method' => env('RUSTDESK_ALIAS_METHOD', 'patch'),
    'status_cache_minutes' => (int) env('RUSTDESK_STATUS_CACHE_MINUTES', 5),
    'download_url' => env('RUSTDESK_CLIENT_DOWNLOAD_URL', 'https://rustdesk.com/download'),
];
