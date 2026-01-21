<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToEmpresa;

class Cfdi extends Model
{
    use HasFactory, BelongsToEmpresa, SoftDeletes;

    protected $table = 'cfdis';

    protected $fillable = [
        'cliente_id',
        'empresa_id',
        'venta_id',
        'factura_id',
        'cfdiable_id',
        'cfdiable_type',
        'cfdi_relacionado_id',
        'tipo_comprobante',
        'direccion',
        'rfc_emisor',
        'nombre_emisor',
        'regimen_fiscal_emisor',
        'rfc_receptor',
        'nombre_receptor',
        'serie',
        'folio',
        'uuid',
        'fecha_timbrado',
        'no_certificado_sat',
        'no_certificado_cfdi',
        'sello_sat',
        'sello_cfdi',
        'cadena_original',
        'estatus',
        'fecha_emision',
        'fecha_cancelacion',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'descuento',
        'total_impuestos_trasladados',
        'total_impuestos_retenidos',
        'total',
        'metodo_pago',
        'forma_pago',
        'condiciones_pago',
        'uso_cfdi',
        'complementos',
        'pac_rfc',
        'pac_nombre',
        'xml_url',
        'pdf_url',
        'observaciones',
        'datos_adicionales',
        'creado_por',
        'actualizado_por',
        'cancelado_por',
        'motivo_cancelacion',
        'folio_sustitucion',
        'acuse_cancelacion',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_timbrado' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'tipo_cambio' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total_impuestos_trasladados' => 'decimal:2',
        'total_impuestos_retenidos' => 'decimal:2',
        'total' => 'decimal:2',
        'complementos' => 'array',
        'datos_adicionales' => 'array',
    ];

    // Constantes para tipos de comprobante
    const TIPO_INGRESO = 'I';
    const TIPO_EGRESO = 'E';
    const TIPO_TRASLADO = 'T';
    const TIPO_NOMINA = 'N';
    const TIPO_PAGOS = 'P';

    // Constantes para estatus
    const ESTATUS_BORRADOR = 'borrador';
    const ESTATUS_TIMBRADO = 'timbrado';
    const ESTATUS_CANCELADO = 'cancelado';
    const ESTATUS_VIGENTE = 'vigente';
    const ESTATUS_CANCELADO_CON_ACUSE = 'cancelado_con_acuse';

    // Constantes para dirección (emitido = a clientes, recibido = de proveedores)
    const DIRECCION_EMITIDO = 'emitido';
    const DIRECCION_RECIBIDO = 'recibido';

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cfdiRelacionado(): BelongsTo
    {
        return $this->belongsTo(Cfdi::class, 'cfdi_relacionado_id');
    }

    public function cfdisRelacionados(): HasMany
    {
        return $this->hasMany(Cfdi::class, 'cfdi_relacionado_id');
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(CfdiConcepto::class);
    }

    /**
     * Relación con cuenta por pagar (para CFDIs recibidos)
     */
    public function cuentaPorPagar(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CuentasPorPagar::class);
    }

    /**
     * Relación con cuenta por cobrar (para CFDIs emitidos)
     */
    public function cuentaPorCobrar(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CuentasPorCobrar::class);
    }

    /**
     * Relación con compra (si fue importado)
     */
    public function compra(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Compra::class, 'cfdi_uuid', 'uuid');
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeEstatus($query, $estatus)
    {
        return $query->where('estatus', $estatus);
    }

    public function scopeTipoComprobante($query, $tipo)
    {
        return $query->where('tipo_comprobante', $tipo);
    }

    public function scopeTimbrados($query)
    {
        return $query->whereIn('estatus', [self::ESTATUS_TIMBRADO, self::ESTATUS_VIGENTE]);
    }

    public function scopeCancelados($query)
    {
        return $query->whereIn('estatus', [self::ESTATUS_CANCELADO, self::ESTATUS_CANCELADO_CON_ACUSE]);
    }

    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePorPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_emision', [$fechaInicio, $fechaFin]);
    }

    public function scopeDireccion($query, $direccion)
    {
        return $query->where('direccion', $direccion);
    }

    public function scopeEmitidos($query)
    {
        return $query->where('direccion', self::DIRECCION_EMITIDO);
    }

    public function scopeRecibidos($query)
    {
        return $query->where('direccion', self::DIRECCION_RECIBIDO);
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getEstaTimbradoAttribute(): bool
    {
        return !empty($this->uuid) && in_array($this->estatus, [self::ESTATUS_TIMBRADO, self::ESTATUS_VIGENTE]);
    }

    public function getEstaCanceladoAttribute(): bool
    {
        return in_array($this->estatus, [self::ESTATUS_CANCELADO, self::ESTATUS_CANCELADO_CON_ACUSE]);
    }

    public function getPuedeCancelarseAttribute(): bool
    {
        return $this->esta_timbrado && !$this->esta_cancelado;
    }

    public function getSerieFolioAttribute(): string
    {
        $serie = $this->serie ? $this->serie . '-' : '';
        return $serie . ($this->folio ?: $this->id);
    }

    public function getTipoComprobanteNombreAttribute(): string
    {
        $tipos = [
            self::TIPO_INGRESO => 'Ingreso',
            self::TIPO_EGRESO => 'Egreso',
            self::TIPO_TRASLADO => 'Traslado',
            self::TIPO_NOMINA => 'Nómina',
            self::TIPO_PAGOS => 'Pagos',
        ];

        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }

    public function getEstatusNombreAttribute(): string
    {
        $estatus = [
            self::ESTATUS_BORRADOR => 'Borrador',
            self::ESTATUS_TIMBRADO => 'Timbrado',
            self::ESTATUS_CANCELADO => 'Cancelado',
            self::ESTATUS_VIGENTE => 'Vigente',
            self::ESTATUS_CANCELADO_CON_ACUSE => 'Cancelado con Acuse',
        ];

        return $estatus[$this->estatus] ?? 'Desconocido';
    }

    // ------------------------------------------------------------------
    // Métodos de negocio
    // ------------------------------------------------------------------

    /**
     * Generar serie automática
     */
    public static function generarSerie($tipoComprobante = 'I', $anio = null): string
    {
        $anio = $anio ?: now()->format('Y');
        $prefijo = match ($tipoComprobante) {
            self::TIPO_INGRESO => 'I',
            self::TIPO_EGRESO => 'E',
            self::TIPO_TRASLADO => 'T',
            self::TIPO_NOMINA => 'N',
            self::TIPO_PAGOS => 'P',
            default => 'I'
        };

        return $prefijo . $anio;
    }

    /**
     * Generar folio automático
     */
    public static function generarFolio($serie): string
    {
        $ultimoCfdi = self::where('serie', $serie)
            ->whereYear('created_at', now()->year)
            ->orderBy('folio', 'desc')
            ->first();

        if ($ultimoCfdi && is_numeric($ultimoCfdi->folio)) {
            return str_pad((int) $ultimoCfdi->folio + 1, 6, '0', STR_PAD_LEFT);
        }

        return '000001';
    }

    /**
     * Marcar como timbrado
     */
    public function marcarComoTimbrado(array $datosTimbrado): void
    {
        $this->update([
            'uuid' => $datosTimbrado['uuid'],
            'fecha_timbrado' => $datosTimbrado['fecha_timbrado'] ?? now(),
            'no_certificado_sat' => $datosTimbrado['no_certificado_sat'],
            'no_certificado_cfdi' => $datosTimbrado['no_certificado_cfdi'],
            'sello_sat' => $datosTimbrado['sello_sat'],
            'sello_cfdi' => $datosTimbrado['sello_cfdi'],
            'cadena_original' => $datosTimbrado['cadena_original'],
            'estatus' => self::ESTATUS_TIMBRADO,
            'xml_url' => $datosTimbrado['xml_url'] ?? null,
            'pdf_url' => $datosTimbrado['pdf_url'] ?? null,
        ]);
    }

    /**
     * Cancelar CFDI
     */
    public function cancelar(string $motivoCancelacion = null, string $canceladoPor = null): void
    {
        $this->update([
            'estatus' => self::ESTATUS_CANCELADO,
            'fecha_cancelacion' => now(),
            'cancelado_por' => $canceladoPor,
            'datos_adicionales' => array_merge($this->datos_adicionales ?? [], [
                'motivo_cancelacion' => $motivoCancelacion
            ])
        ]);
    }

    /**
     * Crear CFDI de sustitución
     */
    public function crearSustitucion(array $datos): Cfdi
    {
        return self::create(array_merge($datos, [
            'cfdi_relacionado_id' => $this->id,
            'serie' => $this->serie,
            'tipo_comprobante' => $this->tipo_comprobante,
        ]));
    }

    /**
     * Validar que el CFDI puede timbrarse
     */
    public function validarParaTimbrado(): array
    {
        $errores = [];

        if (empty($this->cliente)) {
            $errores[] = 'Cliente es obligatorio';
        } elseif ($erroresCliente = $this->cliente->validarParaCfdi()) {
            $errores = array_merge($errores, $erroresCliente);
        }

        if (empty($this->empresa)) {
            $errores[] = 'Empresa emisora es obligatoria';
        }

        if ($this->conceptos->isEmpty()) {
            $errores[] = 'Debe tener al menos un concepto';
        }

        if (empty($this->uso_cfdi)) {
            $errores[] = 'Uso CFDI es obligatorio';
        }

        return $errores;
    }

    // ------------------------------------------------------------------
    // Boot method
    // ------------------------------------------------------------------

    protected static function booted()
    {
        static::creating(function (Cfdi $cfdi) {
            // Generar serie y folio automáticos si no se especifican
            if (empty($cfdi->serie)) {
                $cfdi->serie = self::generarSerie($cfdi->tipo_comprobante);
            }

            if (empty($cfdi->folio)) {
                $cfdi->folio = self::generarFolio($cfdi->serie);
            }

            // Setear fecha de emisión si no viene
            if (empty($cfdi->fecha_emision)) {
                $cfdi->fecha_emision = now();
            }
        });
    }
}
