<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'clave',
        'descripcion',
        'activa',
        'orden',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Clientes que usan esta lista de precios
     */
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    /**
     * Precios de productos en esta lista
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Scope para obtener solo listas activas (incluye globales + empresa actual)
     */
    public function scopeActivas($query)
    {
        $empresaId = auth()->user()?->empresa_id;

        return $query->withoutGlobalScopes()
            ->where('activa', true)
            ->where(function ($q) use ($empresaId) {
                $q->whereNull('empresa_id')  // Listas globales (catálogo del sistema)
                    ->orWhere('empresa_id', $empresaId);  // Listas de esta empresa
            })
            ->orderBy('orden');
    }

    /**
     * Obtener la lista pública general (default)
     */
    public static function getPublicoGeneral()
    {
        return static::where('clave', 'publico_general')->first();
    }
}
