<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Vacacion extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function (Vacacion $vacacion) {
            if (empty($vacacion->folio)) {
                try {
                    $vacacion->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('vacacion');
                } catch (\Exception $e) {
                    Log::error('Error generating folio for vacacion: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * La tabla asociada con el modelo.
     * Necesario porque Laravel asumiría 'vacacions' (plural incorrecto).
     */
    protected $table = 'vacaciones';

    protected $fillable = [
        'folio',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'dias_pendientes',
        'dias_aprobados',
        'dias_rechazados',
        'motivo',
        'estado',
        'observaciones',
        'aprobador_id',
        'fecha_aprobacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_aprobacion' => 'date',
        ];
    }

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobador_id')->withDefault();
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado', 'rechazada');
    }

    public function scopeDelEmpleado($query, $empleadoId)
    {
        return $query->where('user_id', $empleadoId);
    }

    // Métodos útiles
    public function getDiasTotalesAttribute()
    {
        return $this->dias_aprobados + $this->dias_rechazados + $this->dias_pendientes;
    }

    /**
     * Aprobar solicitud de vacaciones con bloqueo pesimista.
     * Error #2 Fix: Previene condición de carrera en aprobación simultánea.
     *
     * @throws \Exception Si la solicitud ya fue procesada
     */
    public function aprobar($aprobadorId, $observaciones = null)
    {
        return DB::transaction(function () use ($aprobadorId, $observaciones) {
            // Bloqueo pesimista: prevenir procesamiento simultáneo
            $vacacion = static::lockForUpdate()->find($this->id);

            if ($vacacion->estado !== 'pendiente') {
                throw new \Exception('La solicitud ya fue procesada. Estado actual: ' . $vacacion->estado);
            }

            $vacacion->update([
                'estado' => 'aprobada',
                'aprobador_id' => $aprobadorId,
                'fecha_aprobacion' => now(),
                'dias_aprobados' => $vacacion->dias_solicitados,
                'dias_pendientes' => 0,
                'dias_rechazados' => 0,
                'observaciones' => $observaciones,
            ]);

            // Registrar el uso de días en el sistema de registro de vacaciones
            $registroActual = RegistroVacaciones::actualizarRegistroAnual($vacacion->user_id);
            if ($registroActual) {
                $registroActual->registrarUsoVacaciones($vacacion->dias_solicitados);
            }

            Log::info('Vacaciones aprobadas', [
                'vacacion_id' => $vacacion->id,
                'user_id' => $vacacion->user_id,
                'dias' => $vacacion->dias_solicitados,
                'aprobador_id' => $aprobadorId,
            ]);

            return $vacacion->fresh();
        });
    }

    /**
     * Rechazar solicitud de vacaciones con bloqueo pesimista.
     * Error #2 Fix: Previene condición de carrera.
     *
     * @throws \Exception Si la solicitud ya fue procesada
     */
    public function rechazar($aprobadorId, $observaciones = null)
    {
        return DB::transaction(function () use ($aprobadorId, $observaciones) {
            $vacacion = static::lockForUpdate()->find($this->id);

            if ($vacacion->estado !== 'pendiente') {
                throw new \Exception('La solicitud ya fue procesada. Estado actual: ' . $vacacion->estado);
            }

            $vacacion->update([
                'estado' => 'rechazada',
                'aprobador_id' => $aprobadorId,
                'fecha_aprobacion' => now(),
                'dias_aprobados' => 0,
                'dias_pendientes' => 0,
                'dias_rechazados' => $vacacion->dias_solicitados,
                'observaciones' => $observaciones,
            ]);

            Log::info('Vacaciones rechazadas', [
                'vacacion_id' => $vacacion->id,
                'user_id' => $vacacion->user_id,
                'aprobador_id' => $aprobadorId,
            ]);

            return $vacacion->fresh();
        });
    }

    /**
     * Cancelar vacaciones y revertir días si estaban aprobadas.
     * Error #3 Fix: Devuelve los días utilizados al registro del empleado.
     *
     * @param string|null $observaciones
     * @return static
     */
    public function cancelar($observaciones = null)
    {
        return DB::transaction(function () use ($observaciones) {
            $vacacion = static::lockForUpdate()->find($this->id);

            // Si estaba aprobada, revertir los días utilizados
            if ($vacacion->estado === 'aprobada') {
                $registro = RegistroVacaciones::where('user_id', $vacacion->user_id)
                    ->where('anio', now()->year)
                    ->first();

                if ($registro) {
                    $registro->revertirUsoVacaciones($vacacion->dias_solicitados);
                }

                Log::info('Días de vacaciones revertidos por cancelación', [
                    'vacacion_id' => $vacacion->id,
                    'user_id' => $vacacion->user_id,
                    'dias_revertidos' => $vacacion->dias_solicitados,
                ]);
            }

            $vacacion->update([
                'estado' => 'cancelada',
                'observaciones' => $observaciones,
                'dias_aprobados' => 0,
                'dias_pendientes' => 0,
            ]);

            return $vacacion->fresh();
        });
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'pendiente' => 'yellow',
            'aprobada' => 'green',
            'rechazada' => 'red',
            'cancelada' => 'gray',
            default => 'gray'
        };
    }

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'aprobada' => 'Aprobada',
            'rechazada' => 'Rechazada',
            'cancelada' => 'Cancelada',
            default => 'Desconocido'
        };
    }
}
