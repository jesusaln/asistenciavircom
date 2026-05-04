<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MantenimientoHerramienta extends Model
{
    protected $table = 'mantenimientos_herramientas';

    protected $fillable = [
        'herramienta_id',
        'fecha_mantenimiento',
        'costo',
        'descripcion',
        'realizado_por',
        'tipo',
    ];

    protected $casts = [
        'fecha_mantenimiento' => 'date',
        'costo' => 'decimal:2',
    ];

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class);
    }

    public function realizadoPor()
    {
        return $this->belongsTo(User::class, 'realizado_por');
    }
}
