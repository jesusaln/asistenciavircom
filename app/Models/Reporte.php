<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relación con Venta (uno a muchos inversa)
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    // Relación con Productos (muchos a muchos)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'reporte_producto', 'reporte_id', 'producto_id')
            ->withPivot('cantidad', 'precio');
    }
}
