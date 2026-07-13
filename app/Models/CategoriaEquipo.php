<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class CategoriaEquipo extends Model
{
    use BelongsToEmpresa;

    protected $table = 'categorias_equipos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'valor',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public static function getOpciones(?int $empresaId = null): array
    {
        $empresaId = $empresaId ?: \App\Support\EmpresaResolver::resolveId();
        return self::where('empresa_id', $empresaId)
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['valor', 'nombre'])
            ->toArray();
    }
}
