<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Enums\EstadoVenta; // Ajusta según tu enum

class Venta extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'ventas';

    protected static function booted(): void
    {
        static::creating(function (Venta $venta) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user) {
                $venta->created_by = $user->id;
                $venta->updated_by = $user->id;
            }

            if (empty($venta->almacen_id)) {
                if ($user && !empty($user->almacen_venta_id)) {
                    $venta->almacen_id = $user->almacen_venta_id;
                }
            }

            if (empty($venta->almacen_id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'almacen_id' => 'El almacén es obligatorio para crear la venta.',
                ]);
            }
        });

        static::updating(function (Venta $venta) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user) {
                $venta->updated_by = $user->id;
            }
        });
    }

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cotizacion_id',
        'pedido_id',
        'numero_venta',
        'fecha',
        'estado',
        'subtotal',
        'descuento_general',
        'iva',
        'isr',
        'retencion_iva',
        'retencion_isr',
        'total',
        'notas',
        'pagado',
        'metodo_pago',
        'forma_pago_sat',      // Clave SAT c_FormaPago (01, 03, 04, etc.)
        'metodo_pago_sat',     // Clave SAT c_MetodoPago (PUE, PPD)
        'aplicar_retencion_iva',
        'aplicar_retencion_isr',
        'cuenta_bancaria_id',
        'fecha_pago',
        'notas_pago',
        'pagado_por',
        'vendedor_type',
        'vendedor_id',
        'almacen_id',
        'created_by',
        'updated_by',
        'cita_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'string', // Temporarily remove enum cast to debug
            'subtotal' => 'decimal:2',
            'descuento_general' => 'decimal:2',
            'iva' => 'decimal:2',
            'retencion_iva' => 'decimal:2',
            'retencion_isr' => 'decimal:2',
            'isr' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha' => 'datetime',
            'fecha_pago' => 'datetime',
            'pagado' => 'boolean',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function pagadoPor()
    {
        return $this->belongsTo(User::class, 'pagado_por');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relaciï¿½n polimï¿½rfica con vendedor (User o Tecnico)
    public function vendedor()
    {
        return $this->morphTo();
    }

    // Relaciï¿½n polimï¿½rfica para productos
    // ✅ HIGH PRIORITY FIX #8: Removido scope ->active() para preservar datos históricos
    // Si un producto se desactiva después de la venta, aún debe aparecer en la relación
    public function productos()
    {
        return $this->morphToMany(
            Producto::class,
            'ventable',
            'venta_items',
            'venta_id',
            'ventable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'costo_unitario')
            ->wherePivot('ventable_type', Producto::class);
    }

    // Relaciï¿½n polimï¿½rfica para servicios
    // ✅ HIGH PRIORITY FIX #8: Removido scope ->active() para preservar datos históricos
    // Si un servicio se desactiva después de la venta, aún debe aparecer en la relación
    public function servicios()
    {
        return $this->morphToMany(
            Servicio::class,
            'ventable',
            'venta_items',
            'venta_id',
            'ventable_id'
        )->withPivot('cantidad', 'precio', 'descuento', 'costo_unitario')
            ->wherePivot('ventable_type', Servicio::class);
    }

    // Todos los ï¿½tems (productos + servicios)
    public function items()
    {
        return $this->hasMany(VentaItem::class, 'venta_id');
    }

    // Relación con series de productos vendidos
    public function series()
    {
        return $this->hasManyThrough(
            VentaItemSerie::class,
            VentaItem::class,
            'venta_id', // Foreign key on VentaItem table
            'venta_item_id', // Foreign key on VentaItemSerie table
            'id', // Local key on Venta table
            'id' // Local key on VentaItem table
        );
    }

    public function cuentaPorCobrar()
    {
        return $this->morphOne(CuentasPorCobrar::class, 'cobrable');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    /**
     * Relación con Facturas (CFDIs)
     */
    public function cfdis()
    {
        return $this->hasMany(Cfdi::class);
    }

    public function getCfdiActualAttribute()
    {
        return $this->cfdis()
            ->whereNotNull('uuid')
            ->where('estatus', '!=', 'cancelado')
            ->latest()
            ->first();
    }

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    // Relación con EntregaDinero (dinero entregado por esta venta)
    public function entregaDinero()
    {
        return $this->hasOne(EntregaDinero::class, 'id_origen')
            ->where('tipo_origen', 'venta');
    }

    // Calcular ganancia total de la venta
    public function getGananciaTotalAttribute()
    {
        $ganancia = 0;

        // Obtener el vendedor (User o Tecnico)
        $vendedor = $this->vendedor;

        // Ganancia de productos
        foreach ($this->productos as $producto) {
            $pivot = $producto->pivot;
            // ✅ MEDIUM PRIORITY FIX #9: Descuento es porcentaje, no monto absoluto
            $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
            $costo = $pivot->costo_unitario ?? $producto->precio_compra;
            $gananciaBase = ($precioVenta - $costo) * $pivot->cantidad;

            // ✅ FIX #9: La comisión se RESTA de la ganancia, no se suma
            // La comisión es un costo que reduce la ganancia neta
            $comisionProducto = $gananciaBase * ($producto->comision_vendedor / 100);
            $ganancia += $gananciaBase - $comisionProducto;

            // Si hay vendedor tï¿½cnico, aplicar mï¿½rgenes adicionales del tï¿½cnico
            if ($vendedor && $vendedor instanceof Tecnico) {
                $margenTecnico = $vendedor->margen_venta_productos / 100;
                $ganancia += $gananciaBase * $margenTecnico;
            }
        }

        // Ganancia de servicios
        foreach ($this->servicios as $servicio) {
            $pivot = $servicio->pivot;
            // ✅ MEDIUM PRIORITY FIX #9: Descuento es porcentaje, no monto absoluto
            $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
            $gananciaBase = $precioVenta * ($servicio->margen_ganancia / 100) * $pivot->cantidad;

            // ✅ FIX #9: La comisión se RESTA de la ganancia, no se suma
            $comisionServicio = $servicio->comision_vendedor * $pivot->cantidad;
            $ganancia += $gananciaBase - $comisionServicio;

            // Si hay vendedor tï¿½cnico, aplicar mï¿½rgenes adicionales del tï¿½cnico
            if ($vendedor && $vendedor instanceof Tecnico) {
                $margenTecnico = $vendedor->margen_venta_servicios / 100;
                $ganancia += $gananciaBase * $margenTecnico;

                // Si es servicio de instalaciï¿½n, agregar comisiï¿½n adicional del tï¿½cnico
                if ($servicio->es_instalacion) {
                    $ganancia += $vendedor->comision_instalacion * $pivot->cantidad;
                }
            }
        }

        return $ganancia;
    }

    // Calcular costo total de la venta
    public function calcularCostoTotal()
    {
        $costoTotal = 0;

        // Identificar componentes de Kits presentes en la venta para evitar duplicar costos
        $kitComponentIds = [];
        foreach ($this->items as $item) {
            if ($item->ventable_type === \App\Models\Producto::class && $item->ventable && $item->ventable->esKit()) {
                foreach ($item->ventable->kitItems as $kitItem) {
                    $kitComponentIds[] = $kitItem->item_id;
                }
            }
        }

        // Costo de productos usando costo histÃ³rico correcto
        // Iterar sobre items para obtener productos correctamente relacionados
        foreach ($this->items as $item) {
            if ($item->ventable_type === \App\Models\Producto::class && $item->ventable) {
                $producto = $item->ventable;

                // FIX: Si es un componente de Kit con precio 0, es una línea de serie auxiliar.
                // Ignoramos su costo porque ya está sumado en el Kit.
                if ($item->precio == 0 && in_array($producto->id, $kitComponentIds)) {
                    continue;
                }

                // Priorizar costo_unitario del item (ya calculado histÃ³ricamente)
                // Si no existe, calcular costo histÃ³rico basado en movimientos recientes
                $esKit = $producto->esKit();

                // Priorizar costo_unitario del item (ya calculado históricamente y guardado en la transacción)
                // Esto garantiza que la utilidad histórica sea correcta aunque los costos actuales cambien.
                if ($item->costo_unitario && $item->costo_unitario > 0) {
                    $costo = $item->costo_unitario;
                } else {
                    // Fallback si no se guardó el costo en la transacción (compatibilidad legacy)
                    $esKit = $producto->esKit();
                    if ($esKit) {
                        $costo = $producto->calcularCostoKit(1, $this->almacen_id);
                    } else {
                        $costo = $producto->precio_compra ?? 0;
                    }
                }

                $costoTotal += $costo * $item->cantidad;
            }
        }

        // Costo de servicios (considerando margen de ganancia)
        foreach ($this->servicios as $servicio) {
            $pivot = $servicio->pivot;
            // ✅ FIX: Descuento es porcentaje, no monto absoluto
            $precioVenta = $pivot->precio * (1 - ($pivot->descuento ?? 0) / 100);
            // El costo del servicio es el precio de venta menos el margen de ganancia
            $costoServicio = $precioVenta * (1 - $servicio->margen_ganancia / 100);
            $costoTotal += $costoServicio * $pivot->cantidad;
        }

        return $costoTotal;
    }

    /**
     * Recalcula y actualiza los costos histÃ³ricos de todos los productos en esta venta
     */
    public function recalcularCostosHistoricos()
    {
        foreach ($this->items as $item) {
            if ($item->ventable_type === \App\Models\Producto::class && $item->ventable) {
                $producto = $item->ventable;

                // Calcular costo histÃ³rico correcto
                $nuevoCostoUnitario = $producto->calcularCostoPorLotes($item->cantidad, $this->almacen_id);

                // Actualizar el costo unitario en el item si es diferente
                if ($nuevoCostoUnitario != $item->costo_unitario) {
                    $item->update(['costo_unitario' => $nuevoCostoUnitario]);
                }
            }
        }

        return $this->calcularCostoTotal();
    }
}
