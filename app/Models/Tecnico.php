<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'direccion',
        'activo',
        'user_id',
        'margen_venta_productos',
        'margen_venta_servicios',
        'comision_instalacion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'margen_venta_productos' => 'decimal:2',
        'margen_venta_servicios' => 'decimal:2',
        'comision_instalacion' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
