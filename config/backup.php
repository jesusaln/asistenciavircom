<?php

// config/backup.php (configuración opcional)
return [
    'disk' => env('BACKUP_DISK', 'local'),

    'path' => env('BACKUP_PATH', 'backups/database/'),

    // Ruta para respaldos completos de aplicación (BD + archivos)
    'full_backup_path' => env('BACKUP_FULL_PATH', 'backups/application/'),

    'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),

    'compression' => [
        'enabled' => env('BACKUP_COMPRESSION', true),
        'level' => env('BACKUP_COMPRESSION_LEVEL', 6), // 0-9
    ],

    'cleanup' => [
        'enabled' => env('BACKUP_CLEANUP_ENABLED', true),
        'days_old' => env('BACKUP_CLEANUP_DAYS', 30),
    ],

    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
        'frequency' => env('BACKUP_SCHEDULE_FREQUENCY', 'daily'), // daily, weekly, monthly
        'time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de notificaciones avanzado
    |
    */

    'notifications' => [
        'email_enabled' => env('BACKUP_EMAIL_NOTIFICATIONS', true),
        'slack_enabled' => env('BACKUP_SLACK_NOTIFICATIONS', false),

        // Destinatarios de email
        'email_to' => env('BACKUP_EMAIL_TO', 'admin@example.com'),

        // Webhook de Slack
        'slack_webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),

        // Eventos que generan notificaciones
        'notify_on_backup_success' => env('BACKUP_NOTIFY_SUCCESS', true),
        'notify_on_backup_failure' => env('BACKUP_NOTIFY_FAILURE', true),
        'notify_on_restore' => env('BACKUP_NOTIFY_RESTORE', true),
        'notify_on_security_issues' => env('BACKUP_NOTIFY_SECURITY', true),
        'notify_admin_on_critical_errors' => env('BACKUP_NOTIFY_CRITICAL', true),

        // Reportes programados
        'daily_digest_enabled' => env('BACKUP_DAILY_DIGEST', false),
        'weekly_report_enabled' => env('BACKUP_WEEKLY_REPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Seguridad
    |--------------------------------------------------------------------------
    |
    | Configuración para características de seguridad avanzada
    |
    */

    'security' => [
        'encrypt_sensitive_data' => env('BACKUP_ENCRYPT_SENSITIVE', true),
        'use_checksums' => env('BACKUP_USE_CHECKSUMS', true),
        'backup_integrity_check' => env('BACKUP_INTEGRITY_CHECK', true),
        'max_backup_age_days' => env('BACKUP_MAX_AGE_DAYS', 90),
        'require_password_confirmation' => env('BACKUP_REQUIRE_CONFIRMATION', true),

        // Tablas sensibles que requieren encriptación especial
        'sensitive_tables' => [
            'users',
            'clientes',
            'personal_access_tokens',
            'password_resets',
            'failed_jobs',
            'user_notifications',
            'bitacora_actividades'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Retención Avanzada
    |--------------------------------------------------------------------------
    |
    | Políticas de retención y limpieza automática mejoradas
    |
    */

    'retention' => [
        'daily_backups' => env('BACKUP_RETENTION_DAILY', 7),     // Días de respaldos diarios
        'weekly_backups' => env('BACKUP_RETENTION_WEEKLY', 4),   // Semanas de respaldos semanales
        'monthly_backups' => env('BACKUP_RETENTION_MONTHLY', 12), // Meses de respaldos mensuales
        'auto_cleanup' => env('BACKUP_AUTO_CLEANUP', true),
        'max_total_backups' => env('BACKUP_MAX_TOTAL', 100),
        'smart_cleanup' => env('BACKUP_SMART_CLEANUP', true),    // Limpieza inteligente basada en espacio
    ],

    // Archivos a incluir/excluir en respaldos completos
    'files' => [
        // Directorios a incluir (relativos al root del proyecto)
        'include_paths' => [
            'storage/app/public',   // fotos y archivos subidos
            'storage/csd',          // certificados si existen
            'public/uploads',       // uploads directos (si existe)
        ],
        // Patrones/directorios a excluir
        'exclude' => [
            'storage/app/backups',
            'storage/app/private/backups',  // Respaldos de BD y aplicación
            'storage/backups',              // Respaldos legacy
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/logs',                 // Logs no necesarios en backup
            'node_modules',
            'vendor',
            '.git',
        ],
    ],
];
