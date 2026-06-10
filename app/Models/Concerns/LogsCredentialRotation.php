<?php

namespace App\Models\Concerns;

use App\Models\CredentialRotation;

trait LogsCredentialRotation
{
    /**
     * Campos que deben auditar su rotación
     */
    protected array $sensitiveFields = [
        'smtp_password',
        'fiel_password',
        'csd_password',
        'pac_apikey',
        'mercadopago_access_token',
        'paypal_client_secret',
        'stripe_secret_key',
        'gdrive_refresh_token',
        'cva_password',
        'google_client_secret'
    ];

    public static function bootLogsCredentialRotation()
    {
        static::updating(function ($model) {
            foreach ($model->sensitiveFields as $field) {
                if ($model->isDirty($field)) {
                    $provider = $model->resolveProviderForField($field);
                    CredentialRotation::record($field, $provider, $model->empresa_id);
                }
            }
        });
    }

    /**
     * Resolver el proveedor basado en el nombre del campo
     */
    protected function resolveProviderForField(string $field): string
    {
        if (str_contains($field, 'cva'))
            return 'CVA';
        if (str_contains($field, 'stripe'))
            return 'Stripe';
        if (str_contains($field, 'paypal'))
            return 'PayPal';
        if (str_contains($field, 'mercadopago'))
            return 'MercadoPago';
        if (str_contains($field, 'gdrive') || str_contains($field, 'google'))
            return 'Google';
        if (str_contains($field, 'pac'))
            return 'PAC Facturación';
        if (str_contains($field, 'smtp'))
            return 'Email/SMTP';
        if (str_contains($field, 'fiel') || str_contains($field, 'csd'))
            return 'SAT (Certificados)';

        return 'Sistema';
    }
}
