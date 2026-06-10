<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioFisicoItem extends Model
{
    use BelongsToEmpresa;

    use HasFactory;

    protected $table = 'inventario_fisico_items';

    protected $fillable = [
        'inventario_fisico_id',
        'producto_id',
        'stock_sistema',
        'stock_fisico',
        'diferencia',
        'ajustado',
    ];

    public function inventarioFisico(): BelongsTo
    {
        return $this->belongsTo(InventarioFisico::class, 'inventario_fisico_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
