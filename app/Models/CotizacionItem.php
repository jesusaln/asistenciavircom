<?php
//cambioamayusuclas
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CotizacionItem extends Model
{

    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'cotizacion_id',
        'cotizable_id',
        'cotizable_type',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'descuento_monto',
        'price_list_id',
    ];

    public function cotizable()
    {
        return $this->morphTo();
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function priceList()
    {
        return $this->belongsTo(\App\Models\PriceList::class);
    }
}
