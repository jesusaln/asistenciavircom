<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\ProductoSerie;
use App\Models\Producto;
use App\Models\EntregaDinero;
use App\Models\CuentasPorCobrar;
use App\Services\InventarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaCancellationService
{
    // ✅ FIX: Public constants for cancellation motives (avoids fragile string comparisons)
    public const MOTIVO_CANCELACION_SIN_SERIE = 'Cancelación de venta (producto sin serie)';
    public const MOTIVO_CANCELACION_KIT_PREFIX = 'Cancelación de venta de kit: ';
    public const MOTIVO_CANCELACION_PATTERN = 'Cancelación de venta%'; // For LIKE queries

    public function __construct(
        private readonly InventarioService $inventarioService
    ) {
    }

    /**
     * Cancel a venta and return all inventory
     *
     * @param Venta $venta The venta to cancel
     * @param string|null $motivo Optional cancellation reason
     * @param bool $forceWithPayments If true (admin only), delete payments first
     * @return Venta
     * @throws \Exception
     */
    public function cancelVenta(Venta $venta, ?string $motivo = null, bool $forceWithPayments = false): Venta
    {
        // Validate venta can be cancelled
        $this->validateCancellation($venta, $forceWithPayments);

        return DB::transaction(function () use ($venta, $motivo, $forceWithPayments) {
            $statusBefore = $venta->estado?->value ?? 'desconocido';

            // 0. If forcing with payments, delete all payment records first
            if ($forceWithPayments) {
                $this->deleteVentaPayments($venta);
            }

            // 1. Update venta status to cancelled
            $venta->update(['estado' => \App\Enums\EstadoVenta::Cancelada]);

            // 2. Return series to stock (Observer handles inventory sync automatically)
            $this->returnSeriesToStock($venta);

            // 3. Return inventory for non-serialized products
            $this->returnNonSerializedInventory($venta);

            // 4. Update CuentasPorCobrar
            $this->updateCuentaPorCobrar($venta);

            // 5. Dispatch VentaCancelled event
            event(new \App\Events\VentaCancelled($venta, $motivo));

            return $venta->fresh() ?? $venta;
        });
    }

    /**
     * Validate that the venta can be cancelled
     *
     * @throws \Exception
     */
    protected function validateCancellation(Venta $venta, bool $forceWithPayments = false): void
    {
        // Check if already cancelled
        if ($venta->estado?->value === \App\Enums\EstadoVenta::Cancelada->value) {
            throw new \Exception('La venta ya está cancelada');
        }

        // Check if there are any payments (skip if admin is forcing)
        if (!$forceWithPayments) {
            $cuentaPorCobrar = $venta->cuentaPorCobrar;
            if ($cuentaPorCobrar && $cuentaPorCobrar->monto_pagado > 0) {
                throw new \Exception('No se puede cancelar una venta con pagos registrados');
            }
        }
    }

    /**
     * Delete all payment records for a venta (admin only operation)
     */
    protected function deleteVentaPayments(Venta $venta): void
    {
        // 1. Delete EntregaDinero records (Cash/Mixed)
        $deletedEntregas = EntregaDinero::where('tipo_origen', 'venta')
            ->where('id_origen', $venta->id)
            ->forceDelete();

        if ($deletedEntregas > 0) {
            Log::info('Deleted EntregaDinero records for cancelled venta', [
                'venta_id' => $venta->id,
                'records_deleted' => $deletedEntregas,
                'user_id' => auth()->id(),
            ]);
        }

        // 2. Identify and Delete MovimientoBancario records
        // Structured approach: Find CxC first, then movements linked to it
        $cxc = CuentasPorCobrar::where('cobrable_type', 'venta') // Using alias/map if available, or direct class
            ->where('cobrable_id', $venta->id)
            ->first();

        if ($cxc) {
            // Find all bank movements linked to this CxC via polymorphic relation
            $movimientos = \App\Models\MovimientoBancario::where('conciliable_type', get_class($cxc))
                ->where('conciliable_id', $cxc->id)
                ->get();

            foreach ($movimientos as $movimiento) {
                // Revert balance on the bank account if it was already updated
                if ($movimiento->cuentaBancaria && $movimiento->estado === 'conciliado') {
                    $movimiento->cuentaBancaria->revertirSaldoPorMovimiento($movimiento);
                }

                // Force delete the movement to clean up financial history for this cancelled sale
                $movimiento->forceDelete();
            }

            if ($movimientos->count() > 0) {
                Log::info('Deleted MovimientoBancario records via CxC for cancelled venta', [
                    'venta_id' => $venta->id,
                    'cxc_id' => $cxc->id,
                    'records_deleted' => $movimientos->count(),
                ]);
            }

            // 3. Finally delete the CuentasPorCobrar
            $cxc->forceDelete();
        }

        // Fallback: If for some reason CxC didn't exist or movements were linked directly to Venta
        $movimientosDirectos = \App\Models\MovimientoBancario::where('conciliable_type', get_class($venta))
            ->where('conciliable_id', $venta->id)
            ->get();
            
        foreach ($movimientosDirectos as $mov) {
            if ($mov->cuentaBancaria && $mov->estado === 'conciliado') {
                $mov->cuentaBancaria->revertirSaldoPorMovimiento($mov);
            }
            $mov->forceDelete();
        }
    }

    /**
     * Return all product series to stock
     */
    protected function returnSeriesToStock(Venta $venta): void
    {
        // ✅ FIX: Include trashed series to ensure everything is returned
        $seriesVendidas = ProductoSerie::withTrashed()
            ->where('venta_id', $venta->id)
            ->get();

        foreach ($seriesVendidas as $serie) {
            // Restore if deleted
            if ($serie->trashed()) {
                $serie->restore();
            }

            $serie->update([
                'estado' => 'en_stock',
                'venta_id' => null
            ]);
            // ✅ ProductoSerieObserver automatically syncs inventory when state changes
        }
    }

    /**
     * Return inventory for products without series
     */
    protected function returnNonSerializedInventory(Venta $venta): void
    {
        // ✅ FIX: Include trashed items to ensure all components are returned, even in edge cases
        $productItems = $venta->items()->withTrashed()
            ->where('ventable_type', Producto::class)
            ->get();

        foreach ($productItems as $item) {
            $producto = $item->ventable;

            if (!$producto) {
                continue;
            }

            // Si es un kit, devolver inventario de componentes en lugar del kit
            if ($producto->esKit()) {
                $this->returnKitComponentsInventory($producto, $item->cantidad, $venta);
            } else {
                // ✅ CRITICAL: Only return inventory manually for products WITHOUT series
                // Products with series are handled automatically by ProductoSerieObserver
                if (!($producto->requiere_serie ?? false)) {
                    $this->inventarioService->entrada($producto, $item->cantidad, [
                        'motivo' => self::MOTIVO_CANCELACION_SIN_SERIE,
                        'almacen_id' => $venta->almacen_id,
                        'user_id' => auth()->id(),
                        'referencia' => $venta,
                    ]);
                }
            }
        }
    }

    /**
     * Return inventory for kit components when canceling a kit sale
     */
    protected function returnKitComponentsInventory(Producto $kit, int $cantidadKits, Venta $venta): void
    {
        foreach ($kit->kitItems as $kitItem) {
            // Solo procesar productos, no servicios
            if (!$kitItem->esProducto()) {
                continue;
            }

            $componente = $kitItem->item;

            if (!$componente) {
                continue;
            }

            $cantidadNecesaria = $kitItem->cantidad * $cantidadKits;

            // Verificar si el componente requiere series
            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if (!$requiereSeries) {
                // Devolver inventario del componente no serializado
                $this->inventarioService->entrada($componente, $cantidadNecesaria, [
                    'motivo' => self::MOTIVO_CANCELACION_KIT_PREFIX . $kit->nombre,
                    'almacen_id' => $venta->almacen_id,
                    'user_id' => auth()->id(),
                    'referencia' => $venta,
                ]);
            }
            // Series are handled automatically by ProductoSerieObserver in returnSeriesToStock
        }
    }

    /**
     * Update the CuentasPorCobrar to cancelled state
     */
    protected function updateCuentaPorCobrar(Venta $venta): void
    {
        if (!$venta->cuentaPorCobrar) {
            return;
        }

        $venta->cuentaPorCobrar->update([
            'monto_total' => 0,
            'monto_pagado' => 0,
            'monto_pendiente' => 0,
            'estado' => 'cancelada',
            'notas' => ($venta->cuentaPorCobrar->notas ?? '') .
                ' | Venta cancelada el ' . now()->format('Y-m-d H:i:s'),
        ]);

        $venta->cuentaPorCobrar->actualizarEstado();
    }
}
