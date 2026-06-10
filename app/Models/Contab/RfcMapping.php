<?php

namespace App\Models\Contab;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfcMapping extends Model
{
    use BelongsToEmpresa;

    protected $table = 'contab_rfc_mappings';

    protected $fillable = [
        'empresa_id',
        'rfc',
        'cuenta_id',
        'nombre_auxiliar',
        'ai_reasoning',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
