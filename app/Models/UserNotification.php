<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNotification extends Model
{
    use SoftDeletes;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'action_url',
        'icon',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    protected $attributes = [
        'type' => 'system',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes consistentes con el controlador
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function markAsRead(): bool
    {
        if (is_null($this->read_at)) {
            return $this->forceFill(['read_at' => now()])->save();
        }
        return true;
    }

    public function markAsUnread(): bool
    {
        return $this->forceFill(['read_at' => null])->save();
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    // Métodos estáticos para crear notificaciones
    public static function createForUser(int $userId, string $type, string $title, ?string $message = null, ?array $data = [], ?string $actionUrl = null, ?string $icon = null): static
    {
        return static::create([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'data'       => $data,
            'action_url' => $actionUrl,
            'icon'       => $icon,
        ]);
    }

    public static function createClientNotification($cliente): void
    {
        $users = User::all();

        foreach ($users as $user) {
            static::createForUser(
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
        }
    }
}
