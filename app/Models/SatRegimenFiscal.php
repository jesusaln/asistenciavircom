<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SatRegimenFiscal extends Model
{

    use HasFactory;

    protected $table = 'sat_regimenes_fiscales';

    public $incrementing = false;        // La PK no es autoincremental
    protected $primaryKey = 'clave';     // La PK es la columna 'clave'
    protected $keyType = 'string';       // La PK es de tipo string
    public $timestamps = false;          // No usamos created_at / updated_at

    protected $fillable = [
        'clave',
        'descripcion',
        'persona_fisica',
        'persona_moral',
    ];

    protected $casts = [
        'persona_fisica' => 'boolean',
        'persona_moral'  => 'boolean',
    ];

    /**
     * Relación inversa: clientes que usan este régimen
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'regimen_fiscal', 'clave');
    }
}
