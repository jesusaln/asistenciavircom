<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use App\Models\Concerns\BelongsToEmpresa;

class Cliente extends Authenticatable implements AuditableContract, CanResetPasswordContract
{
    use HasFactory, Notifiable, SoftDeletes, AuditableTrait, CanResetPassword, BelongsToEmpresa;

    protected $table = 'clientes';

    protected $fillable = [
        'codigo',
        'empresa_id',
        'nombre_razon_social',
        'razon_social',        // Razón social para facturación (puede ser diferente al nombre comercial)
        'tipo_persona',
        'tipo_identificacion',
        'identificacion',
        'curp',
        'rfc',
        'regimen_fiscal',  // clave SAT c_RegimenFiscal
        'uso_cfdi',        // clave SAT c_UsoCFDI
        'forma_pago_default', // clave SAT c_FormaPago (forma de pago preferida)
        'domicilio_fiscal_cp', // Código postal del domicilio fiscal (CFDI 4.0)
        'domicilio_fiscal_calle',
        'domicilio_fiscal_numero',
        'domicilio_fiscal_colonia',
        'domicilio_fiscal_municipio',
        'domicilio_fiscal_estado',
        'misma_direccion_fiscal', // Si el domicilio fiscal es igual al de servicio
        'residencia_fiscal',   // c_Pais para extranjeros (CFDI 4.0)
        'num_reg_id_trib',     // Número de registro fiscal extranjero (CFDI 4.0)
        'email',
        'telefono',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado',          // clave SAT de 3 letras (AGU, SON, etc.)
        'pais',            // 'MX'
        'activo',
        'requiere_factura', // Indica si el cliente requiere facturación electrónica
        'notas',
        'price_list_id',   // Lista de precios asignada

        // ------ WhatsApp Consent (Meta Platform Terms Compliance) ------
        'whatsapp_optin',           // Consentimiento explícito para WhatsApp
        'whatsapp_consent_date',    // Fecha de consentimiento
        'whatsapp_consent_method',  // Método de obtención de consentimiento
        'whatsapp_consent_source',  // Origen del consentimiento

        // ------ Facturación ------
        'cfdi_default_use',
        'payment_form_default',

        // ------ Crédito ------
        'password',
        'credito_activo',
        'estado_credito',
        'limite_credito',
        'dias_credito',
        'dias_gracia',

        // ------ Crédito Firma ------
        'credito_firma',
        'credito_firmado_at',
        'credito_firmado_ip',
        'credito_firmado_nombre',
        'credito_firma_hash',
        'credito_solicitado_monto',
        'credito_solicitado_dias',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'activo' => 'boolean',
        'requiere_factura' => 'boolean',
        'whatsapp_optin' => 'boolean',
        'whatsapp_consent_date' => 'datetime',
        'credito_activo' => 'boolean',
        'limite_credito' => 'decimal:2',
        'dias_credito' => 'integer',
        'dias_gracia' => 'integer',
        'credito_firmado_at' => 'datetime',
        'credito_solicitado_monto' => 'decimal:2',
        'credito_solicitado_dias' => 'integer',
    ];

    protected $attributes = [
        'activo' => true,
        'uso_cfdi' => 'G03', // G03 - Gastos en general por defecto
        'credito_activo' => false,
        'estado_credito' => 'sin_credito',
        'limite_credito' => 0,
        'dias_credito' => 0,
        'dias_gracia' => null, // null = usa configuración global
        // 'pais' se deja sin valor por defecto para permitir extranjeros
    ];

    protected $auditExclude = [
        'updated_at',
    ];

    /**
     * Atributos calculados que se anexan al JSON
     */
    protected $appends = [
        'direccion_completa',
        // Attributes removed from appends to prevent N+1:
        // 'estado_nombre', 'regimen_descripcion', 'uso_cfdi_descripcion' via relations
        'nombre_fiscal', // Nombre limpio para CFDI 4.0
        // Crédito - REMOVED from default appends for performance (N+1)
        // 'saldo_pendiente',
        // 'credito_disponible',
    ];

    /**
     * Boot model events
     */
    protected static function booted()
    {
        static::creating(function (Cliente $cliente) {
            if (empty($cliente->codigo)) {
                try {
                    $cliente->codigo = app(\App\Services\Folio\FolioService::class)->getNextFolio('cliente');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for cliente: ' . $e->getMessage());
                }
            }
            if (is_null($cliente->activo)) {
                $cliente->activo = true;
            }
            if (empty($cliente->uso_cfdi)) {
                $cliente->uso_cfdi = 'G03'; // G03 - Gastos en general por defecto
            }
            // No forzar país a MX para permitir clientes extranjeros
        });

        static::saving(function (Cliente $cliente) {
            // Gestión automática de fechas de consentimiento de WhatsApp
            if ($cliente->isDirty('whatsapp_optin')) {
                if ($cliente->whatsapp_optin) {
                    $cliente->whatsapp_consent_date = now();
                    $cliente->whatsapp_consent_method = $cliente->whatsapp_consent_method ?? 'web';
                    $cliente->whatsapp_consent_source = $cliente->whatsapp_consent_source ?? 'system';
                } else {
                    $cliente->whatsapp_consent_date = null;
                    $cliente->whatsapp_consent_method = null;
                    $cliente->whatsapp_consent_source = null;
                }
            }
        });
    }

    // ------------------------------------------------------------------
    // Relaciones propias
    // ------------------------------------------------------------------

    /**
     * Relación con CuentasPorCobrar a través de Ventas.
     * Útil para reportes, pero para cálculos de saldo se recomienda usar queries directos.
     */
    public function cuentasPorCobrar(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CuentasPorCobrar::class, Venta::class);
    }

    // ... (Rest of the file)

    // ------------------------------------------------------------------
    // Métodos de Crédito
    // ------------------------------------------------------------------

    /**
     * Calcular el saldo total pendiente (Cuentas por Cobrar + Préstamos)
     */
    public function calcularSaldoPendiente(): float
    {
        // 1. Cuentas por Cobrar (Ventas)
        $deudaVentas = CuentasPorCobrar::whereHas('venta', function ($q) {
            $q->where('cliente_id', $this->id);
        })
            ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
            ->sum('monto_pendiente');

        // 2. Préstamos pendientes
        $deudaPrestamos = 0;
        if (class_exists(Prestamo::class)) {
            $deudaPrestamos = Prestamo::where('cliente_id', $this->id)
                ->where('estado', '!=', 'cancelado')
                ->sum('monto_pendiente');
        }

        // 3. Pedidos Online con crédito aún no procesados
        $deudaPedidos = PedidoOnline::where('cliente_id', $this->id)
            ->where('metodo_pago', 'credito')
            ->where('estado', 'pendiente')
            ->sum('total');

        return round((float) $deudaVentas + (float) $deudaPrestamos + (float) $deudaPedidos, 2);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->calcularSaldoPendiente();
    }

    public function getCreditoDisponibleAttribute(): float
    {
        if (!$this->credito_activo) {
            return 0;
        }

        $disponible = $this->limite_credito - $this->saldo_pendiente;
        return max(0, $disponible);
    }

    // ------------------------------------------------------------------
    // Relaciones propias
    // ------------------------------------------------------------------

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class)->withTrashed();
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class)->withTrashed();
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class)->withTrashed();
    }

    public function rentas(): HasMany
    {
        return $this->hasMany(Renta::class)->withTrashed();
    }

    public function polizas(): HasMany
    {
        return $this->hasMany(\App\Models\PolizaServicio::class, 'cliente_id');
    }

    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class)->withTrashed();
    }

    /**
     * Lista de precios asignada al cliente
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    // ------------------------------------------------------------------
    // Relaciones a catálogos SAT (por clave)
    // ------------------------------------------------------------------

    public function regimen(): BelongsTo
    {
        // clave local 'regimen_fiscal' -> clave primaria 'clave'
        return $this->belongsTo(SatRegimenFiscal::class, 'regimen_fiscal', 'clave');
    }

    public function uso(): BelongsTo
    {
        // clave local 'uso_cfdi' -> 'clave'
        return $this->belongsTo(SatUsoCfdi::class, 'uso_cfdi', 'clave');
    }

    public function estadoSat(): BelongsTo
    {
        // requiere un modelo App\Models\SatEstado con PK 'clave'
        return $this->belongsTo(SatEstado::class, 'estado', 'clave');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------
    public function scopeActivos($query)
    {
        return $query->where(function ($q) {
            $q->where('activo', true)->orWhereNull('activo');
        });
    }

    /**
     * Alias for scopeActivos to maintain consistency with other models
     */
    public function scopeActive($query)
    {
        return $this->scopeActivos($query);
    }

    public function scopeInactivos($query)
    {
        return $query->where('activo', false);
    }

    public function scopeBuscar($query, ?string $q)
    {
        $q = trim((string) $q);
        if ($q === '')
            return $query;

        return $query->where(function ($w) use ($q) {
            $w->where('nombre_razon_social', 'like', "%{$q}%")
                ->orWhere('rfc', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        });
    }

    // ------------------------------------------------------------------
    // Mutadores / Normalizadores
    // ------------------------------------------------------------------

    public function setNombreRazonSocialAttribute($value): void
    {
        // Normalizar Nombre: Recortar espacios y manejar mayúsculas si es necesario
        // (En este caso solo trim, el sistema permite minúsculas pero el SAT prefiere mayúsculas)
        $this->attributes['nombre_razon_social'] = trim((string) $value);
    }

    public function setRfcAttribute($value): void
    {
        // Normalizar RFC: mayúsculas, sin espacios/guiones
        $normalized = mb_strtoupper(trim((string) $value), 'UTF-8');
        $normalized = str_replace([' ', '-', '_'], '', $normalized);
        $this->attributes['rfc'] = $normalized;
    }

    public function setCurpAttribute($value): void
    {
        $this->attributes['curp'] = $value ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
    }

    public function setEmailAttribute($value): void
    {
        // Normalizar email y convertir vacío a NULL para consistencia con validación unique
        $trimmed = mb_strtolower(trim((string) $value), 'UTF-8');
        $this->attributes['email'] = $trimmed !== '' ? $trimmed : null;
    }

    public function setCodigoPostalAttribute($value): void
    {
        // Deja solo dígitos y rellena a 5
        $digits = preg_replace('/\D+/', '', (string) $value);
        $this->attributes['codigo_postal'] = str_pad(substr($digits, 0, 5), 5, '0', STR_PAD_LEFT);
    }

    public function setEstadoAttribute($value): void
    {
        // Asegura clave de 3 letras en mayúsculas (AGU, SON, etc.)
        $this->attributes['estado'] = $value ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
    }

    public function setPaisAttribute($value): void
    {
        // Normalizar código de país a mayúsculas, permitir vacío
        $this->attributes['pais'] = $value ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
    }

    public function setDomicilioFiscalCpAttribute($value): void
    {
        // Código postal del domicilio fiscal - debe ser válido según SAT
        $digits = preg_replace('/\D+/', '', (string) $value);
        $this->attributes['domicilio_fiscal_cp'] = str_pad(substr($digits, 0, 5), 5, '0', STR_PAD_LEFT);
    }

    public function setResidenciaFiscalAttribute($value): void
    {
        // Código de país según catálogo c_Pais del SAT (solo para extranjeros)
        $this->attributes['residencia_fiscal'] = $value ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
    }

    public function setNumRegIdTribAttribute($value): void
    {
        // Número de registro fiscal extranjero (máximo 40 caracteres)
        $this->attributes['num_reg_id_trib'] = $value ? mb_strtoupper(trim((string) $value), 'UTF-8') : null;
    }

    // ------------------------------------------------------------------
    // Accessors (incluye descripciones SAT)
    // ------------------------------------------------------------------

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

        return trim(implode(', ', $partes));
    }

    public function getEstadoNombreAttribute(): ?string
    {
        // Devuelve "Sonora" si está cargada la relación; si no, null
        return optional($this->estadoSat)->nombre;
    }

    public function getRegimenDescripcionAttribute(): ?string
    {
        return optional($this->regimen)->descripcion;
    }

    public function getUsoCfdiDescripcionAttribute(): ?string
    {
        return optional($this->uso)->descripcion;
    }

    /**
     * Devuelve el nombre en MAYÚSCULAS y SIN el régimen societario (S.A. de C.V., etc.)
     * requerido obligatoriamente para CFDI 4.0.
     */
    public function getNombreFiscalAttribute(): string
    {
        $nombre = mb_strtoupper(trim((string) $this->nombre_razon_social), 'UTF-8');

        // Regímenes comunes a eliminar (ordenados por longitud para evitar solapamientos incorrectos)
        $regimenes = [
            ' S. DE R.L. DE C.V.',
            ' S DE RL DE CV',
            ' S. DE R.L.',
            ' S DE RL',
            ' S.A.B. DE C.V.',
            ' SAB DE CV',
            ' S.A. DE C.V.',
            ' SA DE CV',
            ' S.A.',
            ' SA',
            ' S.O.F.O.M. E.N.R.',
            ' SOFOM ENR',
            ' S.A.P.I. DE C.V.',
            ' SAPI DE CV',
            ' S.C.P.',
            ' S.C.',
            ' SC',
            ' A.C.',
            ' AC',
            ' S.N.C.',
            ', S.A. DE C.V.',
            ', SA DE CV',
            ', S.A.',
            ', SA'
        ];

        foreach ($regimenes as $reg) {
            // Eliminar si está al final de la cadena
            if (str_ends_with($nombre, $reg)) {
                $nombre = substr($nombre, 0, -strlen($reg));
                break; // Solo eliminamos un régimen (el que coincida primero)
            }
        }

        return trim($nombre);
    }

    /**
     * Verificar si el cliente es extranjero (no mexicano)
     */
    public function getEsExtranjeroAttribute(): bool
    {
        return $this->pais !== 'MX' || $this->rfc === 'XEXX010101000';
    }

    /**
     * Validar que el cliente tenga todos los datos requeridos para CFDI 4.0
     */
    public function validarParaCfdi(): array
    {
        $errores = [];

        // RFC obligatorio y válido
        if (empty($this->rfc)) {
            $errores[] = 'RFC es obligatorio';
        } elseif (!$this->validarRfc($this->rfc)) {
            $errores[] = 'RFC no tiene formato válido';
        }

        // Nombre/Razón social obligatorio
        if (empty($this->nombre_razon_social)) {
            $errores[] = 'Nombre o razón social es obligatorio';
        }

        // Régimen fiscal obligatorio
        if (empty($this->regimen_fiscal)) {
            $errores[] = 'Régimen fiscal es obligatorio';
        }

        // Uso CFDI obligatorio
        if (empty($this->uso_cfdi) && empty($this->cfdi_default_use)) {
            $errores[] = 'Uso CFDI es obligatorio';
        }

        // Código postal del domicilio fiscal obligatorio
        if (empty($this->domicilio_fiscal_cp)) {
            $errores[] = 'Código postal del domicilio fiscal es obligatorio';
        }

        // Para extranjeros: residencia fiscal y num_reg_id_trib obligatorios
        if ($this->es_extranjero) {
            if (empty($this->residencia_fiscal)) {
                $errores[] = 'Residencia fiscal es obligatoria para clientes extranjeros';
            }
            if (empty($this->num_reg_id_trib)) {
                $errores[] = 'Número de registro fiscal extranjero es obligatorio';
            }
        }

        return $errores;
    }

    /**
     * Validar formato de RFC básico
     */
    private function validarRfc(string $rfc): bool
    {
        $rfc = strtoupper($rfc);

        // RFC genérico extranjero
        if ($rfc === 'XEXX010101000') {
            return true;
        }

        // Persona física: 13 caracteres
        if (preg_match('/^[A-ZÑ&]{4}\d{6}[A-Z\d]{3}$/', $rfc)) {
            return true;
        }

        // Persona moral: 12 caracteres
        if (preg_match('/^[A-ZÑ&]{3}\d{6}[A-Z\d]{3}$/', $rfc)) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el cliente ha dado consentimiento para recibir mensajes de WhatsApp
     * Según los nuevos términos de Meta Platform (Sección 3.a.v)
     */
    public function hasWhatsAppConsent(): bool
    {
        return $this->whatsapp_optin && !is_null($this->whatsapp_consent_date);
    }

    /**
     * Registrar consentimiento de WhatsApp
     */
    public function grantWhatsAppConsent(string $method = 'web', string $source = 'system'): void
    {
        $this->whatsapp_optin = true;
        $this->whatsapp_consent_date = now();
        $this->whatsapp_consent_method = $method;
        $this->whatsapp_consent_source = $source;
        $this->save();
    }

    /**
     * Revocar consentimiento de WhatsApp
     */
    public function revokeWhatsAppConsent(): void
    {
        $this->whatsapp_optin = false;
        $this->whatsapp_consent_date = null;
        $this->whatsapp_consent_method = null;
        $this->whatsapp_consent_source = null;
        $this->save();
    }

    // Relación con Tickets de soporte
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Relación con Citas
    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    /**
     * Bóveda de credenciales seguras
     */
    public function credenciales()
    {
        return $this->morphMany(Credencial::class, 'credentialable');
    }

    /**
     * Documentos del expediente de crédito
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(ClienteDocumento::class);
    }
}
