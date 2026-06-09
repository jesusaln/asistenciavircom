<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteDireccion extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'cliente_direcciones';

    protected $fillable = [
        'cliente_id',
        'nombre',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'ciudad',
        'municipio',
        'estado',
        'pais',
        'referencias',
        'contacto_nombre',
        'contacto_telefono',
        'es_principal',
        'activo',
    ];

    /**
     * Relación con el Cliente propietario de esta dirección.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Scope para direcciones activas.
     */
    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }
}
