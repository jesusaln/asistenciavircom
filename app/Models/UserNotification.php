<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserNotification extends Model
{
    use SoftDeletes;

    public const VISIBLE_DAYS_IN_BELL = 3;

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

    /**
     * Notificaciones visibles en la campana (ventana reciente).
     */
    public function scopeVisibleInBell(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subDays(self::VISIBLE_DAYS_IN_BELL));
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
        $notification = static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'icon' => $icon,
        ]);

        try {
            event(new \App\Events\UserNotificationCreated($notification));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error broadcasting UserNotificationCreated: ' . $e->getMessage());
        }

        return $notification;
    }

    public static function createClientNotification($cliente): void
    {
        try {
            dispatch(new \App\Jobs\SendNewClientNotificationsJob($cliente));
            \Illuminate\Support\Facades\Log::info('SendNewClientNotificationsJob despachado desde UserNotification', ['cliente_id' => $cliente->id]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error despachando SendNewClientNotificationsJob', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage()
            ]);
            // Fallback síncrono si falla el dispatch
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
                        'created_at' => $cliente->created_at->toIso8601String()
                    ],
                    "/clientes/{$cliente->id}",
                    'fas fa-user-plus'
                );
            }
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

    public static function createSolicitudMaterialNotification($solicitud): void
    {
        // Notificar al personal de compras de la misma empresa
        $users = User::role(['compras'])
            ->where('empresa_id', $solicitud->empresa_id)
            ->get();

        foreach ($users as $user) {
            static::createForUser(
                $user->id,
                'solicitud_material',
                'Nueva Solicitud de Material',
                "El técnico {$solicitud->user->name} ha solicitado material (Folio: {$solicitud->folio})",
                [
                    'solicitud_id' => $solicitud->id,
                    'folio' => $solicitud->folio,
                    'user_name' => $solicitud->user->name,
                ],
                "/admin/solicitudes-material",
                'fas fa-clipboard-list'
            );
        }
    }

    public static function createTallerNotification($orden, $tipo = 'proxima_entrega'): void
    {
        $admins = User::role(['admin', 'super-admin'])
            ->where('empresa_id', $orden->empresa_id)
            ->get();

        $title = $tipo === 'proxima_entrega' ? 'Próxima Entrega de Taller' : 'Entrega Atrasada de Taller';
        $icon = $tipo === 'proxima_entrega' ? 'fas fa-clock' : 'fas fa-exclamation-triangle';
        $message = "La orden {$orden->folio} ({$orden->equipo_marca}) debe entregarse el {$orden->fecha_compromiso->format('d/m/Y')}";

        foreach ($admins as $admin) {
            // Evitar duplicados recientes
            $exists = static::where('user_id', $admin->id)
                ->where('type', 'taller_alert')
                ->where('data->orden_id', $orden->id)
                ->where('created_at', '>', now()->subDay())
                ->exists();

            if (!$exists) {
                static::createForUser(
                    $admin->id,
                    'taller_alert',
                    $title,
                    $message,
                    [
                        'orden_id' => $orden->id,
                        'folio' => $orden->folio,
                        'tipo' => $tipo
                    ],
                    "/taller/{$orden->id}",
                    $icon
                );
            }
        }
    }

    public static function createNom035Notification($empresaId, $type, $title, $message, $actionUrl = "/nom035"): void
    {
        $admins = User::role(['admin', 'super-admin'])
            ->where('empresa_id', $empresaId)
            ->get();

        foreach ($admins as $admin) {
            // Evitar duplicados recientes (últimas 24 horas para el mismo tipo de alerta)
            $exists = static::where('user_id', $admin->id)
                ->where('type', 'nom035_alert')
                ->where('data->alert_type', $type)
                ->where('created_at', '>', now()->subDay())
                ->exists();

            if (!$exists) {
                static::createForUser(
                    $admin->id,
                    'nom035_alert',
                    $title,
                    $message,
                    [
                        'alert_type' => $type,
                        'empresa_id' => $empresaId
                    ],
                    $actionUrl,
                    'fas fa-shield-halved'
                );
            }
        }
    }

    public static function createFacturaSolicitudNotification($cliente, $venta): void
    {
        $admins = User::role(['admin', 'super-admin'])
            ->where('empresa_id', $venta->empresa_id)
            ->get();

        if ($admins->isEmpty()) {
            $admins = User::where('empresa_id', $venta->empresa_id)->get();
        }

        foreach ($admins as $admin) {
            static::createForUser(
                $admin->id,
                'solicitud_factura',
                'Solicitud de Factura Recibida',
                "El cliente {$cliente->nombre_razon_social} solicita facturar el ticket {$venta->numero_venta}.",
                [
                    'client_id' => $cliente->id,
                    'client_name' => $cliente->nombre_razon_social,
                    'venta_id' => $venta->id,
                    'numero_venta' => $venta->numero_venta,
                    'rfc' => $cliente->rfc
                ],
                "/ventas/{$venta->id}",
                'fas fa-file-invoice-dollar'
            );
        }
    }
}

