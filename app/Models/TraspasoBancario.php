<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TraspasoBancario extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'traspaso_bancarios';

    protected $fillable = [
        'empresa_id',
        'cuenta_origen_id',
        'cuenta_destino_id',
        'monto',
        'fecha',
        'referencia',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function origen(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_origen_id');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
