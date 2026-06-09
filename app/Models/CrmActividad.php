<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActividad extends Model
{
    use HasFactory, BelongsToEmpresa;

    protected $table = 'crm_actividades';

    protected $fillable = [
        'empresa_id',
        'prospecto_id',
        'user_id',
        'tipo',
        'resultado',
        'notas',
        'duracion_minutos',
        'proxima_actividad_at',
        'proxima_actividad_tipo',
    ];

    protected $casts = [
        'proxima_actividad_at' => 'datetime',
        'duracion_minutos' => 'integer',
    ];

    public const TIPOS = [
        'llamada' => 'Llamada',
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'reunion' => 'Reunión',
        'visita' => 'Visita',
        'nota' => 'Nota',
    ];

    public const RESULTADOS = [
        'contactado' => 'Contactado',
        'no_contesto' => 'No contestó',
        'buzon' => 'Buzón de voz',
        'interesado' => 'Interesado',
        'no_interesado' => 'No interesado',
        'cita_agendada' => 'Cita agendada',
        'cotizacion_enviada' => 'Cotización enviada',
        'pendiente' => 'Pendiente',
    ];

    // Relaciones
    public function prospecto(): BelongsTo
    {
        return $this->belongsTo(CrmProspecto::class, 'prospecto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helpers
    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getResultadoLabelAttribute(): string
    {
        return self::RESULTADOS[$this->resultado] ?? $this->resultado ?? 'Sin resultado';
    }

    public function getTipoIconAttribute(): string
    {
        return match($this->tipo) {
            'llamada' => 'phone',
            'email' => 'envelope',
            'whatsapp' => 'whatsapp',
            'reunion' => 'users',
            'visita' => 'building',
            'nota' => 'sticky-note',
            default => 'comment',
        };
    }

    public function getResultadoColorAttribute(): string
    {
        return match($this->resultado) {
            'contactado' => 'blue',
            'interesado', 'cita_agendada', 'cotizacion_enviada' => 'green',
            'no_contesto', 'buzon', 'pendiente' => 'yellow',
            'no_interesado' => 'red',
            default => 'gray',
        };
    }
}
