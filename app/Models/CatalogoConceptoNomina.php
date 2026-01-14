<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoConceptoNomina extends Model
{
    use HasFactory;

    protected $table = 'catalogo_conceptos_nomina';

    protected $fillable = [
        'tipo',
        'clave',
        'nombre',
        'clave_sat',
        'descripcion',
        'porcentaje_default',
        'es_gravable',
        'es_automatico',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_default' => 'decimal:2',
            'es_gravable' => 'boolean',
            'es_automatico' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePercepciones($query)
    {
        return $query->where('tipo', 'percepcion');
    }

    public function scopeDeducciones($query)
    {
        return $query->where('tipo', 'deduccion');
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    // ========================================
    // Métodos estáticos
    // ========================================

    /**
     * Obtener percepciones activas para select
     */
    public static function percepcionesParaSelect()
    {
        return static::activos()
            ->percepciones()
            ->ordenado()
            ->get()
            ->map(fn ($c) => [
                'value' => $c->clave,
                'label' => $c->nombre,
                'clave_sat' => $c->clave_sat,
                'es_gravable' => $c->es_gravable,
            ]);
    }

    /**
     * Obtener deducciones activas para select
     */
    public static function deduccionesParaSelect()
    {
        return static::activos()
            ->deducciones()
            ->ordenado()
            ->get()
            ->map(fn ($c) => [
                'value' => $c->clave,
                'label' => $c->nombre,
                'clave_sat' => $c->clave_sat,
                'porcentaje' => $c->porcentaje_default,
            ]);
    }
}
