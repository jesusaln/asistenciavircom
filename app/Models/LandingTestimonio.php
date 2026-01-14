<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Concerns\BelongsToEmpresa;

class LandingTestimonio extends Model
{
    use BelongsToEmpresa;

    protected $table = 'landing_testimonios';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'cargo',
        'empresa_cliente',
        'comentario',
        'calificacion',
        'foto',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'calificacion' => 'integer',
    ];

    protected $appends = ['foto_url', 'iniciales', 'contenido', 'entidad'];

    // Alias para compatibilidad con Vue template
    public function getContenidoAttribute()
    {
        return $this->comentario;
    }

    public function getEntidadAttribute()
    {
        return $this->empresa_cliente ?? 'Cliente';
    }


    public function getFotoUrlAttribute()
    {
        if (!$this->foto)
            return null;
        return Storage::url($this->foto);
    }

    public function getInicialesAttribute()
    {
        $palabras = explode(' ', $this->nombre);
        $iniciales = '';
        foreach (array_slice($palabras, 0, 2) as $palabra) {
            $iniciales .= mb_substr($palabra, 0, 1);
        }
        return mb_strtoupper($iniciales);
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('id');
    }
}
