<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SatUsoCfdi extends Model
{

    use HasFactory;

    protected $table = 'sat_usos_cfdi';

    public $incrementing = false;        // La PK no es autoincremental
    protected $primaryKey = 'clave';     // La PK es la columna 'clave'
    protected $keyType = 'string';       // La PK es string (ej. "G03")
    public $timestamps = false;          // No usamos created_at / updated_at

    protected $fillable = [
        'clave',
        'descripcion',
        'persona_fisica',
        'persona_moral',
        'activo',
    ];

    protected $casts = [
        'persona_fisica' => 'boolean',
        'persona_moral' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Relación inversa: clientes que usan este uso CFDI por defecto
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'uso_cfdi', 'clave');
    }
}
