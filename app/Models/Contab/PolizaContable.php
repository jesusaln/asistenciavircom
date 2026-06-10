<?php

namespace App\Models\Contab;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PolizaContable extends Model
{
    use BelongsToEmpresa, SoftDeletes;

    protected $table = 'contab_polizas';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'fecha',
        'numero',
        'concepto',
        'cfdi_uuid',
        'cfdi_uuids',
        'total',
        'estado',
        'created_by',
        'xml_content',
        'soportes',
        'banco_movimiento_id',
        'metodo_pago_sat',
        'clave_spei_rastreo',
        'rfc_tercero',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
        'cfdi_uuids' => 'array',
        'soportes' => 'array',
    ];

    public function asientos(): HasMany
    {
        return $this->hasMany(AsientoContable::class, 'poliza_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movimientoBancario(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Bancos\BancoMovimiento::class, 'banco_movimiento_id');
    }
}
