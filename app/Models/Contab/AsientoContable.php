<?php

namespace App\Models\Contab;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsientoContable extends Model
{
    protected $table = 'contab_asientos';

    protected $fillable = [
        'poliza_id',
        'cuenta_id',
        'debe',
        'haber',
        'referencia',
    ];

    protected $casts = [
        'debe' => 'decimal:2',
        'haber' => 'decimal:2',
    ];

    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaContable::class, 'poliza_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
