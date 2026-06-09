<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CfdiConcepto extends Model
{
    use HasFactory;

    protected $table = 'cfdi_conceptos';

    protected $fillable = [
        'cfdi_id',
        'clave_prod_serv',
        'no_identificacion',
        'cantidad',
        'clave_unidad',
        'unidad',
        'descripcion',
        'valor_unitario',
        'importe',
        'descuento',
        'impuestos',
        'numero_pedimento',
        'cuenta_predial',
        'complemento',
        'producto_id',
        'servicio_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'importe' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuestos' => 'array',
        'complemento' => 'array',
    ];

    // ------------------------------------------------------------------
    // Relaciones
    // ------------------------------------------------------------------

    public function cfdi(): BelongsTo
    {
        return $this->belongsTo(Cfdi::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getImporteNetoAttribute(): float
    {
        return $this->importe - $this->descuento;
    }

    public function getTieneImpuestosAttribute(): bool
    {
        return !empty($this->impuestos) &&
               (!empty($this->impuestos['traslados']) || !empty($this->impuestos['retenciones']));
    }

    public function getImpuestosTrasladadosAttribute(): array
    {
        return $this->impuestos['traslados'] ?? [];
    }

    public function getImpuestosRetenidosAttribute(): array
    {
        return $this->impuestos['retenciones'] ?? [];
    }

    // ------------------------------------------------------------------
    // Métodos de negocio
    // ------------------------------------------------------------------

    /**
     * Calcular importe automáticamente
     */
    public function calcularImporte(): void
    {
        $this->importe = $this->cantidad * $this->valor_unitario;
    }

    /**
     * Agregar impuesto trasladado
     */
    public function agregarImpuestoTrasladado(string $tipo, float $tasa, float $importe): void
    {
        $impuestos = $this->impuestos ?? ['traslados' => [], 'retenciones' => []];

        $impuestos['traslados'][] = [
            'tipo' => $tipo, // 'IVA', 'IEPS', 'ISH'
            'tasa' => $tasa,
            'importe' => $importe,
        ];

        $this->impuestos = $impuestos;
    }

    /**
     * Agregar impuesto retenido
     */
    public function agregarImpuestoRetenido(string $tipo, float $tasa, float $importe): void
    {
        $impuestos = $this->impuestos ?? ['traslados' => [], 'retenciones' => []];

        $impuestos['retenciones'][] = [
            'tipo' => $tipo, // 'IVA', 'ISR'
            'tasa' => $tasa,
            'importe' => $importe,
        ];

        $this->impuestos = $impuestos;
    }

    /**
     * Validar que el concepto tenga todos los datos requeridos
     */
    public function validar(): array
    {
        $errores = [];

        if (empty($this->clave_prod_serv)) {
            $errores[] = 'Clave de producto/servicio es obligatoria';
        }

        if (empty($this->descripcion)) {
            $errores[] = 'Descripción es obligatoria';
        }

        if ($this->cantidad <= 0) {
            $errores[] = 'Cantidad debe ser mayor a cero';
        }

        if (empty($this->clave_unidad)) {
            $errores[] = 'Clave de unidad es obligatoria';
        }

        if ($this->valor_unitario < 0) {
            $errores[] = 'Valor unitario no puede ser negativo';
        }

        if ($this->descuento < 0) {
            $errores[] = 'Descuento no puede ser negativo';
        }

        if ($this->descuento > $this->importe) {
            $errores[] = 'Descuento no puede ser mayor al importe';
        }

        return $errores;
    }

    // ------------------------------------------------------------------
    // Boot method
    // ------------------------------------------------------------------

    protected static function booted()
    {
        static::saving(function (CfdiConcepto $concepto) {
            // Calcular importe automáticamente si no está establecido
            if ($concepto->isDirty(['cantidad', 'valor_unitario']) && !isset($concepto->importe)) {
                $concepto->calcularImporte();
            }
        });
    }
}
