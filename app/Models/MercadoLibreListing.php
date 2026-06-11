<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;

class MercadoLibreListing extends Model
{
    use BelongsToEmpresa;

    protected $table = 'mercadolibre_listings';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'listing_id',
        'permalink',
        'status',
        'price',
        'stock_published',
        'meli_category_id',
        'title',
        'thumbnail',
        'last_sync_at',
    ];

    protected $casts = [
        'listing_id' => 'string',
        'price' => 'decimal:2',
        'stock_published' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
