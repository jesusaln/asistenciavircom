<?php

namespace App\Observers;

use App\Models\Cita;
use App\Services\Microsoft\GraphService;
use Illuminate\Support\Facades\Log;

class CitaObserver
{
    /**
     * Handle the Cita "created" event.
     */
    public function created(Cita $cita): void
    {
        $this->syncToMicrosoftToDo($cita);
    }

    /**
     * Handle the Cita "updated" event.
     */
    public function updated(Cita $cita): void
    {
        // Si cambió el técnico, quizás borrar del anterior y crear en el nuevo?
        // Por simplificación, actualizamos si es el mismo, o creamos si no existía.
        $this->syncToMicrosoftToDo($cita);
    }

    /**
     * Handle the Cita "deleted" event.
     */
    public function deleted(Cita $cita): void
    {
        if ($cita->microsoft_task_id && $cita->tecnico && $cita->tecnico->microsoft_token) {
            try {
                $graph = new GraphService($cita->tecnico);
                // La API de Graph para borrar tareas es DELETE /me/todo/lists/{listId}/tasks/{taskId}
                // Necesitamos el listId. Por defecto usaremos la lista 'Tasks' o buscaremos una llamada 'Citas'.
                // Por ahora, para simplificar y evitar complejidad de búsqueda de lista, 
                // podríamos intentar borrar si guardáramos el list_id, pero solo guardamos task_id.
                // TODO: Implementar borrado si es crítico.
            } catch (\Exception $e) {
                Log::error('Error deleting Microsoft To Do task: ' . $e->getMessage());
            }
        }
    }

    protected function syncToMicrosoftToDo(Cita $cita)
    {
        if (!$cita->tecnico || !$cita->tecnico->microsoft_token) {
            return;
        }

        try {
            $graph = new GraphService($cita->tecnico);

            // Buscar o crear lista "Citas Vircom"
            $lists = $graph->getTaskLists()->json();
            $listId = null;

            foreach ($lists['value'] ?? [] as $list) {
                if ($list['displayName'] === 'Citas Vircom') {
                    $listId = $list['id'];
                    break;
                }
            }

            if (!$listId) {
                $newList = $graph->createTaskList('Citas Vircom')->json();
                $listId = $newList['id'];
            }

            $title = "Cita #{$cita->folio}: " . ($cita->cliente?->nombre_completo ?? 'Cliente');
            $content = "Servicio: " . ($cita->tipo_servicio ?? 'N/A') . "\n" .
                "Teléfono: " . ($cita->cliente?->telefono ?? 'N/A') . "\n" .
                "Dirección: " . ($cita->direccion_completa ?? $cita->cliente?->direccion ?? 'N/A') . "\n" .
                "Problema: " . $cita->problema_reportado;

            if ($cita->microsoft_task_id) {
                // Update existing task
                $graph->patch("/me/todo/lists/{$listId}/tasks/{$cita->microsoft_task_id}", [
                    'title' => $title,
                    'body' => ['content' => $content, 'contentType' => 'text'],
                    'dueDateTime' => [
                        'dateTime' => $cita->fecha_hora->setTimezone('UTC')->format('Y-m-d\TH:i:s'),
                        'timeZone' => 'UTC',
                    ]
                ]);
            } else {
                // Create new task
                $task = $graph->createTask(
                    $listId,
                    $title,
                    $content,
                    $cita->fecha_hora
                )->json();

                // Guardar ID sin disparar eventos de nuevo
                $cita->withoutEvents(function () use ($cita, $task) {
                    $cita->update(['microsoft_task_id' => $task['id']]);
                });
            }

        } catch (\Exception $e) {
            Log::error('Error syncing to Microsoft To Do: ' . $e->getMessage());
        }
    }
}
