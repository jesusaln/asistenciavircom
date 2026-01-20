<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PlanRenta extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'plan_rentas';

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
        'deposito_garantia',
        'meses_minimos',
        'beneficios',
        'equipamiento_incluido',
        'activo',
        'destacado',
        'visible_catalogo',
        'orden',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'meses_minimos' => 'integer',
        'beneficios' => 'array',
        'equipamiento_incluido' => 'array',
        'activo' => 'boolean',
        'destacado' => 'boolean',
        'visible_catalogo' => 'boolean',
        'orden' => 'integer',
    ];

    protected $appends = ['icono_display', 'tipo_label'];

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
     * Scope para ordenar.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    /**
     * Obtener el icono o un valor por defecto.
     */
    public function getIconoDisplayAttribute()
    {
        return $this->icono ?: '🖥️';
    }

    /**
     * Tipos de plan disponibles.
     */
    public static function tipos(): array
    {
        return [
            'pdv' => 'Punto de Venta',
            'oficina' => 'Oficina / Administrativo',
            'gaming' => 'Gaming / Alto Desempeño',
            'laptop' => 'Laptop / Movilidad',
            'personalizado' => 'Plan Personalizado',
        ];
    }

    /**
     * Obtener el nombre legible del tipo de plan.
     */
    public function getTipoLabelAttribute()
    {
        return self::tipos()[$this->tipo] ?? ucfirst($this->tipo);
    }
}
