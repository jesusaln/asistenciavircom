<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoPrecioHistorial extends Model
{
    use BelongsToEmpresa;

    protected $table = 'producto_precio_historial';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'precio_compra_anterior',
        'precio_compra_nuevo',
        'precio_venta_anterior',
        'precio_venta_nuevo',
        'tipo_cambio',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'precio_compra_anterior' => 'decimal:2',
        'precio_compra_nuevo' => 'decimal:2',
        'precio_venta_anterior' => 'decimal:2',
        'precio_venta_nuevo' => 'decimal:2',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
