<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class Producto extends Model
{

    use HasFactory, SoftDeletes, Blameable, BelongsToEmpresa;

    protected $appends = ['precio_con_iva'];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'margen_ganancia' => 'decimal:2',
        'comision_vendedor' => 'decimal:2',
        'requiere_serie' => 'boolean',
        'tipo_producto' => 'string',
        'cva_last_sync' => 'datetime',
        'stock_cedis' => 'integer',
    ];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'codigo',
        'codigo_barras',
        'numero_serie',
        'categoria_id',
        'marca_id',
        'proveedor_id',
        'almacen_id',
        'stock',
        'reservado',
        'stock_minimo',
        'expires',
        'requiere_serie',
        'precio_compra',
        'precio_venta',
        'margen_ganancia',
        'comision_vendedor',
        'unidad_medida',
        'fecha_vencimiento',
        'tipo_producto',
        'sat_clave_prod_serv',
        'sat_clave_unidad',
        'sat_objeto_imp',
        'imagen',
        'estado',
        'origen',
        'cva_clave',
        'stock_cedis',
        'cva_last_sync',
    ];

    protected $attributes = [
        'descripcion' => 'Sin descripción disponible',
        'reservado' => 0,
        'stock' => 0,
        'stock_minimo' => 0,
        'expires' => false,
        'requiere_serie' => false,
        'margen_ganancia' => 0,
        'comision_vendedor' => 0,
    ];

    protected static function booted()
    {
        static::creating(function (Producto $producto) {
            if (empty($producto->codigo)) {
                try {
                    $producto->codigo = app(\App\Services\Folio\FolioService::class)->getNextFolio('producto');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for producto: ' . $e->getMessage());
                    // Fallback to timestamp if service fails
                    $producto->codigo = (string) time();
                }
            }
        });
    }

    /**
     * Generar el siguiente código para un producto sin incrementarlo (Vista previa).
     */
    public static function generateNextCodigo(): string
    {
        return app(\App\Services\Folio\FolioService::class)->previewNextFolio('producto');
    }

    /* =========================
     * Relaciones base
     * ========================= */

    /**
     * Precios por lista del producto
     */
    public function precios(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /** @return BelongsTo<Categoria, Producto> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return BelongsTo<Marca, Producto> */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** @return BelongsTo<Proveedor, Producto> */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /** @return BelongsTo<Almacen, Producto> */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /** @return MorphToMany<Compra, Producto> */
    public function compras(): MorphToMany
    {
        return $this->morphToMany(Compra::class, 'comprable', 'compra_items');
    }

    /** @return BelongsToMany<OrdenCompra> */
    public function ordenesCompra(): BelongsToMany
    {
        return $this->belongsToMany(OrdenCompra::class, 'orden_compra_producto')->withPivot('cantidad', 'precio');
    }

    /** @return HasMany<Inventario> */
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    /** @return HasMany<InventarioMovimiento> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(InventarioMovimiento::class);
    }

    /** @return HasMany<ProductoPrecioHistorial> */
    public function precioHistorial(): HasMany
    {
        return $this->hasMany(ProductoPrecioHistorial::class);
    }

    /** @return HasMany<Lote> */
    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    /**
     * Citas donde este producto fue utilizado
     */
    public function citasComoUtilizado(): BelongsToMany
    {
        return $this->belongsToMany(Cita::class, 'cita_productos_utilizados')
            ->withPivot('cantidad', 'precio_unitario', 'tipo_uso')
            ->withTimestamps();
    }

    /**
     * Citas donde este producto fue vendido
     */
    public function citasComoVendido(): BelongsToMany
    {
        return $this->belongsToMany(Cita::class, 'cita_productos_vendidos')
            ->withPivot('cantidad', 'precio_venta', 'venta_id')
            ->withTimestamps();
    }

    /** @return HasMany<ProductoSerie> */
    public function series(): HasMany
    {
        return $this->hasMany(ProductoSerie::class);
    }

    /**
     * Precios de este producto en diferentes listas
     * @return HasMany<ProductPrice>
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Obtener precio para una lista específica
     * Retorna null solo si NO existe registro, permite precio 0 configurado
     */
    public function getPrecioParaLista($priceListId): ?float
    {
        $productPrice = $this->productPrices()
            ->where('price_list_id', $priceListId)
            ->first();

        // Si no existe registro, retornar null (sin precio configurado)
        if (!$productPrice) {
            return null;
        }

        // Si existe registro, retornar el precio (puede ser 0 para promociones)
        return (float) $productPrice->precio;
    }

    /* =========================
     * Relaciones para Kits
     * ========================= */

    /** @return HasMany<KitItem> */
    public function kitItems(): HasMany
    {
        return $this->hasMany(KitItem::class, 'kit_id');
    }



    /**
     * Obtener los productos componentes de este kit
     */
    public function componentes()
    {
        // Componentes (solo productos) a través de los kit items polimórficos
        return $this->kitItems()
            ->where('item_type', 'producto')
            ->with('item');
    }

    /**
     * Obtener los kits que incluyen este producto (como componente)
     */
    public function kits()
    {
        return $this->hasMany(KitItem::class, 'item_id')
            ->where('item_type', 'producto');
    }

    /* =========================================================================
     * Relaciones polimórficas UNIFICADAS a *items (coinciden con tus controladores)
     * ========================================================================= */

    /** Ítems en ventas donde este producto fue usado. @return MorphMany<VentaItem> */
    public function ventaItems(): MorphMany
    {
        return $this->morphMany(VentaItem::class, 'ventable');
    }

    /** Ítems en pedidos donde este producto fue usado. @return MorphMany<PedidoItem> */
    public function pedidoItems(): MorphMany
    {
        return $this->morphMany(PedidoItem::class, 'pedible');
    }

    /** Ítems en cotizaciones donde este producto fue usado. @return MorphMany<CotizacionItem> */
    public function cotizacionItems(): MorphMany
    {
        return $this->morphMany(CotizacionItem::class, 'cotizable');
    }

    /** Ítems en citas donde este producto fue usado. @return MorphMany<CitaItem> */
    public function citaItems(): MorphMany
    {
        return $this->morphMany(CitaItem::class, 'citable');
    }

    /** @return MorphToMany<Venta> */
    public function ventas()
    {
        return $this->morphedByMany(Venta::class, 'ventable', 'venta_items')
            ->withPivot('cantidad', 'precio', 'descuento', 'costo_unitario');
    }

    /* =========================
     * Accessors
     * ========================= */

    public function getStockDisponibleAttribute()
    {
        // Stock disponible = stock total - cantidad reservada
        $stockTotal = (int) $this->stock;
        $reservado = (int) $this->reservado;

        return max(0, $stockTotal - $reservado);
    }

    public function getStockTotalAttribute()
    {
        // Stock total es la suma de cantidades en inventarios
        return $this->inventarios->sum('cantidad');
    }

    public function getGananciaAttribute()
    {
        return $this->precio_venta - $this->precio_compra;
    }

    public function getGananciaMargenAttribute()
    {
        return $this->ganancia * ($this->margen_ganancia / 100);
    }

    public function getPrecioConIvaAttribute()
    {
        return round(($this->precio_venta ?? 0) * 1.16, 2);
    }

    /* =========================
     * Métodos para Kits
     * ========================= */

    /**
     * Verificar si este producto es un kit
     */
    public function esKit(): bool
    {
        return $this->tipo_producto === 'kit';
    }

    /**
     * Obtener el costo total del kit basado en sus componentes
     *
     * @param int $cantidadKits Cantidad de kits a calcular
     * @param int $almacenId ID del almacén (OBLIGATORIO para consistencia)
     * @return float Costo total del kit
     * @throws \InvalidArgumentException Si no se proporciona almacenId
     */
    public function calcularCostoKit(int $cantidadKits = 1, ?int $almacenId = null): float
    {
        if (!$this->esKit()) {
            return $this->precio_compra ?: 0;
        }

        if (!$almacenId) {
            throw new \InvalidArgumentException(
                'almacenId es obligatorio para calcularCostoKit() para asegurar consistencia entre costos y bloqueos de inventario'
            );
        }

        $costoTotal = 0;
        $stockService = app(\App\Services\StockValidationService::class);

        // Asegurar que los kitItems tengan la relación 'item' cargada
        $kitItems = $this->kitItems()->with('item')->get();

        foreach ($kitItems as $item) {
            if (!$item->item) {
                // Saltar ítems eliminados o huérfanos
                continue;
            }

            // Manejar Servicios
            if ($item->item instanceof \App\Models\Servicio) {
                // El costo de un servicio dentro de un kit es su comision (si hay) o 0.
                // No usar el precio, porque eso infla el costo.
                $costoUnitario = $item->item->comision_vendedor ?? 0;
            } else {
                // Manejar Productos
                if ($item->precio_unitario) {
                    // Check if we should use stored price? No, user wants historical cost.
                    // But we previously commented this out.
                }

                $cantidadNecesaria = $item->cantidad * $cantidadKits;
                $costoUnitario = $stockService->calcularCostoHistorico($item->item, $cantidadNecesaria, $almacenId);
            }

            $costoTotal += $costoUnitario * $item->cantidad * $cantidadKits;
        }

        return $costoTotal;
    }

    /**
     * Verificar si hay stock suficiente para vender este kit en un almacén específico
     * NOTA: Este método es para consultas rápidas. Para operaciones críticas de venta,
     * usar StockValidationService::validateStockForSale() que considera concurrencia.
     */
    public function kitTieneStockSuficiente(int $cantidad = 1, ?int $almacenId = null): bool
    {
        if (!$almacenId) {
            throw new \InvalidArgumentException('almacenId es obligatorio para kitTieneStockSuficiente()');
        }

        if (!$this->esKit()) {
            // Para productos normales, usar el mismo mecanismo que StockValidationService
            $stockService = app(\App\Services\StockValidationService::class);
            return $stockService->getStockDisponible($this->id, $almacenId) >= $cantidad;
        }

        // Para kits, verificar cada componente usando el mismo mecanismo
        $stockService = app(\App\Services\StockValidationService::class);

        // Asegurar que los kitItems tengan la relación 'item' cargada
        $kitItems = $this->kitItems()->with('item')->get();

        foreach ($kitItems as $item) {
            if (!$item->esProducto() || !$item->item) {
                continue;
            }

            $componente = $item->item;
            $cantidadNecesaria = $item->cantidad * $cantidad;

            // Verificar si el componente requiere series
            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if ($requiereSeries) {
                // Para componentes serializados, no podemos garantizar stock sin series específicas
                return false;
            }

            // Usar el mismo método que StockValidationService para consistencia
            if ($stockService->getStockDisponible($componente->id, $almacenId) < $cantidadNecesaria) {
                return false;
            }
        }

        return true;
    }

    /**
     * Expandir kit en sus componentes para ventas
     */
    public function expandirKit(int $cantidad = 1, ?int $almacenId = null): array
    {
        if (!$this->esKit()) {
            return [
                [
                    'producto' => $this,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $this->precio_venta,
                    'costo_unitario' => $almacenId ?
                        $this->calcularCostoHistorico($cantidad, $almacenId) :
                        $this->calcularCostoHistorico(),
                ]
            ];
        }

        if (!$almacenId) {
            throw new \InvalidArgumentException('almacenId es obligatorio para expandirKit()');
        }

        $componentes = [];
        $stockService = app(\App\Services\StockValidationService::class);

        // Asegurar que los kitItems tengan la relación 'item' cargada
        $kitItems = $this->kitItems()->with('item')->get();

        foreach ($kitItems as $item) {
            if (!$item->esProducto() || !$item->item) {
                continue;
            }

            $costoUnitario = $item->precio_unitario;
            $cantidadNecesaria = $item->cantidad * $cantidad;

            if (!$costoUnitario) {
                $costoUnitario = $stockService->calcularCostoHistorico($item->item, $cantidadNecesaria, $almacenId);
            }

            $componentes[] = [
                'producto' => $item->item,
                'cantidad' => $cantidadNecesaria,
                'precio_unitario' => $item->precio_unitario ?? $item->item->precio_venta,
                'costo_unitario' => $costoUnitario,
            ];
        }

        return $componentes;
    }

    /* =========================
     * Scopes
     * ========================= */

    public function scopeActive($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeKits($query)
    {
        return $query->where('tipo_producto', 'kit');
    }

    public function scopeNoKits($query)
    {
        return $query->where('tipo_producto', '!=', 'kit');
    }

    /**
     * Calcula el costo histórico promedio basado en los últimos movimientos de entrada
     * @param int|null $cantidad Número de movimientos a considerar (opcional)
     * @param int|null $almacenId ID del almacén para filtrar movimientos (opcional)
     */
    public function calcularCostoHistorico($cantidad = null, $almacenId = null)
    {
        try {
            // Si no hay movimientos de entrada, usar el precio de compra actual
            $movimientosEntrada = $this->movimientos()
                ->where('tipo', 'entrada')
                ->where('cantidad', '>', 0);

            // Filtrar por almacén si se proporciona
            if ($almacenId) {
                $movimientosEntrada->where('almacen_id', $almacenId);
            }

            $movimientosEntrada->orderBy('created_at', 'desc');

            if ($cantidad) {
                $movimientosEntrada->limit($cantidad);
            }

            $movimientos = $movimientosEntrada->get();

            if ($movimientos->isEmpty()) {
                return $this->precio_compra ?: 0;
            }

            // Calcular costo promedio ponderado
            $totalCantidad = 0;
            $totalCosto = 0;

            foreach ($movimientos as $movimiento) {
                $costoUnitario = $movimiento->detalles['costo_unitario'] ?? $this->precio_compra ?: 0;
                $totalCosto += $costoUnitario * $movimiento->cantidad;
                $totalCantidad += $movimiento->cantidad;
            }

            return $totalCantidad > 0 ? $totalCosto / $totalCantidad : ($this->precio_compra ?: 0);
        } catch (\Exception $e) {
            // En caso de error, devolver el precio de compra actual
            return $this->precio_compra ?: 0;
        }
    }

    /**
     * Calcula el costo histórico usando el sistema de lotes (si aplica)
     */
    public function calcularCostoPorLotes($cantidadNecesaria = null, $almacenId = null)
    {
        try {
            if (!$this->expires) {
                return $this->calcularCostoHistorico($cantidadNecesaria);
            }

            // Para productos con lotes, calcular basado en los lotes disponibles
            $query = $this->lotes()
                ->where('cantidad_actual', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('fecha_caducidad')
                        ->orWhere('fecha_caducidad', '>', now());
                });

            if ($almacenId) {
                $query->where('almacen_id', $almacenId);
            }

            $lotes = $query->orderBy('fecha_caducidad', 'asc') // FIFO
                ->get();

            if ($lotes->isEmpty()) {
                return $this->precio_compra ?: 0;
            }

            $cantidadRestante = $cantidadNecesaria ?? $this->stock;
            $costoTotal = 0;

            foreach ($lotes as $lote) {
                if ($cantidadRestante <= 0)
                    break;

                $cantidadUsar = min($cantidadRestante, $lote->cantidad_actual);
                $costoUnitario = $lote->costo_unitario ?: $this->precio_compra ?: 0;
                $costoTotal += $costoUnitario * $cantidadUsar;
                $cantidadRestante -= $cantidadUsar;
            }

            $cantidadUsada = ($cantidadNecesaria ?? $this->stock) - $cantidadRestante;
            return $cantidadUsada > 0 ? $costoTotal / $cantidadUsada : ($this->precio_compra ?: 0);
        } catch (\Exception $e) {
            // En caso de error, devolver el precio de compra actual
            return $this->precio_compra ?: 0;
        }
    }

    /* =========================
     * Blameable Relationships
     * ========================= */

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /** =========================
     * Relaciones SAT
     * ========================= */

    public function satClaveProdServ(): BelongsTo
    {
        return $this->belongsTo(SatClaveProdServ::class, 'sat_clave_prod_serv', 'clave');
    }

    public function satClaveUnidad(): BelongsTo
    {
        return $this->belongsTo(SatClaveUnidad::class, 'sat_clave_unidad', 'clave');
    }

    public function satObjetoImp(): BelongsTo
    {
        return $this->belongsTo(SatObjetoImp::class, 'sat_objeto_imp', 'clave');
    }
}
