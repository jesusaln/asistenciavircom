<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToEmpresa;

class Credencial extends Model
{
    use HasFactory, SoftDeletes, BelongsToEmpresa;

    protected $table = 'credenciales';

    protected $fillable = [
        'empresa_id',
        'credentialable_id',
        'credentialable_type',
        'nombre',
        'usuario',
        'password',
        'host',
        'puerto',
        'notas',
        'created_by'
    ];

    protected $casts = [
        'password' => 'encrypted', // Esta es la mayor seguridad de Laravel
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación polimórfica (Cliente o PolizaServicio)
     */
    public function credentialable()
    {
        return $this->morphTo();
    }

    /**
     * Creador de la credencial
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Logs de acceso
     */
    public function logs()
    {
        return $this->hasMany(CredencialAccesoLog::class);
    }

    /**
     * Registrar acceso para auditoría
     */
    public function registrarAcceso($accion = 'revelado')
    {
        $this->logs()->create([
            'user_id' => auth()->id(),
            'accion' => $accion,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
