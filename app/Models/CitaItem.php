<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CitaItem extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $fillable = [
        'empresa_id',
        'cita_id',
        'citable_id',
        'citable_type',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'descuento_monto',
        'notas',
    ];

    public function citable()
    {
        return $this->morphTo();
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }
}
