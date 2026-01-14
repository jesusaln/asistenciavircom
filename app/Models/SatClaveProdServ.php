<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo SAT c_ClaveProdServ
 * 
 * Claves de productos y servicios para CFDI 4.0
 * Se puede poblar bajo demanda desde XMLs de compras
 */
class SatClaveProdServ extends Model
{
    protected $table = 'sat_claves_prod_serv';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'descripcion',
        'incluir_iva_trasladado',
        'incluir_ieps_trasladado',
        'complemento',
        'palabras_similares',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'activo',
        'importado_xml',
    ];

    protected $casts = [
        'incluir_iva_trasladado' => 'boolean',
        'incluir_ieps_trasladado' => 'boolean',
        'activo' => 'boolean',
        'importado_xml' => 'boolean',
        'fecha_inicio_vigencia' => 'date',
        'fecha_fin_vigencia' => 'date',
    ];

    /**
     * Scope para obtener solo claves activas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para búsqueda por descripción o clave
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function($q) use ($termino) {
            $q->where('clave', 'LIKE', "%{$termino}%")
              ->orWhere('descripcion', 'LIKE', "%{$termino}%")
              ->orWhere('palabras_similares', 'LIKE', "%{$termino}%");
        });
    }

    /**
     * Crear o obtener una clave desde XML de compra
     * Si no existe, la crea con la descripción del XML
     * 
     * @param string $clave Clave del SAT (ej: "43231500")
     * @param string $descripcion Descripción del producto en el XML
     * @return self
     */
    public static function obtenerOCrearDesdeXml(string $clave, string $descripcion): self
    {
        return self::firstOrCreate(
            ['clave' => $clave],
            [
                'descripcion' => $descripcion,
                'incluir_iva_trasladado' => true,
                'incluir_ieps_trasladado' => false,
                'activo' => true,
                'importado_xml' => true,
            ]
        );
    }

    /**
     * Buscar claves para autocompletado
     * 
     * @param string $termino Término de búsqueda
     * @param int $limite Número máximo de resultados
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function buscarParaAutocompletado(string $termino, int $limite = 20)
    {
        return self::activos()
            ->buscar($termino)
            ->limit($limite)
            ->get(['clave', 'descripcion']);
    }

    /**
     * Obtener opciones para select
     */
    public static function getOptions(): array
    {
        return self::activos()
            ->orderBy('descripcion')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn($item) => [$item->clave => $item->clave . ' - ' . substr($item->descripcion, 0, 60)])
            ->toArray();
    }

    /**
     * Verificar si la clave está vigente
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
