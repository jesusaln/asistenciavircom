<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carro extends Model
{
    use HasFactory;

    protected $fillable = [
        'marca',          // Marca del carro
        'modelo',         // Modelo del carro
        'anio',           // Año del carro
        'color',          // Color del carro
        'precio',         // Precio del carro
        'numero_serie',   // Número de serie único del carro
        'combustible',    // Tipo de combustible (Gasolina, Diésel, Eléctrico, Híbrido)
        'kilometraje',    // Kilometraje actual del carro
        'placa',          // Placa del carro
        'foto',           // Ruta de la foto del carro
        'activo',         // Estado del vehículo (activo/inactivo)
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio' => 'decimal:2',
        'anio' => 'integer',
        'kilometraje' => 'integer',
    ];



    /**
     * Obtener los mantenimientos del carro.
     */
    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'carro_id');
    }
}
