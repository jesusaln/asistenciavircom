<?php

namespace App\Observers;

use App\Models\Venta;
use App\Models\CuentasPorCobrar;
use App\Models\MovimientoBancario;
use App\Models\EntregaDinero;
use Illuminate\Support\Facades\Log;

class VentaObserver
{
    /**
     * Handle the Venta "updated" event.
     * Sincroniza el estado de CuentaPorCobrar cuando la venta se marca como pagada.
     */
    public function updated(Venta $venta): void
    {
        // Solo procesar si cambió el campo 'pagado'
        if (!$venta->wasChanged('pagado')) {
            return;
        }

        // Buscar la CxC asociada usando query directa para evitar problemas de relación
        $cxc = CuentasPorCobrar::where('cobrable_type', Venta::class)
            ->where('cobrable_id', $venta->id)
            ->first();

        if (!$cxc) {
            Log::info("VentaObserver: Venta #{$venta->id} no tiene CuentaPorCobrar asociada");
            return;
        }

        if ($venta->pagado) {
            // Venta marcada como pagada -> actualizar CxC
            $cxc->update([
                'estado' => 'pagado',
                'monto_pagado' => $cxc->monto_total,
                'monto_pendiente' => 0,
                'fecha_pago' => $venta->fecha_pago ?? now(),
                'metodo_pago' => $venta->metodo_pago,
            ]);
            Log::info("VentaObserver: CxC #{$cxc->id} sincronizada a PAGADO (Venta {$venta->numero_venta})");
        } else {
            // Venta desmarcada como pagada -> revertir CxC
            $cxc->update([
                'estado' => 'pendiente',
                'monto_pagado' => 0,
                'monto_pendiente' => $cxc->monto_total,
                'fecha_pago' => null,
            ]);
            Log::info("VentaObserver: CxC #{$cxc->id} revertida a PENDIENTE (Venta {$venta->numero_venta})");
        }
    }

    /**
     * Handle the Venta "deleted" event.
     * Cancela la CxC y limpia movimientos financieros cuando se elimina la venta.
     */
    public function deleted(Venta $venta): void
    {
        // ✅ FIX (A-03): Invalidate stats cache
        \App\Services\Ventas\VentaQueryService::invalidateStatsCache($venta->empresa_id);

        // 1. Find the associated CxC
        $cxc = CuentasPorCobrar::where('cobrable_type', Venta::class)
            ->where('cobrable_id', $venta->id)
            ->first();

        // 2. Limpiar Movimientos Bancarios asociados (Fix A-05: Use polymorphic relations instead of strings)
        $movimientos = MovimientoBancario::where(function ($query) use ($venta, $cxc) {
            $query->whereHasMorph('conciliable', [Venta::class], function ($q) use ($venta) {
                $q->where('id', $venta->id);
            });
            
            if ($cxc) {
                $query->orWhereHasMorph('conciliable', [CuentasPorCobrar::class], function ($q) use ($cxc) {
                    $q->where('id', $cxc->id);
                });
            }
        })->get();

        foreach ($movimientos as $movimiento) {
            if ($movimiento->cuentaBancaria) {
                $movimiento->cuentaBancaria->revertirSaldoPorMovimiento($movimiento);
            }
            $movimiento->delete();
        }

        if ($movimientos->count() > 0) {
            Log::info("VentaObserver: " . $movimientos->count() . " movimientos bancarios eliminados para Venta #{$venta->id}");
        }

        // 3. Eliminar CxC asociada (soft delete)
        if ($cxc) {
            $cuentaId = $cxc->id;
            $cxc->delete();
            Log::info("VentaObserver: CxC #{$cuentaId} eliminada por eliminación de Venta #{$venta->id}");
        }

        // 4. Limpiar Entregas de Dinero asociadas (Efectivo/Caja)
        $entregas = EntregaDinero::where('tipo_origen', 'venta')
            ->where('id_origen', $venta->id)
            ->get();

        foreach ($entregas as $entrega) {
            $entrega->delete();
        }

        if ($entregas->count() > 0) {
            Log::info("VentaObserver: {$entregas->count()} registros de EntregaDinero eliminados (soft) para Venta #{$venta->id}");
        }

        if ($movimientos->count() > 0) {
            Log::info("VentaObserver: " . $movimientos->count() . " movimientos bancarios eliminados para Venta #{$venta->id}");
        }
    }

    /**
     * Handle the Venta "saved" event.
     */
    public function saved(Venta $venta): void
    {
        // ✅ FIX (A-03): Invalidate stats cache on any change
        \App\Services\Ventas\VentaQueryService::invalidateStatsCache($venta->empresa_id);
    }
}
