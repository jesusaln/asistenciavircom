<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Cita;
use App\Services\Microsoft\GraphService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncMicrosoftTasks implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtener usuarios con token de Microsoft
        $users = User::whereNotNull('microsoft_token')->get();

        foreach ($users as $user) {
            $this->syncUserTasks($user);
        }
    }

    protected function syncUserTasks(User $user)
    {
        try {
            $graph = new GraphService($user);

            // 1. Obtener lista "Citas Vircom"
            $lists = $graph->getTaskLists()->json();
            $listId = null;

            foreach ($lists['value'] ?? [] as $list) {
                if ($list['displayName'] === 'Citas Vircom') {
                    $listId = $list['id'];
                    break;
                }
            }

            if (!$listId) {
                // Si no existe lista, no hacemos sync inverso por ahora para no llenar listas default
                return;
            }

            // 2. Obtener tareas de esa lista
            $tasks = $graph->getTasks($listId)->json();

            foreach ($tasks['value'] ?? [] as $task) {
                $microsoftTaskId = $task['id'];

                // Verificar si ya existe la cita
                $cita = Cita::where('microsoft_task_id', $microsoftTaskId)->first();

                if ($cita) {
                    // Update existente si cambió algo (opcional)
                    // Por ahora solo actualizamos si está completada en To Do y no en App
                    if (($task['status'] ?? '') === 'completed' && $cita->estado !== Cita::ESTADO_COMPLETADO) {
                        $cita->cambiarEstado(Cita::ESTADO_COMPLETADO);
                    }
                } else {
                    // CREAR NUEVA CITA DESDE TO DO
                    // Asumimos que es una cita nueva pendiente de agendar o info rápida

                    // Extraer datos básicos
                    $subject = $task['title'] ?? 'Sin título';
                    $body = $task['body']['content'] ?? '';
                    $dueDateTime = $task['dueDateTime']['dateTime'] ?? null; // UTC

                    $fechaHora = $dueDateTime
                        ? \Carbon\Carbon::parse($dueDateTime)->setTimezone(config('app.timezone'))
                        : now();

                    // Intentar parsear cliente del título o body? Muy arriesgado.
                    // Creamos Cita sin cliente (o cliente mostrador/genérico) y asignada al técnico.

                    $cita = new Cita();
                    $cita->folio = app(\App\Services\Folio\FolioService::class)->getNextFolio('cita');
                    $cita->tecnico_id = $user->id;
                    $cita->empresa_id = $user->empresa_id ?? 1; // Fallback
                    $cita->estado = Cita::ESTADO_PENDIENTE;
                    $cita->prioridad = Cita::PRIORIDAD_MEDIA;
                    $cita->descripcion = $subject;
                    $cita->problema_reportado = $body ?: $subject;
                    $cita->fecha_hora = $fechaHora;
                    $cita->microsoft_task_id = $microsoftTaskId;

                    // Guardar sin disparar eventos para evitar bucle (crear en App -> dispara Observer -> crea en To Do)
                    // Pero wait, si ya tiene microsoft_task_id, el Observer hace PATCH, no POST.
                    // Aun así, evitamos la llamada HTTP innecesaria.
                    $cita->saveQuietly();
                }
            }

        } catch (\Exception $e) {
            Log::error("Error syncing tasks for user {$user->id}: " . $e->getMessage());
        }
    }
}
