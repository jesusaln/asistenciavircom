<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaItemSerie extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'venta_item_series';

    protected $fillable = [
        'empresa_id',
        'venta_item_id',
        'producto_serie_id',
        'numero_serie',
    ];

    public function ventaItem(): BelongsTo
    {
        return $this->belongsTo(VentaItem::class, 'venta_item_id');
    }

    public function productoSerie(): BelongsTo
    {
        return $this->belongsTo(ProductoSerie::class, 'producto_serie_id');
    }

    public function almacen(): HasOneThrough
    {
        return $this->hasOneThrough(
            Almacen::class,
            ProductoSerie::class,
            'id', // Foreign key on ProductoSerie table
            'id', // Foreign key on Almacen table
            'producto_serie_id', // Local key on VentaItemSerie table
            'almacen_id' // Local key on ProductoSerie table
        );
    }
}
