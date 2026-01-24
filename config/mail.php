<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'verify_peer' => false,
            'verify_peer_name' => false,
            'stream' => [
                'ssl' => [
                    'allow_self_signed' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'security_level' => 0,
                ],
            ],
        ],

        'newsletter' => [
            'transport' => 'smtp',
            'host' => env('NEWSLETTER_MAIL_HOST', env('MAIL_HOST')),
            'port' => env('NEWSLETTER_MAIL_PORT', env('MAIL_PORT')),
            'username' => env('NEWSLETTER_MAIL_USERNAME'),
            'password' => env('NEWSLETTER_MAIL_PASSWORD'),
            'from' => [
                'address' => env('NEWSLETTER_MAIL_FROM_ADDRESS'),
                'name' => 'Asistencia Vircom Blog',
            ],
            'encryption' => env('NEWSLETTER_MAIL_ENCRYPTION', env('MAIL_ENCRYPTION')),
            'timeout' => null,
            'verify_peer' => false,
            'verify_peer_name' => false,
            'stream' => [
                'ssl' => [
                    'allow_self_signed' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'security_level' => 0,
                ],
            ],
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    // Destinatario por defecto para alertas de mantenimiento
    'alertas_mantenimiento_to' => env('ALERTAS_MANTENIMIENTO_TO', 'jesuslopeznoriega@hotmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Email Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuración para limitar el envío de correos y evitar bloqueos
    | por parte del proveedor de email.
    |
    | burst_limit: Máximo de correos en el período de ráfaga
    | burst_window: Período de ráfaga en minutos
    | daily_limit: Máximo de correos por día
    |
    */
    'rate_limit' => [
        'enabled' => env('MAIL_RATE_LIMIT_ENABLED', true),
        'burst_limit' => env('MAIL_RATE_LIMIT_BURST', 10),      // máximo 10 correos
        'burst_window' => env('MAIL_RATE_LIMIT_WINDOW', 10),    // en 10 minutos
        'daily_limit' => env('MAIL_RATE_LIMIT_DAILY', 100),     // máximo 100 correos al día
    ],

];
