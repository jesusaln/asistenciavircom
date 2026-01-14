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

    'facturaloplus' => [
        'base_url' => env('FACTURALO_BASE_URL', 'https://dev.facturaloplus.com/api/rest/servicio'),
        'apikey' => env('FACTURALO_APIKEY', ''),
    ],

    'google_drive' => [
        'enabled' => env('GOOGLE_DRIVE_ENABLED', false),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID') ?: env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET') ?: env('GOOGLE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN', ''),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', ''),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob')),
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

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', '/auth/microsoft/callback'),
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
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

    'contpaqi' => [
        'enabled' => env('CONTPAQI_ENABLED', false),
        'url' => env('CONTPAQI_API_URL', 'http://192.168.191.226:5000'),
        'ruta_empresa' => env('CONTPAQI_RUTA_EMPRESA', 'C:\\Compac\\Empresas\\adTU_EMPRESA'),
        'pass_csd' => env('CONTPAQI_CSD_PASS', ''),
        'concept_code' => env('CONTPAQI_CONCEPT_CODE', '4CLIMAS'),
        'concept_code_pago' => env('CONTPAQI_CONCEPT_CODE_PAGO', '100'),
        'concept_code_anticipo' => env('CONTPAQI_CONCEPT_CODE_ANTICIPO', '4CLIMAS'),
    ],
];
