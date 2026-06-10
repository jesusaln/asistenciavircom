<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class SolicitudMaterialItem extends Model
{
    use BelongsToEmpresa;

    protected $table = 'solicitud_material_items';

    protected $fillable = [
        'solicitud_material_id',
        'producto_id',
        'descripcion',
        'cantidad',
        'unidad_medida',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudMaterial::class, 'solicitud_material_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
