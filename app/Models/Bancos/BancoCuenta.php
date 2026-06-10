<?php

namespace App\Models\Bancos;

use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Contab\CuentaContable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BancoCuenta extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'bancos_cuentas';

    protected $fillable = [
        'empresa_id',
        'nombre_banco',
        'alias',
        'numero_cuenta',
        'clabe',
        'moneda',
        'saldo_inicial',
        'saldo_actual',
        'cuenta_contable_id',
        'es_fiscal',
        'tipo',
    ];

    protected $casts = [
        'saldo_inicial' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'es_fiscal' => 'boolean',
    ];

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(BancoMovimiento::class, 'cuenta_bancaria_id');
    }
}
