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
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected $appends = ['read'];

    public function getReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

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
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'icon' => $icon,
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

    public static function createCreditSignatureNotification($cliente): void
    {
        // Notificar a todos los usuarios con rol admin o super-admin
        $admins = User::role(['admin', 'super-admin'])->get();

        foreach ($admins as $admin) {
            static::createForUser(
                $admin->id,
                'credit_signature',
                'Solicitud de Crédito Firmada',
                "El cliente {$cliente->nombre_razon_social} ha firmado digitalmente su solicitud de crédito.",
                [
                    'client_id' => $cliente->id,
                    'client_name' => $cliente->nombre_razon_social,
                    'limite_solicitado' => $cliente->credito_solicitado_monto,
                    'dias_solicitados' => $cliente->credito_solicitado_dias,
                ],
                "/clientes/{$cliente->id}",
                'fas fa-signature'
            );
        }
    }

    public static function createRentaSignatureNotification($renta): void
    {
        $admins = User::role(['admin', 'super-admin'])->get();
        $cliente = $renta->cliente;

        foreach ($admins as $admin) {
            static::createForUser(
                $admin->id,
                'renta_firmada',
                'Contrato de Renta Firmado',
                "El cliente {$cliente->nombre_razon_social} ha firmado el contrato de renta {$renta->numero_contrato}.",
                [
                    'renta_id' => $renta->id,
                    'cliente_id' => $cliente->id,
                    'numero_contrato' => $renta->numero_contrato,
                ],
                "/rentas/{$renta->id}",
                'fas fa-file-signature'
            );
        }
    }
}
