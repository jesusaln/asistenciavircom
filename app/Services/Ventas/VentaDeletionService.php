<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\InventarioMovimiento;
use App\Models\VentaAuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VentaDeletionService
{
    public function deleteVenta(Venta $venta): void
    {
        $this->validateDeletion($venta);

        DB::transaction(function () use ($venta) {
            $estadoAnterior = $venta->estado?->value;
            $montoTotal = $venta->total;
            $itemsCount = $venta->items->count();

            // Usar soft delete
            $venta->delete();

            // Sincronizar secuencia (PostgreSQL)
            $this->sincronizarSecuenciaVentas();

            VentaAuditLog::logAction(
                $venta->id,
                'deleted',
                $estadoAnterior,
                null,
                [
                    'monto_total' => $montoTotal,
                    'items_count' => $itemsCount,
                    'series_devueltas' => true,
                    'inventario_restaurado' => true,
                ],
                'Venta deleted after complete validation'
            );
        });
    }

    private function validateDeletion(Venta $venta): void
    {
        if ($venta->estado?->value !== \App\Enums\EstadoVenta::Cancelada->value) {
            throw new \Exception('La venta debe ser cancelada antes de eliminarse para asegurar la devolución de inventario.');
        }

        if ($venta->cfdis->isNotEmpty()) {
            throw new \Exception('Esta venta no puede ser eliminada porque tiene un CFDI registrado ante el SAT.');
        }

        if ($venta->cuentaPorCobrar && $venta->cuentaPorCobrar->monto_pagado > 0) {
            throw new \Exception('No se puede eliminar una venta con pagos registrados.');
        }

        $seriesNoDevueltas = ProductoSerie::where('venta_id', $venta->id)
            ->where('estado', 'vendido')
            ->count();

        if ($seriesNoDevueltas > 0) {
            throw new \Exception("La venta tiene {$seriesNoDevueltas} serie(s) que no fueron devueltas al inventario. Cancele la venta primero.");
        }

        $this->validateInventoryRestored($venta);
    }

    private function validateInventoryRestored(Venta $venta): void
    {
        $itemsConProblemas = [];
        foreach ($venta->items()->where('ventable_type', Producto::class)->get() as $item) {
            $producto = $item->ventable;
            if (!$producto)
                continue;

            if ($producto->esKit()) {
                if (!$this->validateKitComponentsRestored($producto, $venta)) {
                    $itemsConProblemas[] = $producto->nombre;
                }
            } else {
                if (!$this->validateRegularProductRestored($producto, $venta)) {
                    $itemsConProblemas[] = $producto->nombre;
                }
            }
        }

        if (!empty($itemsConProblemas)) {
            Log::warning('Intento de eliminar venta sin devolución de inventario completa', [
                'venta_id' => $venta->id,
                'productos_sin_devolucion' => $itemsConProblemas,
            ]);
            throw new \Exception('No se puede eliminar: algunos productos no tienen movimiento de devolución registrado. Productos: ' . implode(', ', $itemsConProblemas));
        }
    }

    private function validateKitComponentsRestored(Producto $kit, Venta $venta): bool
    {
        foreach ($kit->kitItems as $kitItem) {
            if (!$kitItem->esProducto())
                continue;
            $componente = $kitItem->item;
            if (!$componente)
                continue;

            if ($this->requiresSeries($componente))
                continue;

            $movimiento = InventarioMovimiento::where('producto_id', $componente->id)
                ->where('tipo', 'entrada')
                ->where('motivo', 'like', VentaCancellationService::MOTIVO_CANCELACION_PATTERN)
                ->where('referencia_id', $venta->id)
                ->exists();

            if (!$movimiento)
                return false;
        }
        return true;
    }

    private function validateRegularProductRestored(Producto $producto, Venta $venta): bool
    {
        if ($this->requiresSeries($producto))
            return true;

        return InventarioMovimiento::where('producto_id', $producto->id)
            ->where('tipo', 'entrada')
            ->where('motivo', 'like', VentaCancellationService::MOTIVO_CANCELACION_PATTERN)
            ->where('referencia_id', $venta->id)
            ->exists();
    }

    private function requiresSeries(Producto $producto): bool
    {
        return ($producto->requiere_serie ?? false) || ($producto->maneja_series ?? false) || ($producto->expires ?? false);
    }

    private function sincronizarSecuenciaVentas(): void
    {
        try {
            DB::statement("SELECT setval(pg_get_serial_sequence('ventas', 'id'), COALESCE(MAX(id), 1), true) FROM ventas");
        } catch (\Exception $e) {
            Log::warning('Error sincronizando secuencia de ventas: ' . $e->getMessage());
        }
    }
}
