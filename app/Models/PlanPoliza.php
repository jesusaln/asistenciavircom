<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PlanPoliza extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'plan_polizas';

    protected $appends = ['ahorro_anual', 'porcentaje_descuento_anual', 'icono_display', 'tipo_label', 'beneficios_array'];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'slug',
        'descripcion',
        'descripcion_corta',
        'tipo',
        'icono',
        'color',
        'precio_mensual',
        'precio_anual',
        'precio_instalacion',
        'horas_incluidas',
        'tickets_incluidos',
        'sla_horas_respuesta',
        'costo_hora_extra',
        'beneficios',
        'incluye_servicios',
        'activo',
        'destacado',
        'visible_catalogo',
        'orden',
        'min_equipos',
        'max_equipos',
        'imagen',
        'clausulas',
        'terminos_pago',
        'mantenimiento_frecuencia_meses',
        'mantenimiento_dias_anticipacion',
        'generar_cita_automatica',
        'visitas_sitio_mensuales',
        'costo_visita_sitio_extra',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'precio_instalacion' => 'decimal:2',
        'costo_hora_extra' => 'decimal:2',
        'costo_visita_sitio_extra' => 'decimal:2',
        'beneficios' => 'array',
        'incluye_servicios' => 'array',
        'activo' => 'boolean',
        'destacado' => 'boolean',
        'visible_catalogo' => 'boolean',
        'generar_cita_automatica' => 'boolean',
    ];

    /**
     * Boot del modelo - generar slug automáticamente.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->nombre) . '-' . Str::random(6);
            }
        });
    }

    /**
     * Scope para planes activos y visibles en catálogo.
     */
    public function scopePublicos($query)
    {
        return $query->where('activo', true)->where('visible_catalogo', true);
    }

    /**
     * Scope para planes activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para planes destacados.
     */
    public function scopeDestacados($query)
    {
        return $query->where('destacado', true);
    }

    /**
     * Ordenar por orden y luego por nombre.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Obtener el ahorro anual si hay precio anual.
     */
    public function getAhorroAnualAttribute()
    {
        if (!$this->precio_anual) {
            return 0;
        }

        $precioAnualSinDescuento = $this->precio_mensual * 12;
        return max(0, $precioAnualSinDescuento - $this->precio_anual);
    }

    /**
     * Obtener el porcentaje de descuento anual.
     */
    public function getPorcentajeDescuentoAnualAttribute()
    {
        if (!$this->precio_anual || $this->precio_mensual <= 0) {
            return 0;
        }

        $precioAnualSinDescuento = $this->precio_mensual * 12;
        if ($precioAnualSinDescuento <= 0)
            return 0;

        return round((($precioAnualSinDescuento - $this->precio_anual) / $precioAnualSinDescuento) * 100);
    }

    /**
     * Obtener el icono o un valor por defecto según el tipo.
     */
    public function getIconoDisplayAttribute()
    {
        if ($this->icono) {
            return $this->icono;
        }

        $iconosPorTipo = [
            'mantenimiento' => '🔧',
            'soporte' => '🛠️',
            'garantia' => '✅',
            'premium' => '⭐',
            'personalizado' => '🎯',
        ];

        return $iconosPorTipo[$this->tipo] ?? '🛡️';
    }
    /**
     * Tipos de plan disponibles.
     */
    public static function tipos(): array
    {
        return [
            'mantenimiento' => 'Mantenimiento',
            'soporte' => 'Soporte Técnico',
            'garantia' => 'Garantía Extendida',
            'premium' => 'Premium / VIP',
            'personalizado' => 'Personalizado',
        ];
    }

    /**
     * Obtener el nombre legible del tipo de plan.
     */
    public function getTipoLabelAttribute()
    {
        return self::tipos()[$this->tipo] ?? ucfirst($this->tipo);
    }

    /**
     * Asegurar que los beneficios siempre se devuelvan como un array.
     */
    public function getBeneficiosArrayAttribute()
    {
        return is_array($this->beneficios) ? $this->beneficios : [];
    }
}
