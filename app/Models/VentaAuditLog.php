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
        $statusBefore = null,
        $statusAfter = null,
        ?array $changes = null,
        ?string $notes = null
    ): self {
        // Handle Enums or Objects
        $statusBeforeStr = '';
        if ($statusBefore !== null) {
            if ($statusBefore instanceof \BackedEnum) {
                $statusBeforeStr = $statusBefore->value;
            } elseif ($statusBefore instanceof \UnitEnum) {
                $statusBeforeStr = $statusBefore->name;
            } else {
                $statusBeforeStr = (string) $statusBefore;
            }
        }

        $statusAfterStr = '';
        if ($statusAfter !== null) {
            if ($statusAfter instanceof \BackedEnum) {
                $statusAfterStr = $statusAfter->value;
            } elseif ($statusAfter instanceof \UnitEnum) {
                $statusAfterStr = $statusAfter->name;
            } else {
                $statusAfterStr = (string) $statusAfter;
            }
        }

        return self::create([
            'venta_id' => $ventaId,
            'user_id' => auth()->id(),
            'action' => $action,
            'status_before' => substr($statusBeforeStr, 0, 255),
            'status_after' => substr($statusAfterStr, 0, 255),
            'changes' => $changes,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
