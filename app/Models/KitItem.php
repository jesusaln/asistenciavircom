<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KitItem extends Model
{
    use SoftDeletes;
    protected $table = 'kit_items';

    protected $fillable = [
        'kit_id',
        'item_type',
        'item_id',
        'cantidad',
        'precio_unitario',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
    ];

    /**
     * Normalizar item_type a nombres cortos al guardar
     */
    public function setItemTypeAttribute($value)
    {
        // Normalizar a nombres cortos
        if ($value === 'App\\Models\\Producto' || $value === 'producto') {
            $this->attributes['item_type'] = 'producto';
        } elseif ($value === 'App\\Models\\Servicio' || $value === 'servicio') {
            $this->attributes['item_type'] = 'servicio';
        } else {
            $this->attributes['item_type'] = $value;
        }
    }

    /**
     * Relación con el kit (producto padre)
     */
    public function kit(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'kit_id');
    }

    /**
     * Relación polimórfica con el item (Producto o Servicio)
     */
    public function item()
    {
        return $this->morphTo('item');
    }


    /**
     * Verificar si el item es un producto
     */
    public function esProducto(): bool
    {
        return $this->item_type === 'producto';
    }

    /**
     * Verificar si el item es un servicio
     */
    public function esServicio(): bool
    {
        return $this->item_type === 'servicio';
    }

    /**
     * Calcular el subtotal de este item en el kit
     */
    public function getSubtotalAttribute(): float
    {
        if ($this->precio_unitario) {
            return $this->precio_unitario * $this->cantidad;
        }

        // Si no hay precio unitario específico, usar el precio del item
        if ($this->item) {
            $precio = $this->esServicio() 
                ? $this->item->precio 
                : ($this->item->precio_venta ?? 0);
            return $precio * $this->cantidad;
        }

        return 0;
    }

    /**
     * Verificar si el item tiene stock suficiente (solo para productos)
     */
    public function tieneStockSuficiente(int $cantidadKits = 1): bool
    {
        // Los servicios no requieren stock
        if ($this->esServicio()) {
            return true;
        }

        // Para productos, verificar stock
        if ($this->esProducto() && $this->item) {
            $cantidadNecesaria = $this->cantidad * $cantidadKits;
            return $this->item->stock_disponible >= $cantidadNecesaria;
        }

        return false;
    }
}
