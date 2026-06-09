<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraspasoItem extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'traspaso_items';

    protected $fillable = [
        'empresa_id',
        'traspaso_id',
        'producto_id',
        'cantidad',
        'series_ids',
    ];

    protected $casts = [
        'series_ids' => 'array',
        'cantidad' => 'integer',
    ];

    /**
     * Relación con el traspaso padre
     */
    public function traspaso(): BelongsTo
    {
        return $this->belongsTo(Traspaso::class);
    }

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Obtener las series asociadas a este item
     */
    public function getSeries()
    {
        if (empty($this->series_ids)) {
            return collect();
        }

        return ProductoSerie::whereIn('id', $this->series_ids)->get();
    }
}
