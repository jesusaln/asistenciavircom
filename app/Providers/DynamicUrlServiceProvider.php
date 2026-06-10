<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

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

        $request = $this->getCurrentRequest();

        if ($request && $this->shouldUseRequestUrl($request)) {
            $this->applyRequestUrl($request);
            return;
        }

        try {
            // Get configuration from database (fails gracefully if DB/table is missing)
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
                    // config(['app.asset_url' => $appUrl]); // DISABLED: Causes CORS issues with mixed domains
                }

                // --- CONFIGURACIÓN GLOBAL DE CORREO (SMTP) ---
                if (!empty($config->smtp_host)) {
                    // Sanitizar: la BD podría guardar "null" (string) en vez de NULL real
                    $smtpEncryption = $config->smtp_encryption;
                    if ($smtpEncryption === 'null' || $smtpEncryption === '') {
                        $smtpEncryption = null;
                    }

                    config([
                        'mail.mailers.smtp.host' => $config->smtp_host,
                        'mail.mailers.smtp.port' => $config->smtp_port,
                        'mail.mailers.smtp.username' => $config->smtp_username,
                        'mail.mailers.smtp.password' => $config->smtp_password,
                        'mail.mailers.smtp.encryption' => $smtpEncryption,
                        'mail.mailers.smtp.stream' => [
                            'ssl' => [
                                'allow_self_signed' => true,
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                            ],
                        ],
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

    private function getCurrentRequest(): ?Request
    {
        try {
            return request();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function shouldUseRequestUrl(Request $request): bool
    {
        $host = strtolower($request->getHost());

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (str_ends_with($host, '.nip.io')) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_IP)
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    private function applyRequestUrl(Request $request): void
    {
        $scheme = $request->isSecure() ? 'https' : 'http';
        $host = $request->getHttpHost();
        $appUrl = "{$scheme}://{$host}";

        config([
            'app.url' => $appUrl,
            'session.domain' => null,
            'session.secure' => $request->isSecure(),
        ]);

        URL::forceRootUrl($appUrl);
    }
}
