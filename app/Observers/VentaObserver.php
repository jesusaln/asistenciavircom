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
        // 1. Eliminar CxC asociada (Force delete as requested)
        $cxc = CuentasPorCobrar::where('cobrable_type', Venta::class)
            ->where('cobrable_id', $venta->id)
            ->first();

        if ($cxc) {
            $cuentaId = $cxc->id;
            $cxc->forceDelete();
            Log::info("VentaObserver: CxC #{$cuentaId} eliminada por eliminación de Venta #{$venta->id}");
        }

        // 2. Limpiar Entregas de Dinero asociadas (Efectivo/Caja)
        $entregasDeleted = EntregaDinero::where('tipo_origen', 'venta')
            ->where('id_origen', $venta->id)
            ->forceDelete();
        
        if ($entregasDeleted > 0) {
            Log::info("VentaObserver: {$entregasDeleted} registros de EntregaDinero eliminados para Venta #{$venta->id}");
        }

        // 3. Limpiar Movimientos Bancarios asociados
        $movimientos = MovimientoBancario::where(function($query) use ($venta) {
            $query->where('referencia', 'ilike', "%venta #{$venta->id}%")
                  ->orWhere('referencia', 'ilike', "%{$venta->numero_venta}%")
                  ->orWhere('concepto', 'ilike', "%venta #{$venta->id}%")
                  ->orWhere('concepto', 'ilike', "%Cobro Venta {$venta->numero_venta}%") 
                  ->orWhere('concepto', 'ilike', "%{$venta->numero_venta}%")
                  ->orWhereHasMorph('conciliable', [Venta::class], function($q) use ($venta) {
                      $q->where('id', $venta->id);
                  });
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
    }
}
