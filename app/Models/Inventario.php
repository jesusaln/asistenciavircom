<?php

// app/Models/Inventario.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = ['empresa_id', 'producto_id', 'almacen_id', 'cantidad', 'stock_minimo'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }
}
