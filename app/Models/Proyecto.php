<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'owner_id',
        'cliente_id',
        'color',
    ];

    /**
     * Tareas asociadas a este proyecto.
     */
    public function tareas()
    {
        return $this->hasMany(ProyectoTarea::class);
    }

    /**
     * El creador/dueño del proyecto.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Cliente asociado al proyecto.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Usuarios con los que se ha compartido este proyecto.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'proyecto_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Gastos operativos asociados a este proyecto.
     */
    public function gastos()
    {
        return $this->hasMany(Compra::class)->where('tipo', 'gasto');
    }

    /**
     * Total de gastos del proyecto.
     */
    public function getTotalGastosAttribute(): float
    {
        return $this->gastos()->sum('total');
    }

    /**
     * Productos del inventario asociados a este proyecto.
     */
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'proyecto_productos')
            ->withPivot('cantidad', 'precio_unitario', 'notas')
            ->withTimestamps();
    }

    /**
     * Total de productos del proyecto (cantidad * precio).
     */
    public function getTotalProductosAttribute(): float
    {
        return $this->productos->sum(function ($producto) {
            $precio = $producto->pivot->precio_unitario ?? $producto->precio_venta ?? 0;
            return $producto->pivot->cantidad * $precio;
        });
    }

    /**
     * Total general del proyecto (gastos + productos).
     */
    public function getTotalGeneralAttribute(): float
    {
        return $this->total_gastos + $this->total_productos;
    }
}
