<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferenciaHerramientaItem extends Model
{
    protected $table = 'transferencia_herramienta_items';

    protected $fillable = [
        'transferencia_id',
        'herramienta_id'
    ];

    public function transferencia()
    {
        return $this->belongsTo(TransferenciaHerramienta::class, 'transferencia_id');
    }

    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class, 'herramienta_id');
    }
}
