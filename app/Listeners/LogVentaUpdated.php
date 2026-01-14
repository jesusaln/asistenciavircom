<?php

namespace App\Listeners;

use App\Events\VentaUpdated;
use App\Models\VentaAuditLog;
use Illuminate\Support\Facades\Log;

class LogVentaUpdated
{
    /**
     * Handle the event.
     */
    public function handle(VentaUpdated $event): void
    {
        try {
            VentaAuditLog::logAction(
                $event->venta->id,
                'updated',
                json_encode($event->oldData),
                json_encode($event->venta->toArray()),
                [
                    'total_before' => $event->oldData['total'] ?? null,
                    'total_after' => $event->venta->total,
                ],
                'Venta updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error logging venta update', [
                'venta_id' => $event->venta->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
