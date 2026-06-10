<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Herramienta;
use App\Models\TransferenciaHerramientaItem;
use App\Models\Concerns\BelongsToEmpresa;

class TransferenciaHerramienta extends Model
{
    use BelongsToEmpresa;

    protected $table = 'transferencias_herramientas';

    protected $fillable = [
        'emisor_id',
        'receptor_id',
        'estado',
        'observaciones',
        'empresa_id'
    ];

    public function emisor()
    {
        return $this->belongsTo(User::class, 'emisor_id');
    }

    public function receptor()
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function items()
    {
        return $this->hasMany(TransferenciaHerramientaItem::class, 'transferencia_id');
    }

    public function herramientas()
    {
        return $this->belongsToMany(Herramienta::class, 'transferencia_herramienta_items', 'transferencia_id', 'herramienta_id');
    }
}
