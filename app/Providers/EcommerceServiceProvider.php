<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\EmpresaConfiguracion;

class EcommerceServiceProvider extends ServiceProvider
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
     */
    public function boot(): void
    {
        // Solo cargar en requests web, no en consola o queue
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            // Intentar cargar configuración desde BD
            $config = EmpresaConfiguracion::getConfig();

            if ($config && $config->tienda_online_activa) {
                // Google OAuth
                if ($config->google_client_id) {
                    config([
                        'services.google.client_id' => $config->google_client_id,
                        'services.google.client_secret' => $config->google_client_secret,
                    ]);
                }

                // Microsoft OAuth
                if ($config->microsoft_client_id) {
                    config([
                        'services.microsoft.client_id' => $config->microsoft_client_id,
                        'services.microsoft.client_secret' => $config->microsoft_client_secret,
                        'services.microsoft.tenant' => $config->microsoft_tenant_id ?? 'common',
                    ]);
                }

                // MercadoPago
                if ($config->mercadopago_access_token) {
                    config([
                        'services.mercadopago.access_token' => $config->mercadopago_access_token,
                        'services.mercadopago.public_key' => $config->mercadopago_public_key,
                        'services.mercadopago.sandbox' => $config->mercadopago_sandbox,
                    ]);
                }

                // PayPal
                if ($config->paypal_client_id) {
                    config([
                        'services.paypal.client_id' => $config->paypal_client_id,
                        'services.paypal.client_secret' => $config->paypal_client_secret,
                        'services.paypal.mode' => $config->paypal_sandbox ? 'sandbox' : 'live',
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Fallo silencioso - la BD puede no estar disponible
        }
    }
}
