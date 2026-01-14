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

            return $venta->fresh();
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
        // ✅ Observer now handles this but we keep forceDelete here for double-safety
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

        // 2. ✅ Delete MovimientoBancario records (Transfer/Card/Direct Deposit)
        // Search by reference OR concept pattern used in EntregaDineroService/PaymentService/CuentaBancaria
        $movimientos = \App\Models\MovimientoBancario::where(function ($query) use ($venta) {
            $query->where('referencia', 'ilike', "%venta #{$venta->id}%")
                ->orWhere('referencia', 'ilike', "%{$venta->numero_venta}%")
                ->orWhere('concepto', 'ilike', "%venta #{$venta->id}%")
                ->orWhere('concepto', 'ilike', "%Cobro Venta {$venta->numero_venta}%")
                ->orWhere('concepto', 'ilike', "%{$venta->numero_venta}%")
                // Add fallback for conciliable relation
                ->orWhere(function ($q) use ($venta) {
                    $q->where('conciliable_type', Venta::class)
                        ->where('conciliable_id', $venta->id);
                })
                // Add fallback for CxC relation
                ->orWhere(function ($q) use ($venta) {
                    $q->where('conciliable_type', CuentasPorCobrar::class)
                        ->whereIn('conciliable_id', function ($sub) use ($venta) {
                            $sub->select('id')->from('cuentas_por_cobrar')
                                ->where('cobrable_type', Venta::class)
                                ->where('cobrable_id', $venta->id);
                        });
                })
                ->orWhereHasMorph('conciliable', [Venta::class], function ($q) use ($venta) {
                    $q->where('id', $venta->id);
                });
        })->get();

        foreach ($movimientos as $movimiento) {
            // Revert balance on the bank account
            if ($movimiento->cuentaBancaria) {
                $movimiento->cuentaBancaria->revertirSaldoPorMovimiento($movimiento);
            }

            // Delete the movement
            $movimiento->delete();
        }

        if ($movimientos->count() > 0) {
            Log::info('Deleted MovimientoBancario records for cancelled venta', [
                'venta_id' => $venta->id,
                'records_deleted' => $movimientos->count(),
                'user_id' => auth()->id(),
            ]);
        }

        // 3. Delete CuentasPorCobrar if exists
        $cxc = CuentasPorCobrar::where('cobrable_type', Venta::class)
            ->where('cobrable_id', $venta->id)
            ->first();

        if ($cxc) {
            $cuentaId = $cxc->id;
            $cxc->forceDelete();

            Log::info('Deleted CuentasPorCobrar for cancelled venta', [
                'venta_id' => $venta->id,
                'cuenta_id' => $cuentaId,
            ]);
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
