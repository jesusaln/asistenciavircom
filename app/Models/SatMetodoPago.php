<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo SAT c_MetodoPago
 * 
 * Métodos de pago para CFDI 4.0:
 * - PUE: Pago en una sola exhibición
 * - PPD: Pago en parcialidades o diferido
 * - PIP: Pago inicial y parcialidades (CFDI 4.0)
 */
class SatMetodoPago extends Model
{
    protected $table = 'sat_metodos_pago';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Scope para obtener solo métodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener opciones para select
     */
    public static function getOptions(): array
    {
        return self::activos()
            ->orderBy('clave')
            ->pluck('descripcion', 'clave')
            ->toArray();
    }

    /**
     * Verificar si es pago en parcialidades
     */
    public function esParcialidades(): bool
    {
        return in_array($this->clave, ['PPD', 'PIP']);
    }

    /**
     * Verificar si es pago de contado
     */
    public function esContado(): bool
    {
        return $this->clave === 'PUE';
    }
}
