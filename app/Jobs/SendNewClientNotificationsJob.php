<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNewClientNotificationsJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Cliente $cliente)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendNewClientNotificationsJob - Iniciando envío de notificaciones para cliente', [
            'cliente_id' => $this->cliente->id,
            'nombre' => $this->cliente->nombre_razon_social
        ]);

        try {
            $users = User::all();

            foreach ($users as $user) {
                UserNotification::createForUser(
                    $user->id,
                    'new_client',
                    'Nuevo Cliente Registrado',
                    "Se ha registrado el cliente: {$this->cliente->nombre_razon_social}",
                    [
                        'client_id' => $this->cliente->id,
                        'client_name' => $this->cliente->nombre_razon_social,
                        'client_email' => $this->cliente->email,
                        'created_at' => $this->cliente->created_at->toIso8601String()
                    ],
                    "/clientes/{$this->cliente->id}",
                    'fas fa-user-plus'
                );
            }

            Log::info('SendNewClientNotificationsJob - Notificaciones enviadas exitosamente', [
                'total_usuarios' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('SendNewClientNotificationsJob - Error enviando notificaciones', [
                'cliente_id' => $this->cliente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
