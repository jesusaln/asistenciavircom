<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CredencialAccesoLog extends Model
{
    protected $table = 'credenciales_accesos_logs';

    protected $fillable = [
        'credencial_id',
        'user_id',
        'accion',
        'ip_address',
        'user_agent'
    ];

    public function credencial()
    {
        return $this->belongsTo(Credencial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
