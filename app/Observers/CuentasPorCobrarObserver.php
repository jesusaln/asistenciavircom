<?php

namespace App\Observers;

use App\Models\CuentasPorCobrar;
use App\Enums\EstadoVenta;

class CuentasPorCobrarObserver
{
    /**
     * Handle the CuentasPorCobrar "saved" event.
     * Se ejecuta después de crear o actualizar.
     */
    public function saved(CuentasPorCobrar $cuenta): void
    {
        $this->sincronizarVenta($cuenta);
    }

    /**
     * Sincroniza el estado de la venta basado en el estado de la cuenta.
     */
    /**
     * Sincroniza el estado de la venta basado en el estado de la cuenta.
     */
    private function sincronizarVenta(CuentasPorCobrar $cuenta): void
    {
        // Verificar si es una venta usando la relación polimórfica
        if ($cuenta->cobrable_type !== 'venta' && $cuenta->cobrable_type !== \App\Models\Venta::class && $cuenta->cobrable_type !== 'App\\Models\\Venta') {
            return;
        }

        // Cargar la relación si no está cargada
        if (!$cuenta->relationLoaded('cobrable')) {
            $cuenta->load('cobrable');
        }

        $venta = $cuenta->cobrable;

        if (!$venta) {
            return;
        }

        $nuevoEstadoPagado = false;

        // Determinar estado de pago
        if ($cuenta->estado === 'pagado' || $cuenta->monto_pendiente <= 0) {
            $nuevoEstadoPagado = true;
        }

        // Solo actualizar si hay cambios
        if ($venta->pagado !== $nuevoEstadoPagado) {
            $venta->pagado = $nuevoEstadoPagado;

            // Si se pagó, aseguramos que el estado sea Aprobada (si no estaba cancelada)
            if ($nuevoEstadoPagado && $venta->estado !== EstadoVenta::Cancelada) {
                $venta->estado = EstadoVenta::Aprobada;
            }

            $venta->saveQuietly();

            \Log::info("Estado de venta #{$venta->numero_venta} sincronizado desde Observer", [
                'venta_id' => $venta->id,
                'pagado' => $nuevoEstadoPagado,
                'cuenta_estado' => $cuenta->estado
            ]);
        }
    }
}
