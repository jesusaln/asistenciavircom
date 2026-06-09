<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductoSerie extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'producto_series';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'compra_id',
        'cita_id',
        'venta_id',
        'almacen_id',
        'numero_serie',
        'estado',
    ];

    /** @return BelongsTo<Producto, ProductoSerie> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Almacen, ProductoSerie> */
    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    /** @return BelongsTo<\App\Models\Cita, ProductoSerie> */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cita::class);
    }

    /** @return BelongsTo<\App\Models\Venta, ProductoSerie> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Venta::class);
    }
}
