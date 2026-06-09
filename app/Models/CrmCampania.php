<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmCampania extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'crm_campanias';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'producto_id',
        'descripcion',
        'objetivo',
        'fecha_inicio',
        'fecha_fin',
        'meta_actividades_dia',
        'activa',
        'created_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activa' => 'boolean',
        'meta_actividades_dia' => 'integer',
    ];

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(CrmScript::class, 'campania_id')->orderBy('orden');
    }

    public function metas(): HasMany
    {
        return $this->hasMany(CrmMeta::class, 'campania_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true)
            ->where('fecha_fin', '>=', now()->toDateString());
    }

    public function scopeVigentes($query)
    {
        return $query->where('fecha_inicio', '<=', now()->toDateString())
            ->where('fecha_fin', '>=', now()->toDateString());
    }

    // Helpers
    public function estaVigente(): bool
    {
        $hoy = now()->toDateString();
        return $this->activa 
            && $this->fecha_inicio <= $hoy 
            && $this->fecha_fin >= $hoy;
    }

    public function diasRestantes(): int
    {
        return max(0, now()->diffInDays($this->fecha_fin, false));
    }

    /**
     * Exportar datos para IA
     */
    public function toArrayParaIA(): array
    {
        return [
            'campania' => $this->nombre,
            'objetivo' => $this->objetivo,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'producto' => $this->producto ? [
                'nombre' => $this->producto->nombre,
                'codigo' => $this->producto->codigo,
                'precio' => $this->producto->precio_venta,
                'descripcion' => $this->producto->descripcion,
                'dias_garantia' => $this->producto->dias_garantia,
            ] : null,
            'instrucciones_ia' => "Genera scripts de ventas en español mexicano para esta campaña. Incluye:\n" .
                "1. Script de APERTURA (saludo inicial para llamada)\n" .
                "2. Script de PRESENTACION (características y beneficios del producto)\n" .
                "3. Scripts de OBJECION (mínimo 5 objeciones comunes con respuestas)\n" .
                "4. Script de CIERRE (cómo cerrar la venta)\n\n" .
                "IMPORTANTE: Devuelve SOLO un CSV con formato:\n" .
                "tipo,nombre,contenido,tips\n\n" .
                "Donde tipo es: apertura, presentacion, objecion, o cierre",
        ];
    }
}
