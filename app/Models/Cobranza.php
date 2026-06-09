<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToEmpresa;

class Cobranza extends Model
{
    use HasFactory, BelongsToEmpresa;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'cobranzas';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'empresa_id',
        'renta_id',
        'fecha_cobro',
        'monto_cobrado',
        'concepto',
        'estado',
        'fecha_pago',
        'monto_pagado',
        'metodo_pago',
        'referencia_pago',
        'recibido_por',
        'notas_pago',
        'notas',
        'responsable_cobro',
    ];

    /**
     * Atributos que deben convertirse a tipos nativos.
     */
    protected $casts = [
        'fecha_cobro' => 'date',
        'fecha_pago' => 'datetime',
        'monto_cobrado' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'responsable_cobro' => 'integer',
    ];

    /**
     * RelaciÃ³n con Renta.
     */
    public function renta(): BelongsTo
    {
        return $this->belongsTo(Renta::class, 'renta_id');
    }

    /**
     * Scope para cobranzas pendientes.
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para cobranzas pagadas.
     */
    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagado');
    }

    /**
     * Scope para cobranzas vencidas.
     */
    public function scopeVencidas($query)
    {
        return $query->where('estado', 'vencido')
            ->where('fecha_cobro', '<', now());
    }

    /**
     * RelaciÃ³n con el usuario responsable del cobro.
     */
    public function responsableCobro(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_cobro');
    }

    /**
     * Scope para cobranzas del mes actual.
     */
    public function scopeDelMes($query, $mes = null, $anio = null)
    {
        $mes = $mes ?? now()->month;
        $anio = $anio ?? now()->year;

        return $query->whereYear('fecha_cobro', $anio)
            ->whereMonth('fecha_cobro', $mes);
    }
}
