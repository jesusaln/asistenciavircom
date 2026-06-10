<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class LandingOferta extends Model
{
    use BelongsToEmpresa;

    protected $table = 'landing_ofertas';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'subtitulo',
        'descripcion',
        'descuento_porcentaje',
        'precio_original',
        'precio_oferta',
        'caracteristica_1',
        'caracteristica_2',
        'caracteristica_3',
        'fecha_inicio',
        'fecha_fin',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'descuento_porcentaje' => 'integer',
        'precio_original' => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVigente($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_fin')
                ->orWhere('fecha_fin', '>', now());
        })->where(function ($q) {
            $q->whereNull('fecha_inicio')
                ->orWhere('fecha_inicio', '<=', now());
        });
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('id', 'desc');
    }

    /**
     * Obtener las características como array
     */
    public function getCaracteristicasAttribute()
    {
        return array_filter([
            $this->caracteristica_1,
            $this->caracteristica_2,
            $this->caracteristica_3,
        ]);
    }

    /**
     * Verificar si la oferta está vigente
     */
    public function estaVigente()
    {
        $ahora = now();

        if ($this->fecha_inicio && $ahora < $this->fecha_inicio) {
            return false;
        }

        if ($this->fecha_fin && $ahora > $this->fecha_fin) {
            return false;
        }

        return $this->activo;
    }

    /**
     * Obtener tiempo restante en segundos
     */
    public function getTiempoRestanteAttribute()
    {
        if (!$this->fecha_fin) {
            // Si no hay fecha de fin, devolver 24 horas
            return 24 * 60 * 60;
        }

        $diff = $this->fecha_fin->diffInSeconds(now(), false);
        return max(0, -$diff);
    }
}
