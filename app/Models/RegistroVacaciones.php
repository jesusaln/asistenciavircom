<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RegistroVacaciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anio',
        'dias_correspondientes',
        'dias_disponibles',
        'dias_utilizados',
        'dias_pendientes',
        'dias_acumulados_siguiente',
        'fecha_calculo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'dias_correspondientes' => 'integer',
            'dias_disponibles' => 'integer',
            'dias_utilizados' => 'integer',
            'dias_pendientes' => 'integer',
            'dias_acumulados_siguiente' => 'integer',
            'fecha_calculo' => 'date',
        ];
    }

    // Relaciones
    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Calcular días de vacaciones según la ley mexicana "Vacaciones Dignas"
     * Basado en años de antigüedad del empleado
     */
    public static function calcularDiasSegunAntiguedad($aniosAntiguedad)
    {
        // Según la reforma "Vacaciones Dignas" de México
        if ($aniosAntiguedad >= 26) {
            return 30; // 26 años o más
        } elseif ($aniosAntiguedad >= 21) {
            return 28; // 21-25 años
        } elseif ($aniosAntiguedad >= 16) {
            return 26; // 16-20 años
        } elseif ($aniosAntiguedad >= 11) {
            return 24; // 11-15 años
        } elseif ($aniosAntiguedad >= 6) {
            return 22; // 6-10 años
        } elseif ($aniosAntiguedad == 5) {
            return 20; // 5 años
        } elseif ($aniosAntiguedad == 4) {
            return 18; // 4 años
        } elseif ($aniosAntiguedad == 3) {
            return 16; // 3 años
        } elseif ($aniosAntiguedad == 2) {
            return 14; // 2 años
        } elseif ($aniosAntiguedad >= 1) {
            return 12; // 1 año
        }

        return 0; // Menos de 1 año
    }

    /**
     * Crear o actualizar registro de vacaciones para un empleado en un año específico
     */
    public static function actualizarRegistroAnual($empleadoId, $anio = null)
    {
        $anio = $anio ?? now()->year;

        // Buscar registro existente
        $registro = self::where('user_id', $empleadoId)
                       ->where('anio', $anio)
                       ->first();

        $empleado = User::find($empleadoId);

        if (!$empleado || !$empleado->es_empleado) {
            return null;
        }

        // Calcular antigüedad del empleado
        $fechaContratacion = $empleado->fecha_contratacion;
        if (!$fechaContratacion) {
            return null;
        }

        $fechaCalculo = now();
        $aniosAntiguedad = $fechaCalculo->diffInYears($fechaContratacion);

        // Calcular días correspondientes según antigüedad
        $diasCorrespondientes = self::calcularDiasSegunAntiguedad($aniosAntiguedad);

        // Si el empleado tiene menos de 1 año, no le corresponden días pero igual creamos registro
        if ($aniosAntiguedad < 1) {
            $diasCorrespondientes = 0;
        }

        // Calcular días utilizados a partir de vacaciones aprobadas del año
        $diasUtilizados = \App\Models\Vacacion::where('user_id', $empleadoId)
            ->where('estado', 'aprobada')
            ->where(function($query) use ($anio) {
                $query->whereYear('fecha_inicio', $anio)
                      ->orWhereYear('fecha_fin', $anio);
            })
            ->sum('dias_solicitados');

        if ($registro) {
            // Error #5 Fix: Recalcular dias_disponibles correctamente
            // Buscar días acumulados del año anterior si no están ya incluidos
            $registroAnterior = self::where('user_id', $empleadoId)
                                   ->where('anio', $anio - 1)
                                   ->first();
            $diasAcumulados = $registroAnterior ? $registroAnterior->dias_acumulados_siguiente : 0;

            // Solo actualizar dias_disponibles si los dias_correspondientes cambiaron
            $nuevosDisponibles = $diasCorrespondientes + $diasAcumulados;

            $registro->update([
                'dias_correspondientes' => $diasCorrespondientes,
                'dias_disponibles' => $nuevosDisponibles,
                'dias_utilizados' => $diasUtilizados,
                'dias_pendientes' => max(0, $nuevosDisponibles - $diasUtilizados),
                'fecha_calculo' => $fechaCalculo,
            ]);
        } else {
            // Buscar días acumulados del año anterior
            $registroAnterior = self::where('user_id', $empleadoId)
                                   ->where('anio', $anio - 1)
                                   ->first();

            $diasAcumulados = $registroAnterior ? $registroAnterior->dias_acumulados_siguiente : 0;

            $diasDisponibles = $diasCorrespondientes + $diasAcumulados;

            // Crear nuevo registro
            $registro = self::create([
                'user_id' => $empleadoId,
                'anio' => $anio,
                'dias_correspondientes' => $diasCorrespondientes,
                'dias_disponibles' => $diasDisponibles,
                'dias_utilizados' => $diasUtilizados,
                'dias_pendientes' => max(0, $diasDisponibles - $diasUtilizados),
                'dias_acumulados_siguiente' => 0,
                'fecha_calculo' => $fechaCalculo,
            ]);
        }

        return $registro;
    }

    /**
     * Acumular días no utilizados para el siguiente año
     */
    public function acumularDiasSiguienteAnio()
    {
        // Los días no utilizados se acumulan para el siguiente año
        // Según la ley mexicana, se pueden acumular hasta 2 años de vacaciones
        $diasParaAcumular = min($this->dias_pendientes, $this->dias_correspondientes);

        $this->update([
            'dias_acumulados_siguiente' => $diasParaAcumular,
        ]);

        return $diasParaAcumular;
    }

    /**
     * Registrar uso de días de vacaciones
     */
    public function registrarUsoVacaciones($diasUtilizados)
    {
        $nuevosDiasUtilizados = $this->dias_utilizados + $diasUtilizados;
        $nuevosDiasPendientes = $this->dias_disponibles - $nuevosDiasUtilizados;

        $this->update([
            'dias_utilizados' => $nuevosDiasUtilizados,
            'dias_pendientes' => max(0, $nuevosDiasPendientes),
        ]);

        return [
            'dias_utilizados' => $nuevosDiasUtilizados,
            'dias_pendientes' => max(0, $nuevosDiasPendientes),
        ];
    }

    /**
     * Revertir uso de días de vacaciones (para cancelaciones).
     * Error #3 Fix: Permite devolver días cuando se cancela una vacación aprobada.
     *
     * @param int $diasARevertir
     * @return array
     */
    public function revertirUsoVacaciones($diasARevertir)
    {
        $nuevosDiasUtilizados = max(0, $this->dias_utilizados - $diasARevertir);
        $nuevosDiasPendientes = $this->dias_disponibles - $nuevosDiasUtilizados;

        $this->update([
            'dias_utilizados' => $nuevosDiasUtilizados,
            'dias_pendientes' => max(0, $nuevosDiasPendientes),
        ]);

        return [
            'dias_utilizados' => $nuevosDiasUtilizados,
            'dias_pendientes' => max(0, $nuevosDiasPendientes),
            'dias_revertidos' => $diasARevertir,
        ];
    }

    /**
     * Verificar si tiene suficientes días disponibles
     */
    public function tieneDiasDisponibles($diasSolicitados)
    {
        return $this->dias_pendientes >= $diasSolicitados;
    }

    // Scopes
    public function scopeDelEmpleado($query, $empleadoId)
    {
        return $query->where('user_id', $empleadoId);
    }

    public function scopeDelAnio($query, $anio)
    {
        return $query->where('anio', $anio);
    }

    public function scopeConDiasPendientes($query)
    {
        return $query->where('dias_pendientes', '>', 0);
    }

    /**
     * Aplicar un ajuste manual de días por un administrador.
     * Error #8 Fix: Valida que el ajuste no provoque inconsistencias.
     *
     * @param int $dias Días a ajustar (positivo o negativo)
     * @param string|null $motivo Motivo del ajuste
     * @param int $adminId ID del administrador que realiza el ajuste
     * @throws \Exception Si el ajuste provoca inconsistencias
     * @return static
     */
    public function aplicarAjuste(int $dias, ?string $motivo, int $adminId)
    {
        $dias = (int) $dias;
        if ($dias === 0) {
            return $this;
        }

        $nuevosDisponibles = ($this->dias_disponibles ?? 0) + $dias;
        $nuevosPendientes = ($this->dias_pendientes ?? 0) + $dias;

        // Error #8 Fix: Validar que no se cree inconsistencia
        if ($nuevosDisponibles < $this->dias_utilizados) {
            throw new \Exception(
                "No se puede reducir días disponibles por debajo de los ya utilizados. " .
                "Días utilizados: {$this->dias_utilizados}, Nuevo total disponible: {$nuevosDisponibles}"
            );
        }

        // Asegurar que los valores no sean negativos
        $nuevosDisponibles = max(0, $nuevosDisponibles);
        $nuevosPendientes = max(0, $nuevosPendientes);

        $this->update([
            'dias_disponibles' => $nuevosDisponibles,
            'dias_pendientes' => $nuevosPendientes,
        ]);

        \App\Models\AjusteVacaciones::create([
            'user_id' => $this->user_id,
            'anio' => $this->anio,
            'dias' => $dias,
            'motivo' => $motivo,
            'creado_por' => $adminId,
        ]);

        return $this->fresh();
    }
}
