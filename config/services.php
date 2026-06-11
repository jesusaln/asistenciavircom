<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sw_sapien' => [
        'url' => env('SW_SAPIEN_URL', 'https://services.test.sw.com.mx'),
        'token' => env('SW_SAPIEN_TOKEN', ''),
        'rfc' => env('SW_SAPIEN_RFC', 'EKU9003173C9'),
        'emisor_nombre' => env('SW_SAPIEN_EMISOR_NOMBRE', 'ESCUELA KEMPER URGATE'),
        'regimen' => env('SW_SAPIEN_REGIMEN', '601'),
    ],

    'facturama' => [
        'url' => env('FACTURAMA_URL', 'https://apisandbox.facturama.mx'),
        'username' => env('FACTURAMA_USERNAME', ''),
        'password' => env('FACTURAMA_PASSWORD', ''),
    ],

    'google_drive' => [
        'enabled' => env('GOOGLE_DRIVE_ENABLED', false),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID') ?: env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET') ?: env('GOOGLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN', ''),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob')),
        'allow_deletes' => env('GDRIVE_ALLOW_DELETES', false),
    ],

    'sat_descarga_masiva' => [
        'verify' => env('SAT_DESCARGA_VERIFY', true),
        'cafile' => env('SAT_DESCARGA_CAFILE'),
        'segment_days' => env('SAT_DESCARGA_SEGMENT_DAYS', 31),
        'document_status' => env('SAT_DESCARGA_DOCUMENT_STATUS', 'active'),
    ],
    'sat_consulta' => [
        'endpoint' => env('SAT_CONSULTA_ENDPOINT', 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc'),
        'verify' => env('SAT_CONSULTA_VERIFY', true),
    ],

    // OAuth Social Login para Tienda
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],



    // Pasarelas de Pago
    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'key' => env('MERCADOPAGO_ACCESS_TOKEN'), // Alias para compatibilidad
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox o live
    ],

    'stripe' => [
        'key' => env('STRIPE_PUBLIC_KEY'),
        'secret' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],



    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1'),
        'temperature' => max(0.0, min(2.0, (float) env('OLLAMA_TEMPERATURE', 0.7))),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'llama-3.1-70b-versatile'),
        'temperature' => max(0.0, min(2.0, (float) env('GROQ_TEMPERATURE', 0.7))),
    ],

    // Proveedor de IA preferido: 'groq' o 'ollama'
    'ai_provider' => env('AI_PROVIDER', 'groq'),

    'meta' => [
        'enabled' => env('META_CAPI_ENABLED', false),
        'pixel_id' => env('META_PIXEL_ID', '244351761667472'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'test_event_code' => env('META_TEST_EVENT_CODE'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'temperature' => max(0.0, min(1.0, (float) env('GEMINI_TEMPERATURE', 0.7))),
    ],

    'trading' => [
        'sync_token' => env('TRADING_SYNC_TOKEN', 'cdd_ia_master_2026'),
        'macro_timeframe' => env('TRADING_MACRO_TIMEFRAME', '4h'),
        'atr_period' => (int) env('TRADING_ATR_PERIOD', 14),
        'stop_loss_atr_multiplier' => (float) env('TRADING_STOP_LOSS_ATR_MULTIPLIER', 1.8),
        'trailing_stop_atr_multiplier' => (float) env('TRADING_TRAILING_STOP_ATR_MULTIPLIER', 1.35),
        'trailing_activation_atr_multiplier' => (float) env('TRADING_TRAILING_ACTIVATION_ATR_MULTIPLIER', 1.1),
        'default_order_amount' => (float) env('TRADING_DEFAULT_ORDER_AMOUNT', 100),
    ],
    'belvo' => [
        'secret_id' => env('BELVO_SECRET_ID'),
        'secret_password' => env('BELVO_SECRET_PASSWORD'),
        'url' => env('BELVO_URL', 'https://sandbox.belvo.com'),
    ],

    'cva' => [
        'base_url' => env('CVA_API_BASE_URL', 'https://apicvaservices.grupocva.com/api/v2'),
        'shipping_url' => env('CVA_SHIPPING_URL', 'https://www.grupocva.com/api/paqueteria_service'),
    ],
];
