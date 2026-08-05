<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class EncuestaSatisfaccion extends Model
{
    use BelongsToEmpresa;

    protected $table = 'encuesta_satisfaccion';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cita_id',
        'wa_id',
        'calificacion',
        'comentario',
        'cupon_codigo',
        'cupon_porcentaje',
        'cupon_vigencia_hasta',
        'respondida_at',
        'origen',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'cupon_porcentaje' => 'integer',
        'cupon_vigencia_hasta' => 'datetime',
        'respondida_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
