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

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.1'),
        'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'model' => env('GROQ_MODEL', 'llama-3.1-70b-versatile'),
        'temperature' => env('GROQ_TEMPERATURE', 0.7),
    ],

    // Proveedor de IA preferido: 'groq' o 'ollama'
    'ai_provider' => env('AI_PROVIDER', 'groq'),

    'biometrics' => [
        'provider' => env('BIOMETRICS_PROVIDER', 'mock'),
        'strict_match' => env('BIOMETRICS_STRICT_MATCH', false),
        // Umbrales base para modo local (0 a 1). Inician flexibles y se pueden subir gradualmente.
        'local_match_threshold' => (float) env('BIOMETRICS_LOCAL_MATCH_THRESHOLD', 0.72),
        'local_liveness_threshold' => (float) env('BIOMETRICS_LOCAL_LIVENESS_THRESHOLD', 0.45),
        // Ajuste automático por cercanía geográfica
        'geofence_soft_margin_meters' => (int) env('BIOMETRICS_GEOFENCE_SOFT_MARGIN_METERS', 120),
        'nearby_match_relax' => (float) env('BIOMETRICS_NEARBY_MATCH_RELAX', 0.06),
        'nearby_liveness_relax' => (float) env('BIOMETRICS_NEARBY_LIVENESS_RELAX', 0.10),
        'far_match_penalty' => (float) env('BIOMETRICS_FAR_MATCH_PENALTY', 0.06),
        'far_liveness_penalty' => (float) env('BIOMETRICS_FAR_LIVENESS_PENALTY', 0.10),
    ],
];
