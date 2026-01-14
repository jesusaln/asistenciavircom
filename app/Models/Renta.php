<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Pago;
use App\Models\Concerns\BelongsToEmpresa;

class Renta extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected static function booted()
    {
        static::creating(function (Renta $renta) {
            if (empty($renta->numero_contrato)) {
                try {
                    $renta->numero_contrato = app(\App\Services\Folio\FolioService::class)->getNextFolio('renta');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error generating folio for renta: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Nombre de la tabla.
     */
    protected $table = 'rentas';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'empresa_id',
        'numero_contrato',
        'cliente_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_firma',
        'monto_mensual',
        'deposito_garantia',
        'dia_pago',
        'estado',
        'ultimo_pago',
        'saldo_pendiente',
        'meses_mora',
        'condiciones_especiales',
        'renovacion_automatica',
        'meses_duracion',
        'lugar_instalacion',
        'fecha_instalacion',
        'fecha_retiro',
        'notas_instalacion',
        'notas_retiro',
        'responsable_entrega',
        'responsable_recibe',
        'observaciones',
        'historial_cambios',
        'forma_pago',
        'referencia_pago',
    ];

    /**
     * Atributos que deben convertirse a tipos nativos.
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_firma' => 'date',
        'ultimo_pago' => 'date',
        'fecha_instalacion' => 'date',
        'fecha_retiro' => 'date',
        'monto_mensual' => 'decimal:2',
        'deposito_garantia' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'renovacion_automatica' => 'boolean',
        'lugar_instalacion' => 'json',
        'historial_cambios' => 'json',
        'condiciones_especiales' => 'encrypted', // Opcional: cifrar condiciones sensibles
        'observaciones' => 'encrypted', // Opcional
    ];

    /**
     * Mutaciones de fecha.
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    /**
     * Relación con Cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Equipos (a través de una tabla pivot).
     * Asumiendo que tienes una tabla `equipo_renta` con:
     * - renta_id
     * - equipo_id
     * - precio_mensual (precio individual del equipo en esta renta)
     */
    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Equipo::class, 'equipo_renta')
            ->withPivot('precio_mensual')
            ->withTimestamps();
    }

    /**
     * Relación con Pagos.
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'renta_id');
    }

    /**
     * Relación con Cobranzas.
     */
    public function cobranzas(): HasMany
    {
        return $this->hasMany(\App\Models\Cobranza::class, 'renta_id');
    }

    /**
     * Relación unificada con Cuentas por Cobrar.
     */
    public function cuentasPorCobrar()
    {
        return $this->morphMany(CuentasPorCobrar::class, 'cobrable');
    }

    /**
     * Scope para encontrar rentas próximas a vencer (en 30 días).
     */
    public function scopeProximoVencimiento($query)
    {
        $hoy = now();
        $dentroDe30Dias = $hoy->copy()->addDays(30);
        return $query->where('fecha_fin', '>', $hoy)
            ->where('fecha_fin', '<=', $dentroDe30Dias)
            ->where('estado', 'activo');
    }

    /**
     * Scope para rentas vencidas o morosas.
     */
    public function scopeVencidas($query)
    {
        return $query->where('fecha_fin', '<', now())
            ->whereIn('estado', ['vencido', 'moroso']);
    }
}
