<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class ProyectoTarea extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'titulo',
        'descripcion',
        'estado',
        'prioridad',
        'orden',
        'proyecto_id',
    ];

    protected $casts = [
        'orden' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }

}
