<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo SAT c_FormaPago
 * 
 * Formas de pago para CFDI 4.0:
 * - 01: Efectivo
 * - 02: Cheque nominativo
 * - 03: Transferencia electrónica de fondos
 * - 04: Tarjeta de crédito
 * - 28: Tarjeta de débito
 * - 99: Por definir
 * - etc.
 */
class SatFormaPago extends Model
{
    protected $table = 'sat_formas_pago';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'clave',
        'descripcion',
        'bancarizado',
        'patron_cuenta_bancaria',
        'orden',
        'activo',
    ];

    protected $casts = [
        'bancarizado' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Scope para obtener solo formas activas
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por campo orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('clave');
    }

    /**
     * Obtener opciones para select
     */
    public static function getOptions(): array
    {
        return self::activos()
            ->ordenado()
            ->get()
            ->mapWithKeys(fn($item) => [$item->clave => $item->clave . ' - ' . $item->descripcion])
            ->toArray();
    }

    /**
     * Obtener opciones comunes para select (las más usadas)
     */
    public static function getOpcionesComunes(): array
    {
        $comunes = ['01', '02', '03', '04', '28', '99'];
        
        return self::whereIn('clave', $comunes)
            ->activos()
            ->ordenado()
            ->get()
            ->mapWithKeys(fn($item) => [$item->clave => $item->clave . ' - ' . $item->descripcion])
            ->toArray();
    }

    /**
     * Verificar si requiere datos bancarios
     */
    public function requiereDatosBancarios(): bool
    {
        return $this->bancarizado;
    }

    /**
     * Verificar si es efectivo
     */
    public function esEfectivo(): bool
    {
        return $this->clave === '01';
    }

    /**
     * Verificar si es transferencia
     */
    public function esTransferencia(): bool
    {
        return $this->clave === '03';
    }

    /**
     * Verificar si es tarjeta (crédito o débito)
     */
    public function esTarjeta(): bool
    {
        return in_array($this->clave, ['04', '28']);
    }
}
