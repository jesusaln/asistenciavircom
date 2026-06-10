<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompraItem extends Model
{
    use BelongsToEmpresa;

    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'compra_id',
        'comprable_id',
        'comprable_type',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'subtotal',
        'descuento_monto',
        'unidad_medida',
        'descripcion', // Para items sin producto vinculado (importación masiva)
    ];

    public function comprable()
    {
        return $this->morphTo();
    }

    public function series()
    {
        return $this->hasMany(ProductoSerie::class, 'compra_item_id');
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }
}
