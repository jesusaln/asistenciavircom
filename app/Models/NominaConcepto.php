<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NominaConcepto extends Model
{
    use HasFactory;

    protected $table = 'nomina_conceptos';

    protected $fillable = [
        'nomina_id',
        'tipo',
        'concepto',
        'clave',
        'clave_sat',
        'monto',
        'porcentaje',
        'base_calculo',
        'es_automatico',
        'es_gravable',
        'es_exento',
        'prestamo_id',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'porcentaje' => 'decimal:2',
            'base_calculo' => 'decimal:2',
            'es_automatico' => 'boolean',
            'es_gravable' => 'boolean',
            'es_exento' => 'boolean',
        ];
    }

    // ========================================
    // Relaciones
    // ========================================

    public function nomina()
    {
        return $this->belongsTo(Nomina::class);
    }

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }

    // ========================================
    // Accessors
    // ========================================

    /**
     * Tipo formateado
     */
    public function getTipoFormateadoAttribute()
    {
        return $this->tipo === 'percepcion' ? 'Percepción' : 'Deducción';
    }

    /**
     * Es percepción
     */
    public function getEsPercepcionAttribute()
    {
        return $this->tipo === 'percepcion';
    }

    /**
     * Es deducción
     */
    public function getEsDeduccionAttribute()
    {
        return $this->tipo === 'deduccion';
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopePercepciones($query)
    {
        return $query->where('tipo', 'percepcion');
    }

    public function scopeDeducciones($query)
    {
        return $query->where('tipo', 'deduccion');
    }

    public function scopeAutomaticos($query)
    {
        return $query->where('es_automatico', true);
    }

    public function scopeManuales($query)
    {
        return $query->where('es_automatico', false);
    }

    // ========================================
    // Métodos
    // ========================================

    /**
     * Calcular monto si tiene porcentaje
     */
    public function calcularMontoPorPorcentaje(float $base): void
    {
        if ($this->porcentaje) {
            $this->base_calculo = $base;
            $this->monto = round($base * ((float) $this->porcentaje / 100), 2);
            $this->save();
        }
    }
}
