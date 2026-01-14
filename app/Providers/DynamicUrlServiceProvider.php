<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DynamicUrlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     * 
     * Overrides APP_URL with the value from database configuration (app_url field).
     * This allows changing the application URL from the admin panel without editing .env
     */
    public function boot(): void
    {
        // Skip during tests or if in local/development environment
        if (app()->runningUnitTests() || app()->environment('local')) {
            return;
        }

        try {
            // Check if table exists
            if (!Schema::hasTable('empresa_configuracion')) {
                return;
            }

            // Get configuration from database
            $config = \App\Models\EmpresaConfiguracion::getConfig();

            if ($config) {
                $appUrl = $config->app_url;
                $forceHttps = $config->force_https;

                // --- SOPORTE INTELIGENTE PARA ZEROTIER ---
                // Si el usuario accede por la IP de ZeroTier, usamos esa IP como URL base
                // para evitar errores de certificado SSL (ERR_CERT_COMMON_NAME_INVALID)
                if (isset($_SERVER['HTTP_HOST'])) {
                    $currentHost = $_SERVER['HTTP_HOST'];
                    $ztIp = $config->zerotier_ip;

                    if (!empty($ztIp) && (str_contains($currentHost, $ztIp) || $currentHost === $ztIp)) {
                        $appUrl = 'http://' . $currentHost;
                        $forceHttps = false; // ZeroTier usualmente no tiene SSL en la IP
                    }
                }

                if (!empty($appUrl)) {
                    // Ensure URL has protocol
                    if (!str_starts_with($appUrl, 'http://') && !str_starts_with($appUrl, 'https://')) {
                        $appUrl = ($forceHttps ? 'https://' : 'http://') . $appUrl;
                    }

                    // Override Laravel's APP_URL
                    config(['app.url' => $appUrl]);

                    if ($forceHttps || str_starts_with($appUrl, 'https://')) {
                        URL::forceScheme('https');
                        // Asegurar que las URLs generadas usen https
                        if (isset($_SERVER['HTTPS'])) {
                            $_SERVER['HTTPS'] = 'on';
                        }
                    }

                    // Set asset URL as well
                    config(['app.asset_url' => $appUrl]);
                }

                // --- CONFIGURACIÓN GLOBAL DE CORREO (SMTP) ---
                if (!empty($config->smtp_host)) {
                    config([
                        'mail.mailers.smtp.host' => $config->smtp_host,
                        'mail.mailers.smtp.port' => $config->smtp_port,
                        'mail.mailers.smtp.username' => $config->smtp_username,
                        'mail.mailers.smtp.password' => $config->smtp_password,
                        'mail.mailers.smtp.encryption' => $config->smtp_encryption,
                        'mail.from.address' => $config->email_from_address,
                        'mail.from.name' => $config->email_from_name,
                    ]);

                    // Si se configuró SMTP, lo ponemos como default
                    config(['mail.default' => 'smtp']);

                    // Asegurar que Laravel use la nueva configuración
                    \Illuminate\Support\Facades\Mail::purge('smtp');
                    app()->forgetInstance('mail.manager');
                }
            }
        } catch (\Throwable $e) {
            Log::debug('DynamicUrlServiceProvider: Could not load URL configuration - ' . $e->getMessage());
        }
    }
}
