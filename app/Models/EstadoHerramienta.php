<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoHerramienta extends Model
{
    use HasFactory;

    protected $fillable = [
        'herramienta_id',
        'condicion_general',
        'porcentaje_desgaste',
        'necesita_mantenimiento',
        'necesita_reemplazo',
        'observaciones',
        'fecha_inspeccion',
        'inspeccionado_por',
        'foto_estado',
        'prioridad_mantenimiento'
    ];

    protected $casts = [
        'fecha_inspeccion' => 'datetime',
        'porcentaje_desgaste' => 'decimal:2',
        'necesita_mantenimiento' => 'boolean',
        'necesita_reemplazo' => 'boolean',
    ];

    // Estados posibles de condición
    const CONDICION_EXCELENTE = 'excelente';
    const CONDICION_BUENA = 'buena';
    const CONDICION_REGULAR = 'regular';
    const CONDICION_MALA = 'mala';
    const CONDICION_CRITICA = 'critica';

    // Prioridades de mantenimiento
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';

    // Relaciones
    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class);
    }

    public function inspeccionadoPor()
    {
        return $this->belongsTo(User::class, 'inspeccionado_por');
    }

    // Scopes
    public function scopeNecesitanMantenimiento($query)
    {
        return $query->where('necesita_mantenimiento', true);
    }

    public function scopeNecesitanReemplazo($query)
    {
        return $query->where('necesita_reemplazo', true);
    }

    public function scopePorCondicion($query, $condicion)
    {
        return $query->where('condicion_general', $condicion);
    }

    public function scopePorPrioridad($query, $prioridad)
    {
        return $query->where('prioridad_mantenimiento', $prioridad);
    }

    public function scopeDesgasteMayorA($query, $porcentaje)
    {
        return $query->where('porcentaje_desgaste', '>', $porcentaje);
    }

    // Métodos auxiliares
    public function getColorCondicionAttribute()
    {
        return match($this->condicion_general) {
            self::CONDICION_EXCELENTE => 'green',
            self::CONDICION_BUENA => 'blue',
            self::CONDICION_REGULAR => 'yellow',
            self::CONDICION_MALA => 'orange',
            self::CONDICION_CRITICA => 'red',
            default => 'gray'
        };
    }

    public function getColorPrioridadAttribute()
    {
        return match($this->prioridad_mantenimiento) {
            self::PRIORIDAD_BAJA => 'green',
            self::PRIORIDAD_MEDIA => 'yellow',
            self::PRIORIDAD_ALTA => 'orange',
            self::PRIORIDAD_URGENTE => 'red',
            default => 'gray'
        };
    }

    public function getLabelCondicionAttribute()
    {
        return match($this->condicion_general) {
            self::CONDICION_EXCELENTE => 'Excelente',
            self::CONDICION_BUENA => 'Buena',
            self::CONDICION_REGULAR => 'Regular',
            self::CONDICION_MALA => 'Mala',
            self::CONDICION_CRITICA => 'Crítica',
            default => 'Sin evaluar'
        };
    }

    public function getLabelPrioridadAttribute()
    {
        return match($this->prioridad_mantenimiento) {
            self::PRIORIDAD_BAJA => 'Baja',
            self::PRIORIDAD_MEDIA => 'Media',
            self::PRIORIDAD_ALTA => 'Alta',
            self::PRIORIDAD_URGENTE => 'Urgente',
            default => 'Sin prioridad'
        };
    }

    public function estaEnBuenEstado()
    {
        return in_array($this->condicion_general, [self::CONDICION_EXCELENTE, self::CONDICION_BUENA]);
    }

    public function necesitaAtencion()
    {
        return $this->necesita_mantenimiento || $this->necesita_reemplazo;
    }

    public function esPrioridadAlta()
    {
        return in_array($this->prioridad_mantenimiento, [self::PRIORIDAD_ALTA, self::PRIORIDAD_URGENTE]);
    }

    public function getDiasDesdeInspeccionAttribute()
    {
        return $this->fecha_inspeccion ? now()->diffInDays($this->fecha_inspeccion) : null;
    }

    // Obtener el último estado de una herramienta
    public static function getUltimoEstado($herramientaId)
    {
        return static::where('herramienta_id', $herramientaId)
            ->latest('fecha_inspeccion')
            ->first();
    }

    // Verificar si una herramienta necesita inspección (más de 30 días)
    public static function necesitaInspeccion($herramientaId)
    {
        $ultimoEstado = static::getUltimoEstado($herramientaId);
        if (!$ultimoEstado) return true;

        return $ultimoEstado->dias_desde_inspeccion > 30;
    }

    // Calcular prioridad basada en desgaste y condición
    public static function calcularPrioridad($porcentajeDesgaste, $condicion)
    {
        if ($porcentajeDesgaste >= 90 || $condicion === self::CONDICION_CRITICA) {
            return self::PRIORIDAD_URGENTE;
        } elseif ($porcentajeDesgaste >= 70 || $condicion === self::CONDICION_MALA) {
            return self::PRIORIDAD_ALTA;
        } elseif ($porcentajeDesgaste >= 40 || $condicion === self::CONDICION_REGULAR) {
            return self::PRIORIDAD_MEDIA;
        } else {
            return self::PRIORIDAD_BAJA;
        }
    }
}
