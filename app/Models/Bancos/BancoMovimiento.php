<?php

namespace App\Models\Bancos;

use App\Models\Contab\PolizaContable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BancoMovimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bancos_movimientos';

    protected $fillable = [
        'cuenta_bancaria_id',
        'fecha',
        'tipo',
        'forma_pago_sat',
        'monto',
        'concepto',
        'referencia',
        'beneficiario_rfc',
        'beneficiario_nombre',
        'poliza_id',
        'conciliable_type',
        'conciliable_id',
        'estado_conciliacion',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(BancoCuenta::class, 'cuenta_bancaria_id');
    }

    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaContable::class, 'poliza_id');
    }

    public function conciliable()
    {
        return $this->morphTo();
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
