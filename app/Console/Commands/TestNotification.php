<?php

namespace App\Console\Commands;

use App\Helpers\EnvironmentHelper;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Notification;
use App\Models\Cliente;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:notification {--type= : Tipo de notificación a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear notificaciones de prueba para verificar el sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Prevenir ejecución en producción
        EnvironmentHelper::preventDevelopmentAction('el comando de prueba de notificaciones');

        $type = $this->option('type') ?: 'test';

        $this->info('=== Verificando Sistema de Notificaciones ===');

        // Verificar usuarios
        $users = User::all();
        $this->info("Usuarios encontrados: {$users->count()}");

        if ($users->isEmpty()) {
            $this->error('No hay usuarios en la base de datos');
            return;
        }

        // Verificar clientes
        $clientes = Cliente::all();
        $this->info("Clientes encontrados: {$clientes->count()}");

        // Verificar tabla notifications
        try {
            $notificationsCount = Notification::count();
            $this->info("Notificaciones existentes: {$notificationsCount}");
        } catch (\Exception $e) {
            $this->error('Error accediendo a tabla notifications: ' . $e->getMessage());
            return;
        }

        // Crear notificación de prueba
        $this->info('=== Creando Notificación de Prueba ===');

        $user = $users->first();
        $cliente = $clientes->isNotEmpty() ? $clientes->first() : null;

        try {
            if ($type === 'client' && $cliente) {
                $notification = Notification::createForUser(
                    $user->id,
                    'new_client',
                    'Cliente de Prueba',
                    "Se creó el cliente: {$cliente->nombre_razon_social}",
                    [
                        'client_id' => $cliente->id,
                        'client_name' => $cliente->nombre_razon_social,
                        'created_at' => now()->toISOString()
                    ],
                    "/clientes/{$cliente->id}",
                    'fas fa-user-plus'
                );
            } else {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => 'Notificación de Prueba',
                    'message' => 'Esta es una notificación de prueba creada desde el comando.',
                    'data' => ['test' => true, 'timestamp' => now()->toISOString()],
                    'action_url' => '/panel',
                    'icon' => 'fas fa-bell',
                    'read' => false
                ]);
            }

            $this->info("✅ Notificación creada exitosamente!");
            $this->info("ID: {$notification->id}");
            $this->info("Tipo: {$notification->type}");
            $this->info("Título: {$notification->title}");
            $this->info("Usuario: {$user->name} (ID: {$user->id})");

            // Verificar que se creó
            $totalAfter = Notification::count();
            $this->info("Total notificaciones después: {$totalAfter}");

        } catch (\Exception $e) {
            $this->error('❌ Error creando notificación: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
        }

        $this->info('=== Prueba Completada ===');
    }
}
