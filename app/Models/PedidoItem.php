<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class PedidoItem extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'pedible_id',
        'pedible_type',
        'nombre',
        'tipo_item',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'descuento_monto',
        'price_list_id',
    ];

    public function pedible()
    {
        return $this->morphTo();
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function priceList()
    {
        return $this->belongsTo(\App\Models\PriceList::class);
    }
}
