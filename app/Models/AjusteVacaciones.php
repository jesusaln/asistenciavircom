<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AjusteVacaciones extends Model
{
    use HasFactory;

    protected $table = 'ajustes_vacaciones';

    protected $fillable = [
        'user_id',
        'anio',
        'dias',
        'motivo',
        'creado_por',
    ];

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}

