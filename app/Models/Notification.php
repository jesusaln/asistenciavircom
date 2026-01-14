<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read',
        'read_at',
        'action_url',
        'icon'
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $attributes = [
        'read' => false,
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('read', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Métodos helper
    public function markAsRead()
    {
        if (!$this->read) {
            $this->update([
                'read' => true,
                'read_at' => now()
            ]);
        }
        return $this;
    }

    public function markAsUnread()
    {
        $this->update([
            'read' => false,
            'read_at' => null
        ]);
        return $this;
    }

    // Métodos estáticos para crear notificaciones
    public static function createForUser($userId, $type, $title, $message, $data = [], $actionUrl = null, $icon = null)
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'icon' => $icon,
        ]);
    }

    public static function createClientNotification($cliente)
    {
        Log::info('Notification::createClientNotification - Iniciando', [
            'cliente_id' => $cliente->id,
            'cliente_nombre' => $cliente->nombre_razon_social
        ]);

        try {
            // Crear notificación para todos los usuarios con permisos
            $users = User::all(); // En producción, filtrar por permisos

            Log::info('Notification::createClientNotification - Usuarios encontrados', [
                'count' => $users->count()
            ]);

            foreach ($users as $user) {
                Log::info('Notification::createClientNotification - Creando notificación para usuario', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);

                $notification = static::createForUser(
                    $user->id,
                    'new_client',
                    'Nuevo Cliente Registrado',
                    "Se ha registrado el cliente: {$cliente->nombre_razon_social}",
                    [
                        'client_id' => $cliente->id,
                        'client_name' => $cliente->nombre_razon_social,
                        'client_email' => $cliente->email,
                        'created_at' => $cliente->created_at->toISOString()
                    ],
                    "/clientes/{$cliente->id}",
                    'fas fa-user-plus'
                );

                Log::info('Notification::createClientNotification - Notificación creada', [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id
                ]);
            }

            Log::info('Notification::createClientNotification - Completado exitosamente');

        } catch (\Exception $e) {
            Log::error('Notification::createClientNotification - Error', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
