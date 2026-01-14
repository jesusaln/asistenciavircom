<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo SAT c_ClaveUnidad
 * 
 * Claves de unidad de medida para CFDI 4.0:
 * - H87: Pieza
 * - E48: Unidad de servicio
 * - ACT: Actividad
 * - KGM: Kilogramo
 * - LTR: Litro
 * - MTR: Metro
 * - etc.
 */
class SatClaveUnidad extends Model
{
    protected $table = 'sat_claves_unidad';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'simbolo',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'activo',
        'uso_comun',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'uso_comun' => 'boolean',
        'fecha_inicio_vigencia' => 'date',
        'fecha_fin_vigencia' => 'date',
    ];

    /**
     * Scope para obtener solo unidades activas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener unidades de uso común
     */
    public function scopeComunes($query)
    {
        return $query->where('uso_comun', true);
    }

    /**
     * Scope para búsqueda por nombre o clave
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('clave', 'LIKE', "%{$termino}%")
              ->orWhere('nombre', 'LIKE', "%{$termino}%")
              ->orWhere('descripcion', 'LIKE', "%{$termino}%");
        });
    }

    /**
     * Obtener opciones para select (uso común)
     */
    public static function getOptions(): array
    {
        return self::activos()
            ->comunes()
            ->orderBy('nombre')
            ->select('clave', 'nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todas las opciones para select
     */
    public static function getAllOptions(): array
    {
        return self::activos()
            ->orderBy('nombre')
             ->select('clave', 'nombre')
            ->get()
            ->toArray();
    }

    /**
     * Verificar si la unidad está vigente
     */
    public function estaVigente(): bool
    {
        $hoy = now()->startOfDay();
        
        if ($this->fecha_fin_vigencia && $this->fecha_fin_vigencia < $hoy) {
            return false;
        }
        
        return $this->activo;
    }
}
