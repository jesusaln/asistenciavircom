<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionHerramienta extends Model
{
    use HasFactory;

    protected $fillable = [
        'herramienta_id',
        'tecnico_id',
        'tipo_asignacion', // 'entrega' o 'recepcion'
        'fecha_asignacion',
        'firma_entrega',
        'firma_recepcion',
        'observaciones_entrega',
        'observaciones_recepcion',
        'estado_herramienta_entrega',
        'estado_herramienta_recepcion',
        'foto_estado_entrega',
        'foto_estado_recepcion',
        'usuario_entrega_id',
        'usuario_recepcion_id',
        'activo'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'activo' => 'boolean',
    ];

    // Relaciones
    public function herramienta()
    {
        return $this->belongsTo(Herramienta::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    public function usuarioEntrega()
    {
        return $this->belongsTo(User::class, 'usuario_entrega_id');
    }

    public function usuarioRecepcion()
    {
        return $this->belongsTo(User::class, 'usuario_recepcion_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeEntregas($query)
    {
        return $query->where('tipo_asignacion', 'entrega');
    }

    public function scopeRecepciones($query)
    {
        return $query->where('tipo_asignacion', 'recepcion');
    }

    // Métodos auxiliares
    public function esEntrega()
    {
        return $this->tipo_asignacion === 'entrega';
    }

    public function esRecepcion()
    {
        return $this->tipo_asignacion === 'recepcion';
    }

    public function estaActiva()
    {
        return $this->activo;
    }

    public function tieneFirmaEntrega()
    {
        return !empty($this->firma_entrega);
    }

    public function tieneFirmaRecepcion()
    {
        return !empty($this->firma_recepcion);
    }

    public function tieneTodasLasFirmas()
    {
        return $this->tieneFirmaEntrega() && $this->tieneFirmaRecepcion();
    }

    // Obtener la última asignación activa de una herramienta
    public static function getAsignacionActiva($herramientaId)
    {
        return static::where('herramienta_id', $herramientaId)
            ->where('activo', true)
            ->latest('fecha_asignacion')
            ->first();
    }

    // Verificar si una herramienta está asignada actualmente
    public static function herramientaEstaAsignada($herramientaId)
    {
        $asignacion = static::getAsignacionActiva($herramientaId);
        return $asignacion && $asignacion->esEntrega() && !$asignacion->esRecepcion();
    }
}
