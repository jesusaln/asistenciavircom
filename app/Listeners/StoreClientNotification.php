<?php

namespace App\Listeners;

use App\Events\ClientCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class StoreClientNotification
{
    public function handle(ClientCreated $event)
    {
        Log::info('StoreClientNotification: Iniciando procesamiento de evento ClientCreated', [
            'cliente_id' => $event->cliente->id,
            'cliente_nombre' => $event->cliente->nombre_razon_social
        ]);

        try {
            // Crear notificación para todos los usuarios
            // En producción, podrías filtrar por usuarios con permisos específicos
            $users = \App\Models\User::all();

            Log::info('StoreClientNotification: Encontrados ' . $users->count() . ' usuarios');

            $users->each(function ($user) use ($event) {
                Log::info('StoreClientNotification: Creando notificación para usuario', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);

                $notification = \App\Models\Notification::createForUser(
                    $user->id,
                    'new_client',
                    'Nuevo Cliente',
                    "Se registro a: {$event->cliente->nombre_razon_social}",
                    [
                        'client_id' => $event->cliente->id,
                        'client_name' => $event->cliente->nombre_razon_social,
                        'client_email' => $event->cliente->email,
                        'created_at' => $event->cliente->created_at->toISOString()
                    ],
                    "/clientes/{$event->cliente->id}",
                    'fas fa-user-plus'
                );

                Log::info('StoreClientNotification: Notificación creada exitosamente', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id
                ]);
            });

            Log::info('StoreClientNotification: Procesamiento completado exitosamente');

        } catch (\Exception $e) {
            Log::error('StoreClientNotification: Error procesando evento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cliente_id' => $event->cliente->id
            ]);
            throw $e;
        }
    }
}
