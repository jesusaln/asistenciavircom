<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaAuditLog extends Model
{
    protected $table = 'venta_audit_logs';

    protected $fillable = [
        'venta_id',
        'user_id',
        'action',
        'status_before',
        'status_after',
        'changes',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a sale action
     */
    public static function logAction(
        ?int $ventaId,
        string $action,
        ?string $statusBefore = null,
        ?string $statusAfter = null,
        ?array $changes = null,
        ?string $notes = null
    ): self {
        return self::create([
            'venta_id' => $ventaId,
            'user_id' => auth()->id(),
            'action' => $action,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'changes' => $changes,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
