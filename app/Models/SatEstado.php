<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SatEstado extends Model
{

    use HasFactory;

    protected $table = 'sat_estados';

    public $incrementing = false;        // La PK no es autoincremental
    protected $primaryKey = 'clave';     // La PK es la columna 'clave'
    protected $keyType = 'string';       // Es string (ej. "SON")
    public $timestamps = false;          // No usamos created_at / updated_at

    protected $fillable = [
        'clave',
        'nombre',
    ];

    /**
     * Relación inversa: clientes registrados en este estado
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'estado', 'clave');
    }
}
