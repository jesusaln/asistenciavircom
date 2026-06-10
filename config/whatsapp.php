<?php

return [
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v20.0'),
    'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'es_MX'),
    'request_timeout' => (int) env('WHATSAPP_REQUEST_TIMEOUT', 30),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'default_template' => env('WHATSAPP_DEFAULT_TEMPLATE', 'saludo'),
    'dev_config_file' => env('WHATSAPP_DEV_CONFIG_FILE', 'whatsapp.dev.json'),
    'security' => [
        'token_cache_ttl' => 3600,
    ],

    'pricing' => [
        'maintenance' => [
            '1_ton' => env('WHATSAPP_PRECIO_MANTENIMIENTO_1TON', 500),
            '1.5_ton' => env('WHATSAPP_PRECIO_MANTENIMIENTO_1_5TON', 600),
            '2_ton' => env('WHATSAPP_PRECIO_MANTENIMIENTO_2TON', 700),
            '3_ton' => env('WHATSAPP_PRECIO_MANTENIMIENTO_3TON', 850),
        ],
        'minisplit_mirage_life_12' => env('WHATSAPP_PRECIO_MINISPLIT_MIRAGE', 4700),
        'instalacion_basica' => env('WHATSAPP_PRECIO_INSTALACION', 1500),
    ],

    'materiales' => [
        'termico_sencillo' => env('WHATSAPP_PRECIO_TERMICO_SENCILLO'),
        'termico_doble' => env('WHATSAPP_PRECIO_TERMICO_DOBLE'),
        'tierra_metro' => env('WHATSAPP_PRECIO_TIERRA_METRO'),
        'tierra_inverter_instalada' => env('WHATSAPP_PRECIO_TIERRA_INVERTER'),
        'cable_calibre12_metro' => env('WHATSAPP_PRECIO_CABLE_12_METRO'),
        'telefono_asesor' => env('WHATSAPP_TELEFONO_ASESOR', '662 460 6840'),
        // IDs de productos en BD para consultar precios reales
        'producto_termico_2p' => env('WHATSAPP_PRODUCTO_TERMICO_2P', 84),
        'producto_cable_tierra' => env('WHATSAPP_PRODUCTO_CABLE_TIERRA', 105),
        'producto_kit_tierra' => env('WHATSAPP_PRODUCTO_KIT_TIERRA', 92),
        'producto_cable_calibre12' => env('WHATSAPP_PRODUCTO_CABLE_12', 104),
        'producto_centro_carga' => env('WHATSAPP_PRODUCTO_CENTRO_CARGA', 88),
        'producto_base_pared_1' => env('WHATSAPP_PRODUCTO_BASE_PARED_1', 144),
        'producto_base_pared_2' => env('WHATSAPP_PRODUCTO_BASE_PARED_2', 141),
        'producto_linea_gas_1ton' => env('WHATSAPP_PRODUCTO_LINEA_GAS_1TON', 317),
        'producto_linea_gas_2ton' => env('WHATSAPP_PRODUCTO_LINEA_GAS_2TON', 318),
    ],
];
