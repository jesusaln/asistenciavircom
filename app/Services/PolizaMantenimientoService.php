<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\PolizaMantenimiento;
use App\Models\PolizaMantenimientoEjecucion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PolizaMantenimientoService
{
    /**
     * Generar mantenimientos programados para todas las pólizas activas.
     */
    public function generarMantenimientos(): int
    {
        $polizas = PolizaServicio::activa()->get();
        $generados = 0;

        foreach ($polizas as $poliza) {
            $mantenimientos = PolizaMantenimiento::where('poliza_id', $poliza->id)
                ->where('activo', true)
                ->get();

            foreach ($mantenimientos as $mant) {
                if ($this->debeGenerarse($mant)) {
                    $this->crearEjecucion($mant);
                    $generados++;
                }
            }
        }

        return $generados;
    }

    /**
     * Determina si se debe generar una nueva ejecución basado en la frecuencia.
     */
    /**
     * Determina si se debe generar una nueva ejecución basado en la frecuencia.
     * @param PolizaMantenimiento|mixed $mant
     */
    protected function debeGenerarse($mant): bool
    {
        if (!$mant instanceof PolizaMantenimiento) {
            Log::warning('PolizaMantenimientoService: Objeto inválido paso a debeGenerarse', ['type' => gettype($mant)]);
            return false;
        }

        // Si no se ha generado nunca, adelante
        if (!$mant->ultima_generacion_at) {
            return true;
        }

        $ultima = $mant->ultima_generacion_at; // Asumimos que es Carbon por el cast del modelo
        $hoy = now();

        return match ($mant->frecuencia) {
            'diario' => $ultima->diffInDays($hoy) >= 1,
            'semanal' => $ultima->diffInWeeks($hoy) >= 1,
            'quincenal' => $ultima->diffInDays($hoy) >= 15,
            'mensual' => $ultima->diffInMonths($hoy) >= 1,
            'bimestral' => $ultima->diffInMonths($hoy) >= 2,
            'trimestral' => $ultima->diffInMonths($hoy) >= 3,
            'semestral' => $ultima->diffInMonths($hoy) >= 6,
            default => false,
        };
    }

    /**
     * Crea la instancia de ejecución programada.
     * @param PolizaMantenimiento|mixed $mant
     */
    protected function crearEjecucion($mant): PolizaMantenimientoEjecucion
    {
        if (!$mant instanceof PolizaMantenimiento) {
            throw new \InvalidArgumentException('Argumento debe ser una instancia de PolizaMantenimiento');
        }
        // Calcular fecha sugerida
        $fecha = $this->calcularFechaProgramada($mant);

        // Transformar checklist de strings a objetos con estado
        $checklistBase = $mant->checklist ?? [];
        $checklistConEstado = collect($checklistBase)->map(function ($item) {
            return ['label' => is_string($item) ? $item : ($item['label'] ?? 'Tarea'), 'checked' => false];
        })->toArray();

        $ejecucion = PolizaMantenimientoEjecucion::create([
            'mantenimiento_id' => $mant->id,
            'tecnico_id' => null, // Se asignará manual o al responsable predeterminado
            'fecha_programada' => $fecha,
            'fecha_original' => $fecha,
            'estado' => 'pendiente',
            'checklist' => $checklistConEstado,
        ]);

        $mant->update(['ultima_generacion_at' => now()]);

        Log::info("Mantenimiento generado: {$mant->nombre} para Póliza #{$mant->poliza_id}");

        return $ejecucion;
    }

    /**
     * Calcula la fecha exacta basada en el día preferido.
     */
    protected function calcularFechaProgramada(PolizaMantenimiento $mant): Carbon
    {
        $hoy = now();
        $target = $hoy->copy();

        // Ajustar al día preferido si es mensual o mayor
        if (in_array($mant->frecuencia, ['mensual', 'bimestral', 'trimestral', 'semestral'])) {
            // Ir al mes correspondiente
            // Fijar día
            $dia = min($mant->dia_preferido, $target->daysInMonth);
            $target->day($dia);

            // Si ya pasó este mes, mover al siguiente ciclo
            if ($target->isPast()) {
                $target = match ($mant->frecuencia) {
                    'mensual' => $target->addMonth(),
                    'bimestral' => $target->addMonths(2),
                    'trimestral' => $target->addMonths(3),
                    'semestral' => $target->addMonths(6),
                    default => $target->addMonth(),
                };
                // Recalcular día por si cambia longitud de mes
                $dia = min($mant->dia_preferido, $target->daysInMonth);
                $target->day($dia);
            }
        } else {
            // Semanal, quincenal: simplemente sumar desde la última vez o hoy
            // Para simplificar MVP, si es nuevo, programar para mañana a las 9am
            $target = $hoy->addDay()->setTime(9, 0, 0);
        }

        return $target;
    }

    /**
     * Reprogramar una ejecución existente.
     */
    public function reprogramar(PolizaMantenimientoEjecucion $ejecucion, string $nuevaFechaStr, string $motivo, $user = null): void
    {
        $nuevaFecha = Carbon::parse($nuevaFechaStr);

        $ejecucion->reprogramar($nuevaFecha, $motivo, $user);

        Log::info("Mantenimiento reprogramado: ID {$ejecucion->id} a {$nuevaFecha}");
    }

    /**
     * Permite al cliente solicitar/agendar un mantenimiento incluido manualmente.
     */
    public function solicitarMantenimientoManual(PolizaMantenimiento $mant, string $fechaSolicitadaStr, string $notasCliente): PolizaMantenimientoEjecucion
    {
        $fecha = Carbon::parse($fechaSolicitadaStr);

        // Crear la ejecución agendada por el cliente
        $ejecucion = PolizaMantenimientoEjecucion::create([
            'mantenimiento_id' => $mant->id,
            'tecnico_id' => null, // Se asignará después
            'fecha_programada' => $fecha,
            'fecha_original' => $fecha,
            'estado' => 'pendiente',
            'notas_reprogramacion' => "Solicitado por cliente para: {$fecha->format('d/m/Y H:i')}",
            'notas_tecnico' => "Nota del cliente: " . $notasCliente,
        ]);

        // Actualizar última generación para no duplicar en el automático si es reciente
        $mant->update(['ultima_generacion_at' => now()]);

        Log::info("Mantenimiento solicitado por cliente: {$mant->nombre} para {$fecha}");

        return $ejecucion;
    }
}
