<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    | 
    | SANDBOX (Desarrollo):
    |   1. Ir a https://developer.paypal.com/
    |   2. Crear cuenta sandbox en "Sandbox Accounts"
    |   3. Crear App en "My Apps & Credentials" > Sandbox
    |   4. Copiar Client ID y Secret
    |
    | LIVE (Producción):
    |   1. Ir a https://developer.paypal.com/
    |   2. Crear App en "My Apps & Credentials" > Live
    |   3. Copiar Client ID y Secret
    |
    */
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // 'sandbox' or 'live'
        'client_id' => env('PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
        'sandbox' => [
            'api_url' => 'https://api-m.sandbox.paypal.com',
            'js_url' => 'https://www.paypal.com/sdk/js',
        ],
        'live' => [
            'api_url' => 'https://api-m.paypal.com',
            'js_url' => 'https://www.paypal.com/sdk/js',
        ],
        'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | MercadoPago Configuration
    |--------------------------------------------------------------------------
    |
    | CREDENCIALES DE PRUEBA (Desarrollo):
    |   1. Ir a https://www.mercadopago.com.mx/developers/panel
    |   2. En "Credenciales de prueba" copiar Public Key y Access Token
    |   3. Crear usuarios de prueba en "Cuentas de prueba"
    |
    | CREDENCIALES DE PRODUCCIÓN:
    |   1. Ir a https://www.mercadopago.com.mx/developers/panel
    |   2. En "Credenciales de producción" copiar Public Key y Access Token
    |
    | TARJETAS DE PRUEBA:
    |   - Visa: 4509 9535 6623 3704
    |   - Mastercard: 5031 7557 3453 0604
    |   - CVV: 123, Fecha: cualquiera futura
    |   - DNI: 12345678
    |
    */
    'mercadopago' => [
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY', ''),
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN', ''),
        'api_url' => 'https://api.mercadopago.com',
        'js_url' => 'https://sdk.mercadopago.com/js/v2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration (Tarjeta de Crédito)
    |--------------------------------------------------------------------------
    |
    | MODO TEST (Desarrollo):
    |   1. Ir a https://dashboard.stripe.com/test/apikeys
    |   2. Copiar Publishable key (pk_test_...) y Secret key (sk_test_...)
    |
    | MODO LIVE (Producción):
    |   1. Ir a https://dashboard.stripe.com/apikeys
    |   2. Copiar Publishable key (pk_live_...) y Secret key (sk_live_...)
    |
    | TARJETAS DE PRUEBA:
    |   - Éxito: 4242 4242 4242 4242
    |   - Requiere autenticación: 4000 0025 0000 3155
    |   - Declinada: 4000 0000 0000 9995
    |   - CVV: cualquier 3 dígitos, Fecha: cualquiera futura
    |
    */
    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY', ''),
        'secret_key' => env('STRIPE_SECRET_KEY', ''),
        'api_url' => 'https://api.stripe.com',
        'js_url' => 'https://js.stripe.com/v3/',
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYMENT_CURRENCY', 'MXN'),
    'success_url' => env('PAYMENT_SUCCESS_URL', '/contratacion/exito'),
    'cancel_url' => env('PAYMENT_CANCEL_URL', '/contratacion/cancelado'),

    /*
    |--------------------------------------------------------------------------
    | Métodos de Pago Habilitados
    |--------------------------------------------------------------------------
    */
    'enabled_methods' => [
        'paypal' => env('PAYMENT_PAYPAL_ENABLED', true),
        'mercadopago' => env('PAYMENT_MERCADOPAGO_ENABLED', true),
        'stripe' => env('PAYMENT_STRIPE_ENABLED', true),
    ],
];
