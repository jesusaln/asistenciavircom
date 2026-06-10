<?php

namespace App\Jobs;

use App\Models\Cita;
use App\Models\User;
use App\Services\Microsoft\GraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCitaToMicrosoftToDo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cita;
    protected $isDeletion;
    protected $oldTecnicoId;
    protected $oldTaskId;
    protected $oldListId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        Cita $cita, 
        bool $isDeletion = false, 
        ?int $oldTecnicoId = null, 
        ?string $oldTaskId = null,
        ?string $oldListId = null
    ) {
        $this->cita = $cita;
        $this->isDeletion = $isDeletion;
        $this->oldTecnicoId = $oldTecnicoId;
        $this->oldTaskId = $oldTaskId;
        $this->oldListId = $oldListId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->isDeletion || $this->oldTecnicoId) {
            $this->handleDeletion();
            if ($this->isDeletion) return;
        }

        $this->handleSync();
    }

    protected function handleSync(): void
    {
        $cita = $this->cita->fresh(['tecnico', 'cliente']);

        if (!$cita || !$cita->tecnico || !$cita->tecnico->microsoft_token) {
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

            $title = "Cita #{$cita->folio}: " . strip_tags($cita->cliente?->nombre_razon_social ?? $cita->cliente?->nombre ?? 'Cliente');
            $content = "Servicio: " . strip_tags($cita->tipo_servicio ?? 'N/A') . "\n" .
                "Teléfono: " . strip_tags($cita->cliente?->telefono ?? 'N/A') . "\n" .
                "Dirección: " . strip_tags($cita->direccion_completa ?? $cita->cliente?->direccion ?? 'N/A') . "\n" .
                "Problema: " . strip_tags($cita->problema_reportado);

            if ($cita->microsoft_task_id && !$this->oldTecnicoId) {
                // Update existing task (same technician)
                $graph->patch("/me/todo/lists/{$listId}/tasks/{$cita->microsoft_task_id}", [
                    'title' => $title,
                    'body' => ['content' => $content, 'contentType' => 'text'],
                    'dueDateTime' => [
                        'dateTime' => $cita->fecha_hora->setTimezone('UTC')->format('Y-m-d\TH:i:s'),
                        'timeZone' => 'UTC',
                    ]
                ]);
            } else {
                // Create new task (new technician or reassigned)
                $task = $graph->createTask(
                    $listId,
                    $title,
                    $content,
                    $cita->fecha_hora
                )->json();

                // Guardar IDs sin disparar eventos de nuevo
                $cita->withoutEvents(function () use ($cita, $task, $listId) {
                    $cita->update([
                        'microsoft_task_id' => $task['id'],
                        'microsoft_list_id' => $listId
                    ]);
                });
            }

        } catch (\Exception $e) {
            Log::error('Error syncing to Microsoft To Do in Job: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function handleDeletion(): void
    {
        $tecnicoId = $this->oldTecnicoId ?? $this->cita->tecnico_id;
        $taskId = $this->oldTaskId ?? $this->cita->microsoft_task_id;
        $listId = $this->oldListId ?? $this->cita->microsoft_list_id;

        if (!$tecnicoId || !$taskId || !$listId) return;

        $tecnico = User::find($tecnicoId);
        if (!$tecnico || !$tecnico->microsoft_token) return;

        try {
            $graph = new GraphService($tecnico);
            $graph->delete("/me/todo/lists/{$listId}/tasks/{$taskId}");
            Log::info("Tarea Microsoft To Do eliminada para cita #{$this->cita->folio} (Técnico ID: {$tecnicoId})");
        } catch (\Exception $e) {
            if (!str_contains($e->getMessage(), '404')) {
                Log::error('Error deleting Microsoft To Do task in Job: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
