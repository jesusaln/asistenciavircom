<?php

namespace App\Models\Contab;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaContable extends Model
{
    use BelongsToEmpresa, SoftDeletes;

    protected $table = 'contab_cuentas';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'tipo',
        'naturaleza',
        'nivel',
        'padre_id',
        'es_detalle',
        'sat_codigo',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(CuentaContable::class, 'padre_id');
    }

    public function asientos(): HasMany
    {
        return $this->hasMany(AsientoContable::class, 'cuenta_id');
    }
}
