<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_razon_social',
        'tipo_persona',
        'tipo_identificacion',
        'identificacion',
        'curp',
        'rfc',
        'regimen_fiscal',
        'uso_cfdi',
        'email',
        'telefono',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',
        'pais',
        // WhatsApp fields
        'whatsapp_enabled',
        'whatsapp_business_account_id',
        'whatsapp_phone_number_id',
        'whatsapp_sender_phone',
        'whatsapp_access_token',
        'whatsapp_app_secret',
        'whatsapp_webhook_verify_token',
        'whatsapp_default_language',
        'whatsapp_template_payment_reminder',
        'whatsapp_template_maintenance',
        'whatsapp_chatbot_enabled',
        'whatsapp_chatbot_prompt',
        'whatsapp_chatbot_mode',
    ];

    protected $casts = [
        'whatsapp_access_token' => 'encrypted',
        'whatsapp_app_secret' => 'encrypted',
        'whatsapp_enabled' => 'boolean',
        'whatsapp_chatbot_enabled' => 'boolean',
    ];

    protected $hidden = [
        'whatsapp_access_token',
        'whatsapp_app_secret',
        'whatsapp_webhook_verify_token',
    ];

    protected $attributes = [
        'tipo_persona' => 'moral',
        'regimen_fiscal' => '601',
        'uso_cfdi' => 'G03',
        'pais' => 'México',
        'whatsapp_enabled' => false,
        'whatsapp_default_language' => 'es_MX',
    ];

    /**
     * Alias para nombre_razon_social o nombre comercial
     */
    public function getNombreEmpresaAttribute(): string
    {
        return $this->nombre_razon_social ?? 'Empresa';
    }

    /**
     * Dirección formateada
     */
    public function getDireccionCompletaAttribute(): string
    {
        $partes = array_filter([
            $this->calle,
            trim($this->numero_exterior . ($this->numero_interior ? " Int. {$this->numero_interior}" : '')),
            $this->colonia,
            $this->codigo_postal,
            $this->municipio,
            $this->estado
        ]);

        return implode(', ', $partes);
    }

    /**
     * Ciudad y Estado
     */
    public function getCiudadAttribute(): string
    {
        return trim(($this->municipio ?? '') . ', ' . ($this->estado ?? ''));
    }

    /**
     * Boot method para limpiar caché de configuración cuando se actualiza empresa
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            // Limpiar caché de configuración cuando se actualiza empresa
            EmpresaConfiguracion::clearCache();
        });

        static::creating(function (Empresa $empresa) {
            $empresa->tipo_persona = $empresa->tipo_persona ?: 'moral';
            $empresa->regimen_fiscal = $empresa->regimen_fiscal ?: '601';
            $empresa->uso_cfdi = $empresa->uso_cfdi ?: 'G03';
            $empresa->pais = $empresa->pais ?: 'México';
            $empresa->whatsapp_default_language = $empresa->whatsapp_default_language ?: 'es_MX';

            if (empty($empresa->rfc)) {
                $empresa->rfc = 'EMP' . now()->format('ymd') . strtoupper(Str::random(4));
            }
        });

        static::deleted(function () {
            // Limpiar caché de configuración cuando se elimina empresa
            EmpresaConfiguracion::clearCache();
        });
    }
}
