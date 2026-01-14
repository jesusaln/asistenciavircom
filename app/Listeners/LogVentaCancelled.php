<?php

namespace App\Listeners;

use App\Events\VentaCancelled;
use App\Models\VentaAuditLog;
use Illuminate\Support\Facades\Log;

class LogVentaCancelled
{
    /**
     * Handle the event.
     */
    public function handle(VentaCancelled $event): void
    {
        try {
            VentaAuditLog::logAction(
                $event->venta->id,
                'cancelled',
                'active',
                'cancelada',
                ['motivo' => $event->motivo ?? 'No especificado'],
                'Venta cancelled'
            );
        } catch (\Exception $e) {
            Log::error('Error logging venta cancellation', [
                'venta_id' => $event->venta->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
