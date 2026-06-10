<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class CitaHistorial extends Model
{
    use BelongsToEmpresa;

    protected $table = 'cita_historial';

    protected $fillable = [
        'cita_id',
        'user_id',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'metadatos',
    ];

    protected $casts = [
        'metadatos' => 'array',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
