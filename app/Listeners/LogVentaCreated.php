<?php

namespace App\Listeners;

use App\Events\VentaCreated;
use App\Models\VentaAuditLog;
use Illuminate\Support\Facades\Log;

class LogVentaCreated
{
    /**
     * Handle the event.
     */
    public function handle(VentaCreated $event): void
    {
        try {
            VentaAuditLog::logAction(
                $event->venta->id,
                'created',
                null,
                $event->venta->estado->value,
                [
                    'total' => $event->venta->total,
                    'productos_count' => $event->venta->items()
                        ->where('ventable_type', \App\Models\Producto::class)
                        ->count(),
                    'servicios_count' => $event->venta->items()
                        ->where('ventable_type', \App\Models\Servicio::class)
                        ->count(),
                ],
                'Venta created successfully'
            );
        } catch (\Exception $e) {
            Log::error('Error logging venta creation', [
                'venta_id' => $event->venta->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
