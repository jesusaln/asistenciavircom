<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToEmpresa;
use App\Support\EmpresaResolver;

class EmpresaConfiguracion extends Model
{
    use BelongsToEmpresa;

    protected $table = 'empresa_configuracion';

    protected $appends = ['logo_url', 'favicon_url', 'logo_reportes_url', 'direccion_completa'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $fillable = [
        'empresa_id',
        'nombre_empresa',
        'rfc',
        'razon_social',
        'regimen_fiscal',
        'calle',
        'numero_exterior',
        'numero_interior',
        'telefono',
        'whatsapp',
        'email',
        'sitio_web',
        'codigo_postal',
        'colonia',
        'ciudad',
        'estado',
        'pais',
        'logo_path',
        'favicon_path',
        'descripcion_empresa',
        'color_principal',
        'color_secundario',
        'color_terciario',
        'pie_pagina_facturas',
        'pie_pagina_cotizaciones',
        'pie_pagina_ventas',
        'terminos_condiciones',
        'politica_privacidad',
        'iva_porcentaje',
        'isr_porcentaje',
        'moneda',
        'formato_numeros',
        'mantenimiento',
        'mensaje_mantenimiento',
        'registro_usuarios',
        'notificaciones_email',
        'logo_reportes',
        'formato_fecha',
        'formato_hora',
        'backup_automatico',
        'frecuencia_backup',
        'retencion_backups',
        'backup_cloud_enabled',
        'backup_tipo',
        'backup_hora_completo',
        'email_cobros',
        'cobros_hora_reporte',
        'cobros_reporte_automatico',
        'cobros_dias_anticipacion',
        'email_pagos',
        'pagos_hora_reporte',
        'pagos_reporte_automatico',
        'pagos_dias_anticipacion',
        'intentos_login',
        'tiempo_bloqueo',
        'dias_gracia_corte',
        'requerir_2fa',
        // Datos bancarios existentes
        'banco',
        'sucursal',
        'cuenta',
        'clabe',
        'titular',
        // Datos bancarios adicionales
        'numero_cuenta',
        'numero_tarjeta',
        'nombre_titular',
        'informacion_adicional_bancaria',
        // Configuración de correo
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'email_from_address',
        'email_from_name',
        'email_reply_to',
        // Configuración DKIM
        'dkim_selector',
        'dkim_domain',
        'dkim_public_key',
        'dkim_enabled',
        // Configuración de modo oscuro
        'dark_mode_enabled',
        'dark_mode_primary_color',
        'dark_mode_secondary_color',
        'dark_mode_background_color',
        'dark_mode_surface_color',
        // Configuración Fiscal Adicional
        'enable_isr',
        'enable_retencion_iva',
        'enable_retencion_isr',
        'retencion_iva',
        'retencion_isr',
        // Certificados FIEL (e.firma)
        'fiel_cer_path',
        'fiel_key_path',
        'fiel_password',
        'fiel_valid_from',
        'fiel_valid_to',
        'fiel_serial',
        'fiel_rfc',
        // Certificados CSD (Sello Digital)
        'csd_cer_path',
        'csd_key_path',
        'csd_password',
        'csd_valid_from',
        'csd_valid_to',
        'csd_serial',
        'csd_rfc',
        'pac_nombre',
        'pac_base_url',
        'pac_apikey',
        'pac_produccion',
        // Configuración de Red y Dominio
        'dominio_principal',
        'dominio_secundario',
        'servidor_ipv4',
        'servidor_ipv6',
        'ssl_enabled',
        'ssl_certificado_path',
        'ssl_key_path',
        'ssl_ca_bundle_path',
        'ssl_fecha_expiracion',
        'ssl_proveedor',
        'app_url',
        'force_https',
        // Configuración ZeroTier VPN
        'zerotier_enabled',
        'zerotier_network_id',
        'zerotier_ip',
        'zerotier_node_id',
        'zerotier_notas',
        // Tienda en Línea - E-commerce
        'tienda_online_activa',
        'google_client_id',
        'google_client_secret',
        'microsoft_client_id',
        'microsoft_client_secret',
        'microsoft_tenant_id',
        'mercadopago_access_token',
        'mercadopago_public_key',
        'mercadopago_sandbox',
        'paypal_client_id',
        'paypal_client_secret',
        'paypal_sandbox',
        'stripe_public_key', // Stripe
        'stripe_secret_key',
        'stripe_webhook_secret',
        'stripe_sandbox',
        // Respaldos Cloud (Google Drive)
        'gdrive_enabled',
        'gdrive_client_id',
        'gdrive_client_secret',
        'gdrive_access_token',
        'gdrive_refresh_token',
        'gdrive_folder_id',
        'gdrive_folder_name',
        'gdrive_auto_backup',
        'gdrive_token_expires_at',
        'gdrive_last_sync',
        'cloud_provider',
        // Redes Sociales
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'youtube_url',
        'linkedin_url',
        'cuenta_id_paypal',
        'cuenta_id_mercadopago',
        'cuenta_id_stripe',
        // CVA API
        'cva_active',
        'cva_user',
        'cva_password',
        'cva_utility_percentage',
        'cva_utility_tiers',
        'cva_codigo_sucursal',
        'cva_paqueteria_envio',
        // Shipping
        'shipping_local_cp_prefix',
        'shipping_local_cost',
        'n8n_webhook_blog',
    ];

    protected $casts = [
        'mantenimiento' => 'boolean',
        'registro_usuarios' => 'boolean',
        'notificaciones_email' => 'boolean',
        'backup_automatico' => 'boolean',
        'requerir_2fa' => 'boolean',
        'dkim_enabled' => 'boolean',
        'dark_mode_enabled' => 'boolean',
        'iva_porcentaje' => 'decimal:2',
        'isr_porcentaje' => 'decimal:2',
        'intentos_login' => 'integer',
        'tiempo_bloqueo' => 'integer',
        'frecuencia_backup' => 'integer',
        'retencion_backups' => 'integer',
        'backup_cloud_enabled' => 'boolean',
        'cobros_reporte_automatico' => 'boolean',
        'enable_isr' => 'boolean',
        'enable_retencion_iva' => 'boolean',
        'enable_retencion_isr' => 'boolean',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        // Certificados - fechas
        'fiel_valid_from' => 'datetime',
        'fiel_valid_to' => 'datetime',
        'csd_valid_from' => 'datetime',
        'csd_valid_to' => 'datetime',
        // Contraseñas encriptadas
        'fiel_password' => 'encrypted',
        'csd_password' => 'encrypted',
        // 'smtp_password' => 'encrypted', // Temporalmente deshabilitado por error de desencriptación
        // Configuración de Red
        'ssl_enabled' => 'boolean',
        'force_https' => 'boolean',
        'ssl_fecha_expiracion' => 'date',
        'zerotier_enabled' => 'boolean',
        'pac_produccion' => 'boolean',
        // Tienda en Línea y Pagos
        'tienda_online_activa' => 'boolean',
        'mercadopago_sandbox' => 'boolean',
        'paypal_sandbox' => 'boolean',
        'stripe_sandbox' => 'boolean', // Stripe
        // Respaldos Cloud
        'gdrive_enabled' => 'boolean',
        'gdrive_auto_backup' => 'boolean',
        'gdrive_token_expires_at' => 'datetime',
        'gdrive_last_sync' => 'datetime',
        'cva_active' => 'boolean',
        'cva_utility_percentage' => 'decimal:2',
        'cva_utility_tiers' => 'array',
        'cva_codigo_sucursal' => 'integer',
        'cva_paqueteria_envio' => 'integer',
        'shipping_local_cost' => 'decimal:2',
        'dias_gracia_corte' => 'integer',
    ];

    /**
     * Campos ocultos en JSON (contraseñas y rutas de llaves privadas)
     */
    protected $hidden = [
        'fiel_password',
        'fiel_key_path',
        'csd_password',
        'csd_key_path',
        'smtp_password',
        'pac_apikey',
        // Rutas SSL (seguridad)
        'ssl_key_path',
        // Credenciales de pago (seguridad)
        'google_client_secret',
        'microsoft_client_secret',
        'mercadopago_access_token',
        'paypal_client_secret',
        'stripe_secret_key', // Stripe
        'stripe_webhook_secret', // Stripe
        // Respaldos Cloud (Seguridad)
        'gdrive_client_secret',
        'gdrive_access_token',
        'gdrive_refresh_token',
        'cva_password',
    ];

    /**
     * Obtener la configuración actual de la empresa
     * Si no existe, devuelve valores por defecto
     */
    public static function getConfig(?int $empresaId = null)
    {
        // Default config to return when database is unavailable
        $defaultConfig = new self;
        $defaultConfig->forceFill([
            'nombre_empresa' => 'CLIMAS DEL DESIERTO',
            'rfc' => 'LONJ880321KMA',
            'razon_social' => 'JESUS ALBERTO LOPEZ NORIEGA',
            'color_principal' => '#FF6B35',
            'color_secundario' => '#E55A2B',
            'iva_porcentaje' => 16.00,
            'moneda' => 'MXN',
            'backup_automatico' => true,
            'backup_hora_completo' => '18:00',
        ]);

        try {
            // Skip if running in console without database
            if (app()->runningInConsole() && !app()->bound('db')) {
                return $defaultConfig;
            }

            // Skip database query if table doesn't exist (during migrations)
            if (!\Illuminate\Support\Facades\Schema::hasTable('empresa_configuracion')) {
                return $defaultConfig;
            }

            $empresaId = $empresaId ?: EmpresaResolver::resolveId();
            $cacheKey = $empresaId ? "empresa_configuracion_{$empresaId}" : 'empresa_configuracion';

            $config = Cache::remember($cacheKey, 300, function () use ($empresaId) {
                // Check if column exists to avoid "Column not found" error on single-tenant schemas
                $hasEmpresaId = \Illuminate\Support\Facades\Schema::hasColumn('empresa_configuracion', 'empresa_id');

                if ($empresaId && $hasEmpresaId) {
                    return self::where('empresa_id', $empresaId)->first();
                }
                return self::first();
            });

            if (!$config && $empresaId) {
                // Check column again before insert attempts
                $hasEmpresaId = \Illuminate\Support\Facades\Schema::hasColumn('empresa_configuracion', 'empresa_id');

                if ($hasEmpresaId) {
                    $config = self::create([
                        'empresa_id' => $empresaId,
                        'nombre_empresa' => $defaultConfig->nombre_empresa,
                        'rfc' => $defaultConfig->rfc,
                        'iva_porcentaje' => $defaultConfig->iva_porcentaje,
                        'moneda' => $defaultConfig->moneda,
                        'backup_automatico' => $defaultConfig->backup_automatico,
                        'backup_hora_completo' => $defaultConfig->backup_hora_completo,
                    ]);
                }
            }

            return $config ?? $defaultConfig;

        } catch (\Throwable $e) {
            // Catch ANY error (Exception, Error, PDOException, etc.)
            // This ensures we never crash the app due to missing table
            return $defaultConfig;
        }
    }

    /**
     * Limpiar caché de configuración
     */
    public static function clearCache(?int $empresaId = null)
    {
        if ($empresaId) {
            Cache::forget("empresa_configuracion_{$empresaId}");
            return;
        }

        $resolvedId = EmpresaResolver::resolveId();
        if ($resolvedId) {
            Cache::forget("empresa_configuracion_{$resolvedId}");
        }

        Cache::forget('empresa_configuracion');
    }

    /**
     * Obtener URL completa del logo
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            return \App\Helpers\UrlHelper::storageUrl($this->logo_path);
        }
        return null;
    }

    /**
     * Obtener URL completa del favicon
     */
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon_path) {
            return \App\Helpers\UrlHelper::storageUrl($this->favicon_path);
        }
        return null;
    }

    /**
     * Obtener URL completa del logo para reportes
     */
    public function getLogoReportesUrlAttribute()
    {
        if ($this->logo_reportes) {
            return \App\Helpers\UrlHelper::storageUrl($this->logo_reportes);
        } elseif ($this->logo_path) {
            return \App\Helpers\UrlHelper::storageUrl($this->logo_path);
        }
        return null;
    }

    /**
     * Obtener dirección completa formateada
     */
    public function getDireccionCompletaAttribute()
    {
        // Construir dirección con calle y números
        $direccionPartes = array_filter([
            $this->calle,
            $this->numero_exterior ? 'No. ' . $this->numero_exterior : null,
            $this->numero_interior ? 'Int. ' . $this->numero_interior : null,
        ]);

        $direccion = implode(' ', $direccionPartes);

        // Agregar resto de información incluyendo colonia
        $partes = array_filter([
            $direccion,
            $this->colonia,
            $this->codigo_postal ? 'C.P. ' . $this->codigo_postal : null,
            $this->ciudad,
            $this->estado,
            $this->pais,
        ]);

        return implode(', ', $partes);
    }

    /**
     * Verificar si el sistema está en modo mantenimiento
     */
    public static function enMantenimiento()
    {
        $config = self::getConfig();
        return $config->mantenimiento;
    }

    /**
     * Obtener mensaje de mantenimiento
     */
    public static function mensajeMantenimiento()
    {
        $config = self::getConfig();
        return $config->mensaje_mantenimiento;
    }

    /**
     * Obtener información básica de la empresa para documentos
     */
    public static function getInfoEmpresa()
    {
        $config = self::getConfig();

        // Calcular ruta absoluta del logo para DomPDF y convertir a Base64
        $logoPathAbsolute = null;
        $logoBase64 = null;

        if ($config->logo_path) {
            $logoPathAbsolute = storage_path('app/public/' . $config->logo_path);

            if (file_exists($logoPathAbsolute)) {
                try {
                    $type = pathinfo($logoPathAbsolute, PATHINFO_EXTENSION);
                    $data = file_get_contents($logoPathAbsolute);
                    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                } catch (\Exception $e) {
                    \Log::error('Error convirtiendo logo a base64: ' . $e->getMessage());
                    $logoBase64 = null;
                }
            } else {
                $logoPathAbsolute = null;
            }
        }

        return [
            'nombre' => $config->nombre_empresa,
            'rfc' => $config->rfc,
            'razon_social' => $config->razon_social,
            'direccion' => $config->direccion_completa,
            'telefono' => $config->telefono,
            'email' => $config->email,
            'sitio_web' => $config->sitio_web,
            'logo_url' => $config->logo_url,
            'logo_path_absolute' => $logoPathAbsolute,
            'logo_base64' => $logoBase64,
        ];
    }

    /**
     * Obtener configuración de colores
     */
    public static function getColores()
    {
        $config = self::getConfig();

        return [
            'principal' => $config->color_principal,
            'secundario' => $config->color_secundario,
            'terciario' => $config->color_terciario,
        ];
    }

    /**
     * Obtener configuración financiera
     */
    public static function getConfiguracionFinanciera()
    {
        $config = self::getConfig();

        return [
            'iva_porcentaje' => $config->iva_porcentaje,
            'moneda' => $config->moneda,
            'formato_numeros' => $config->formato_numeros,
        ];
    }

    /**
     * Obtener pie de página para documentos
     */
    public static function getPiePagina($tipo = 'facturas')
    {
        $config = self::getConfig();

        switch ($tipo) {
            case 'cotizaciones':
                return $config->pie_pagina_cotizaciones;
            case 'ventas':
                return $config->pie_pagina_ventas;
            case 'facturas':
            default:
                return $config->pie_pagina_facturas;
        }
    }

    /**
     * Override para guardar y limpiar caché
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }
}
