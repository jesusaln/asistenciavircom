<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToEmpresa;
use App\Support\EmpresaResolver;

class EmpresaConfiguracion extends Model
{
    use Concerns\LogsCredentialRotation;

    protected $table = 'empresa_configuracion';

    protected $appends = ['logo_url', 'favicon_url', 'logo_reportes_url', 'direccion_completa', 'firma_digital_url'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $fillable = [
        'id',
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
        'firma_digital',
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
        // Shipping
        'shipping_local_cp_prefix',
        'shipping_local_cost',
        'n8n_webhook_blog',
        // Google Gemini AI
        'gemini_api_key',
        'gemini_model',
        'gemini_temperature',
        // AI Configuration (original fields)
        'ai_provider',
        'groq_api_key',
        'groq_model',
        'groq_temperature',
        'ollama_base_url',
        'ollama_model',
        'chatbot_enabled',
        'chatbot_system_prompt',
        'chatbot_name',
        'pin_auditoria',
        'repse_number',
        'repse_expiry',
        'repse_activity',
        'repse_constancia_path',
        'acta_constitutiva_path',
        'registro_patronal_imss',
        'repse_alert_days',
        'audit_contact_email',
        'curp_pdf_path',
        'csf_pdf_path',
        'repse_constancia_name',
        'acta_constitutiva_name',
        'curp_pdf_name',
        'csf_pdf_name',
        'meli_active',
        'meli_app_id',
        'meli_client_secret',
        'meli_access_token',
        'meli_refresh_token',
        'meli_user_id',
        'meli_token_expires_at',
        'meli_site_id',
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
        'repse_expiry' => 'date',
        'registro_patronal_imss' => 'array',
        'repse_alert_days' => 'integer',
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
        'shipping_local_cost' => 'decimal:2',
        'dias_gracia_corte' => 'integer',
        // AI
        'gemini_api_key' => 'encrypted',
        'gemini_temperature' => 'decimal:2',
        'groq_api_key' => 'encrypted',
        'groq_temperature' => 'decimal:2',
        'chatbot_enabled' => 'boolean',
        'cva_utility_tiers' => 'array',
        'cva_active' => 'boolean',
        'cva_tipo_cambio_auto' => 'boolean',
        'cva_tipo_cambio' => 'decimal:4',
        'cva_utility_percentage' => 'decimal:2',
        'cva_codigo_sucursal' => 'integer',
        'cva_paqueteria_envio' => 'integer',
        'cva_monedero_balance' => 'decimal:2',
        'meli_active' => 'boolean',
        'meli_access_token' => 'encrypted',
        'meli_client_secret' => 'encrypted',
        'meli_user_id' => 'integer',
        'meli_token_expires_at' => 'datetime',
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
        'google_client_secret',
        'mercadopago_access_token',
        'paypal_client_secret',
        'stripe_secret_key', // Stripe
        'stripe_webhook_secret', // Stripe
        // Respaldos Cloud (Seguridad)
        'gdrive_client_secret',
        'gdrive_access_token',
        'gdrive_refresh_token',
        'gemini_api_key',
        'groq_api_key',
        'meli_client_secret',
        'meli_access_token',
        'meli_refresh_token',
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
            'rfc' => 'CDD123456ABC',
            'razon_social' => 'CLIMAS DEL DESIERTO S.A. DE C.V.',
            'color_principal' => '#F59E0B',
            'color_secundario' => '#D97706',
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

            $empresaId = $empresaId ?: EmpresaResolver::resolveId();
            $connection = config('database.default');
            $cacheKey = $empresaId ? "empresa_configuracion_{$connection}_{$empresaId}" : "empresa_configuracion_{$connection}";

            $config = Cache::remember($cacheKey, 300, function () use ($empresaId) {
                if ($empresaId) {
                    return self::find($empresaId);
                }
                return self::first();
            });

            if (!$config && $empresaId) {
                $config = self::create([
                    'id' => $empresaId,
                    'nombre_empresa' => $defaultConfig->nombre_empresa,
                    'rfc' => $defaultConfig->rfc,
                    'iva_porcentaje' => $defaultConfig->iva_porcentaje,
                    'moneda' => $defaultConfig->moneda,
                ]);
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

    public function getEmpresaIdAttribute(): ?int
    {
        return $this->id;
    }

    /**
     * Get the WhatsApp phone number formatted for international use.
     * Default country code prefix is 52 (Mexico) for 10-digit Hermosillo/Mexico numbers.
     */
    public function getWhatsappAttribute($value)
    {
        // Fallback to phone number if whatsapp is empty
        $numberObj = $value ?: $this->telefono;
        if (!$numberObj) {
            return null;
        }

        // Clean all non-digit characters
        $clean = preg_replace('/\D/', '', $numberObj);

        // Prepend 52 if it is a standard 10-digit Mexican phone number
        if (strlen($clean) === 10) {
            return '52' . $clean;
        }

        return $clean;
    }


    /**
     * Obtener URL completa del logo
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo_path)) {
            return \App\Helpers\UrlHelper::storageUrl($this->logo_path);
        }
        return null;
    }

    /**
     * Obtener URL completa del favicon
     */
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->favicon_path)) {
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
     * Obtener URL completa de la firma digital.
     * Compatible con rutas de archivo (nuevo) y base64 legacy (datos históricos).
     */
    public function getFirmaDigitalUrlAttribute()
    {
        if ($this->firma_digital) {
            return \App\Helpers\Base64ToFile::getUrl($this->firma_digital);
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
    public static function getInfoEmpresa(?int $empresaId = null)
    {
        $config = self::getConfig($empresaId);

        // Calcular ruta absoluta del logo para DomPDF y convertir a Base64
        $logoPathAbsolute = null;
        $logoBase64 = null;

        if ($config->logo_path) {
            $logoPathAbsolute = storage_path('app/public/' . $config->logo_path);

            if (file_exists($logoPathAbsolute)) {
                try {
                    $type = strtolower(pathinfo($logoPathAbsolute, PATHINFO_EXTENSION));
                    $data = file_get_contents($logoPathAbsolute);

                    // ✅ FIX: DomPDF no soporta WebP. Convertimos a JPEG o lo omitimos.
                    if ($type === 'webp') {
                        $converted = false;

                        // Intentar con GD
                        if (!$converted && function_exists('imagecreatefromwebp')) {
                            try {
                                $img = @imagecreatefromwebp($logoPathAbsolute);
                                if ($img) {
                                    ob_start();
                                    imagejpeg($img, null, 90);
                                    $data = ob_get_clean();
                                    imagedestroy($img);
                                    $type = 'jpeg';
                                    $converted = true;
                                }
                            } catch (\Throwable $e) {
                                \Log::warning('GD WebP conversion failed: ' . $e->getMessage());
                            }
                        }

                        // Intentar con Imagick
                        if (!$converted && class_exists('Imagick')) {
                            try {
                                $imagick = new \Imagick($logoPathAbsolute);
                                $imagick->setImageFormat('jpeg');
                                $imagick->setImageCompressionQuality(90);
                                $data = $imagick->getImageBlob();
                                $imagick->destroy();
                                $type = 'jpeg';
                                $converted = true;
                            } catch (\Throwable $e) {
                                \Log::warning('Imagick WebP conversion failed: ' . $e->getMessage());
                            }
                        }

                        // Si no se pudo convertir, omitir el logo para evitar crash de DomPDF
                        if (!$converted) {
                            \Log::warning('Logo WebP no puede ser convertido. Se omitirá el logo en el PDF. Instale ext-gd con soporte WebP o ext-imagick.');
                            $logoBase64 = null;
                            $logoPathAbsolute = null;
                        }
                    }

                    // Solo asignar base64 si no fue anulado arriba
                    if ($logoBase64 === null && $type !== 'webp') {
                        // Tipo ya fue convertido a jpeg arriba
                    }
                    if ($data && $type !== 'webp') {
                        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error convirtiendo logo a base64: ' . $e->getMessage());
                    $logoBase64 = null;
                }
            } else {
                $logoPathAbsolute = null;
            }
        }

        // ✅ FIX: Firma Digital Base64 (compatible con rutas de archivo y base64 legacy)
        $firmaBase64 = null;
        if ($config->firma_digital) {
            // Si es base64 legacy (aún no migrado), usarlo directamente
            if (str_starts_with($config->firma_digital, 'data:')) {
                $firmaBase64 = $config->firma_digital;
            } else {
                // Es una ruta de archivo - leer y convertir a base64 para PDF
                $firmaPath = storage_path('app/public/' . $config->firma_digital);
                if (file_exists($firmaPath)) {
                    try {
                        $fType = strtolower(pathinfo($firmaPath, PATHINFO_EXTENSION));
                        $fData = file_get_contents($firmaPath);
                        if ($fData && $fType !== 'webp') {
                            $firmaBase64 = 'data:image/' . $fType . ';base64,' . base64_encode($fData);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error convirtiendo firma a base64: ' . $e->getMessage());
                    }
                }
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
            'firma_digital' => $config->firma_digital, // El path original
            'firma_base64' => $firmaBase64,
            'firma_digital_url' => $config->firma_digital ? \App\Helpers\UrlHelper::storageUrl($config->firma_digital) : null,
        ];
    }

    /**
     * Obtener configuración de colores
     */
    public static function getColores(?int $empresaId = null)
    {
        $config = self::getConfig($empresaId);

        return [
            'principal' => $config->color_principal,
            'secundario' => $config->color_secundario,
            'terciario' => $config->color_terciario,
        ];
    }

    /**
     * Obtener configuración financiera
     */
    public static function getConfiguracionFinanciera(?int $empresaId = null)
    {
        $config = self::getConfig($empresaId);

        return [
            'iva_porcentaje' => $config->iva_porcentaje,
            'moneda' => $config->moneda,
            'formato_numeros' => $config->formato_numeros,
        ];
    }

    /**
     * Obtener pie de página para documentos
     */
    public static function getPiePagina($tipo = 'facturas', ?int $empresaId = null)
    {
        $config = self::getConfig($empresaId);

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
