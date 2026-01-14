<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Venta;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaItem extends Model
{
    use SoftDeletes, BelongsToEmpresa;

    protected $table = 'venta_items';
    protected $fillable = [
        'empresa_id',
        'venta_id',
        'ventable_id',
        'ventable_type',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'descuento_monto',
        'costo_unitario',
        'price_list_id',  // Lista de precios usada
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function ventable()
    {
        return $this->morphTo();
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(VentaItemSerie::class, 'venta_item_id');
    }

    /**
     * Get the serial numbers for this item
     */
    public function getSerialNumbersAttribute()
    {
        return $this->series->pluck('numero_serie')->toArray();
    }
}
