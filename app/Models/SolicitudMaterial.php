<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;
use App\Models\Concerns\Blameable;

class SolicitudMaterial extends Model
{
    use BelongsToEmpresa;

    use SoftDeletes, BelongsToEmpresa, Blameable;

    protected $table = 'solicitud_materiales';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'folio',
        'tipo',
        'prioridad',
        'estado',
        'motivo',
        'comentarios_admin',
        'fecha_requerida',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SolicitudMaterialItem::class);
    }
}
