<?php

namespace App\Models;

use App\Enums\EstadoCompra;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

/**
 * @property int $id
 * @property int|null $almacen_id
 */
class Compra extends Model
{
    use HasFactory, Blameable, BelongsToEmpresa; // SoftDeletes temporarily disabled

    protected $table = 'compras';

    protected $fillable = [
        'empresa_id',
        'created_by',
        'tipo',
        'categoria_gasto_id',
        'proveedor_id',
        'almacen_id',
        'orden_compra_id',
        'numero_compra',
        'fecha_compra',
        'moneda',
        'tipo_cambio',
        'subtotal',
        'descuento_general',
        'descuento_items',
        'iva',
        'iva',
        'retencion_iva',
        'retencion_isr',
        'isr',
        'aplicar_retencion_iva',
        'aplicar_retencion_isr',
        'total',
        'notas',
        'estado',
        'inventario_procesado',
        'metodo_pago',
        'cuenta_bancaria_id',
        // Campos CFDI
        'cfdi_uuid',
        'cfdi_folio',
        'cfdi_serie',
        'cfdi_tipo_comprobante',
        'cfdi_forma_pago',
        'cfdi_metodo_pago',
        'cfdi_uso',
        'cfdi_fecha',
        'cfdi_emisor_rfc',
        'cfdi_emisor_nombre',
        'cfdi_total',
        'origen_importacion', // 'manual', 'bulk_import', 'sat_descarga'
        'proyecto_id',
    ];

    protected $casts = [
        'estado' => 'string',
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'descuento_items' => 'decimal:2',
        'iva' => 'decimal:2',
        'retencion_iva' => 'decimal:2',
        'retencion_isr' => 'decimal:2',
        'isr' => 'decimal:2',
        'aplicar_retencion_iva' => 'boolean',
        'aplicar_retencion_isr' => 'boolean',
        'total' => 'decimal:2',
        'fecha_compra' => 'datetime',
        'cfdi_fecha' => 'datetime',
    ];

    /** Relación con proveedor */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /** Items de la compra (relación directa con compra_items) */
    public function compraItems()
    {
        return $this->hasMany(CompraItem::class, 'compra_id');
    }

    /** Productos de la compra (relación polimórfica - puede no funcionar correctamente) */
    public function productos(): MorphToMany
    {
        return $this->morphedByMany(Producto::class, 'comprable', 'compra_items')
            ->withPivot('cantidad', 'precio', 'descuento', 'subtotal', 'descuento_monto');
    }

    /** Relación con almacén */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /** Relación con orden de compra */
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    /** Relación con cuentas por pagar */
    public function cuentasPorPagar(): HasOne
    {
        return $this->hasOne(CuentasPorPagar::class);
    }

    /** Relación con movimientos de inventario */
    public function movimientos(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(InventarioMovimiento::class, 'referencia');
    }

    /** Relación con categoría de gasto */
    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class);
    }

    /** Relación con cuenta bancaria */
    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    /** Relación con proyecto (para gastos asociados a proyectos) */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** Verificar si es un gasto operativo */
    public function esGasto(): bool
    {
        return $this->tipo === 'gasto';
    }

    /** Verificar si es una compra de inventario */
    public function esInventario(): bool
    {
        return $this->tipo === 'inventario';
    }

    // Relaciones de "culpables"
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    protected static function booted(): void
    {
        static::creating(function (Compra $compra) {
            if (empty($compra->numero_compra)) {
                $compra->numero_compra = static::generarNumero($compra->orden_compra_id, $compra->tipo ?? 'inventario');
            }
            // Establecer estado por defecto si no viene definido
            if (empty($compra->estado)) {
                $compra->estado = EstadoCompra::Procesada;
            }
        });

        // Al eliminar una compra, elimina también la cuenta por pagar asociada para evitar huérfanos
        static::deleting(function (Compra $compra) {
            $cuenta = $compra->cuentasPorPagar;

            if (!$cuenta) {
                return;
            }

            // Verify if Compra uses SoftDeletes
            if (method_exists($compra, 'isForceDeleting') && !$compra->isForceDeleting()) {
                $cuenta->delete();
            } else {
                $cuenta->forceDelete();
            }
        });
    }

    public static function generarNumero($ordenCompraId = null, $tipo = 'inventario'): string
    {
        try {
            return app(\App\Services\Folio\FolioService::class)->getNextFolio('compra');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error generando folio de compra: " . $e->getMessage());
            return 'COM-' . date('Ymd-His');
        }
    }
}
