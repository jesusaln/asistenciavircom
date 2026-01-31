<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolizaAuditLog extends Model
{
    use HasFactory;

    protected $table = 'poliza_audit_logs';

    protected $fillable = [
        'poliza_id',
        'user_id',
        'cliente_id',
        'event',
        'system_event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'system_event' => 'boolean',
    ];

    /**
     * Relación con la póliza.
     */
    public function poliza(): BelongsTo
    {
        return $this->belongsTo(PolizaServicio::class, 'poliza_id');
    }

    /**
     * Relación con el usuario (admin/empleado).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el cliente (portal).
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Helper para registrar auditoría.
     */
    public static function log(PolizaServicio $poliza, string $event, ?array $old = null, ?array $new = null)
    {
        $userId = null;
        $clienteId = null;
        $systemEvent = false;

        if (auth()->check()) {
            $user = auth()->user();
            if ($user instanceof User) {
                $userId = $user->id;
            } elseif ($user instanceof Cliente) {
                $clienteId = $user->id;
            }
        } elseif (auth('client')->check()) {
            $clienteId = auth('client')->id();
        } else {
            // Si no hay sesión, marcar como evento de sistema (ej: cron, retrieval trigger)
            $systemEvent = true;
        }

        return self::create([
            'poliza_id' => $poliza->id,
            'user_id' => $userId,
            'cliente_id' => $clienteId,
            'event' => $event,
            'system_event' => $systemEvent,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
