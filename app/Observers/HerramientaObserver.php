<?php

namespace App\Observers;

use App\Models\Herramienta;
use App\Models\HistorialHerramienta;
use Illuminate\Support\Facades\Auth;

class HerramientaObserver
{
    /**
     * Handle the Herramienta "updating" event.
     * Sincroniza automáticamente tecnico_id <-> estado
     */
    public function updating(Herramienta $herramienta)
    {
        // Detectar si cambió tecnico_id
        if ($herramienta->isDirty('tecnico_id')) {
            $this->sincronizarEstadoPorTecnico($herramienta);
        }

        // Detectar si cambió estado
        if ($herramienta->isDirty('estado')) {
            $this->sincronizarTecnicoPorEstado($herramienta);
        }
    }

    /**
     * Handle the Herramienta "saved" event.
     * Usar 'saved' en lugar de 'updated' garantiza que se ejecute DESPUÉS
     * de que todos los cambios (incluyendo los del Observer) se hayan aplicado
     */
    public function saved(Herramienta $herramienta)
    {
        // Verificar si cambió tecnico_id comparando con el original
        $tecnicoOriginal = $herramienta->getOriginal('tecnico_id');
        $tecnicoActual = $herramienta->tecnico_id;
        
        // Solo registrar si realmente cambió el técnico
        if ($tecnicoOriginal != $tecnicoActual) {
            $this->registrarEnHistorial($herramienta, $tecnicoOriginal, $tecnicoActual);
        }
    }

    /**
     * Sincronizar estado cuando cambia tecnico_id
     */
    protected function sincronizarEstadoPorTecnico(Herramienta $herramienta)
    {
        $tecnicoAnterior = $herramienta->getOriginal('tecnico_id');
        $tecnicoNuevo = $herramienta->tecnico_id;

        // Si se asigna un técnico (NULL -> ID)
        if (empty($tecnicoAnterior) && !empty($tecnicoNuevo)) {
            $herramienta->estado = Herramienta::ESTADO_ASIGNADA;
            $herramienta->fecha_asignacion = now();
        }

        // Si se libera el técnico (ID -> NULL)
        if (!empty($tecnicoAnterior) && empty($tecnicoNuevo)) {
            $herramienta->estado = Herramienta::ESTADO_DISPONIBLE;
            $herramienta->fecha_recepcion = now();
        }

        // Si se reasigna (ID -> ID diferente)
        if (!empty($tecnicoAnterior) && !empty($tecnicoNuevo) && $tecnicoAnterior !== $tecnicoNuevo) {
            $herramienta->estado = Herramienta::ESTADO_ASIGNADA;
            $herramienta->fecha_asignacion = now();
        }
    }

    /**
     * Sincronizar tecnico_id cuando cambia estado
     */
    protected function sincronizarTecnicoPorEstado(Herramienta $herramienta)
    {
        $estadoNuevo = $herramienta->estado;

        // Si cambia a disponible, liberar técnico
        if ($estadoNuevo === Herramienta::ESTADO_DISPONIBLE) {
            if (!empty($herramienta->tecnico_id)) {
                $herramienta->tecnico_id = null;
                $herramienta->fecha_recepcion = now();
            }
        }

        // Si cambia a asignada, validar que tenga técnico
        if ($estadoNuevo === Herramienta::ESTADO_ASIGNADA) {
            if (empty($herramienta->tecnico_id)) {
                // Prevenir cambio de estado sin técnico
                throw new \Exception('No se puede cambiar el estado a "asignada" sin un técnico asignado');
            }
        }

        // Estados mantenimiento, baja, perdida pueden o no tener técnico
        // No forzamos liberación para mantener trazabilidad
    }

    /**
     * Registrar cambio de asignación en historial
     * Mejorado para garantizar que siempre se cree el historial
     */
    protected function registrarEnHistorial(Herramienta $herramienta, $tecnicoAnterior, $tecnicoNuevo)
    {
        // Devolución (técnico anterior devuelve la herramienta)
        if (!empty($tecnicoAnterior) && empty($tecnicoNuevo)) {
            HistorialHerramienta::create([
                'herramienta_id' => $herramienta->id,
                'tecnico_id' => $tecnicoAnterior,
                'fecha_asignacion' => $herramienta->getOriginal('fecha_asignacion') ?? $herramienta->getOriginal('updated_at'),
                'fecha_devolucion' => now(),
                'asignado_por' => null, // No sabemos quién asignó originalmente
                'recibido_por' => Auth::id(),
                'observaciones_devolucion' => 'Liberación de herramienta',
                'estado_herramienta_devolucion' => $herramienta->estado,
                'duracion_dias' => null, // Se calculará automáticamente por accessor
            ]);
        }

        // Asignación nueva (de disponible a técnico)
        if (empty($tecnicoAnterior) && !empty($tecnicoNuevo)) {
            HistorialHerramienta::create([
                'herramienta_id' => $herramienta->id,
                'tecnico_id' => $tecnicoNuevo,
                'fecha_asignacion' => now(),
                'fecha_devolucion' => null,
                'asignado_por' => Auth::id(),
                'recibido_por' => null,
                'observaciones_asignacion' => 'Asignación de herramienta',
                'estado_herramienta_asignacion' => $herramienta->estado,
                'duracion_dias' => null,
            ]);
        }

        // Reasignación (de un técnico a otro)
        if (!empty($tecnicoAnterior) && !empty($tecnicoNuevo) && $tecnicoAnterior !== $tecnicoNuevo) {
            // Registro de devolución del técnico anterior
            HistorialHerramienta::create([
                'herramienta_id' => $herramienta->id,
                'tecnico_id' => $tecnicoAnterior,
                'fecha_asignacion' => $herramienta->getOriginal('fecha_asignacion') ?? $herramienta->getOriginal('updated_at'),
                'fecha_devolucion' => now(),
                'asignado_por' => null,
                'recibido_por' => Auth::id(),
                'observaciones_devolucion' => "Reasignación a técnico ID: {$tecnicoNuevo}",
                'estado_herramienta_devolucion' => $herramienta->estado,
                'duracion_dias' => null,
            ]);

            // Registro de asignación al técnico nuevo
            HistorialHerramienta::create([
                'herramienta_id' => $herramienta->id,
                'tecnico_id' => $tecnicoNuevo,
                'fecha_asignacion' => now(),
                'fecha_devolucion' => null,
                'asignado_por' => Auth::id(),
                'recibido_por' => null,
                'observaciones_asignacion' => "Reasignación desde técnico ID: {$tecnicoAnterior}",
                'estado_herramienta_asignacion' => $herramienta->estado,
                'duracion_dias' => null,
            ]);
        }
    }
}
