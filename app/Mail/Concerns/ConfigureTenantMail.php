<?php

namespace App\Mail\Concerns;

use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Mail;

trait ConfigureTenantMail
{
    /**
     * Override the send method to dynamically swap SMTP settings.
     */
    public function send($mailer)
    {
        $empresaId = $this->getEmpresaIdForTenantMail();
        if ($empresaId) {
            $config = EmpresaConfiguracion::getConfig($empresaId);
            if ($config && !empty($config->smtp_host)) {
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
                    'mail.from.address' => $config->email_from_address ?: config('mail.from.address'),
                    'mail.from.name' => $config->email_from_name ?: config('mail.from.name'),
                ]);

                // Clear/purge the resolved SMTP mailer to force recreation with new settings
                Mail::purge('smtp');
            }
        }

        return parent::send($mailer);
    }

    /**
     * Extract the empresa_id from available properties.
     */
    protected function getEmpresaIdForTenantMail(): ?int
    {
        if (isset($this->empresaId)) {
            return $this->empresaId;
        }
        if (isset($this->empresa) && is_object($this->empresa) && isset($this->empresa->id)) {
            return $this->empresa->id;
        }
        if (isset($this->cfdi) && is_object($this->cfdi) && isset($this->cfdi->empresa_id)) {
            return $this->cfdi->empresa_id;
        }
        if (isset($this->venta) && is_object($this->venta) && isset($this->venta->empresa_id)) {
            return $this->venta->empresa_id;
        }
        if (isset($this->poliza) && is_object($this->poliza) && isset($this->poliza->empresa_id)) {
            return $this->poliza->empresa_id;
        }
        if (isset($this->ordenCompra) && is_object($this->ordenCompra) && isset($this->ordenCompra->empresa_id)) {
            return $this->ordenCompra->empresa_id;
        }
        if (isset($this->orden) && is_object($this->orden) && isset($this->orden->empresa_id)) {
            return $this->orden->empresa_id;
        }
        if (isset($this->compra) && is_object($this->compra) && isset($this->compra->empresa_id)) {
            return $this->compra->empresa_id;
        }
        if (isset($this->datos) && is_array($this->datos)) {
            if (isset($this->datos['mantenimiento']) && is_object($this->datos['mantenimiento']) && isset($this->datos['mantenimiento']->empresa_id)) {
                return $this->datos['mantenimiento']->empresa_id;
            }
            if (isset($this->datos['empresa_id'])) {
                return $this->datos['empresa_id'];
            }
        }
        if (isset($this->cliente) && is_object($this->cliente) && isset($this->cliente->empresa_id)) {
            return $this->cliente->empresa_id;
        }
        return \App\Support\EmpresaResolver::resolveId();
    }
}
