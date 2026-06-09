<?php

namespace App\Observers;

use App\Models\Cita;
use App\Jobs\SyncCitaToMicrosoftToDo;
use Illuminate\Support\Facades\Log;

class CitaObserver
{
    /**
     * Handle the Cita "created" event.
     */
    public function created(Cita $cita): void
    {
        SyncCitaToMicrosoftToDo::dispatch($cita);
    }

    /**
     * Handle the Cita "updated" event.
     */
    public function updated(Cita $cita): void
    {
        // Si cambió el técnico, necesitamos borrar la tarea del técnico anterior
        if ($cita->wasChanged('tecnico_id')) {
            $oldTecnicoId = $cita->getOriginal('tecnico_id');
            $oldTaskId = $cita->getOriginal('microsoft_task_id');
            $oldListId = $cita->getOriginal('microsoft_list_id');

            SyncCitaToMicrosoftToDo::dispatch(
                $cita, 
                false, // No es borrado total, es reasignación
                $oldTecnicoId, 
                $oldTaskId, 
                $oldListId
            );
            return;
        }

        // Solo sincronizar si cambiaron campos relevantes para la tarea
        $relevantFields = [
            'cliente_id', 'fecha_hora', 'descripcion',
            'problema_reportado', 'tipo_servicio', 'estado', 'folio',
        ];

        if ($cita->wasChanged($relevantFields)) {
            SyncCitaToMicrosoftToDo::dispatch($cita);
        }
    }

    /**
     * Handle the Cita "deleted" event.
     */
    public function deleted(Cita $cita): void
    {
        SyncCitaToMicrosoftToDo::dispatch($cita, true);
    }
}
