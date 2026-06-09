<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\ProductoSerie;
use App\Models\Producto;
use App\Models\Servicio;
use App\Services\StockValidationService;
use App\Services\InventarioService;
use App\Services\PrecioService;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentaUpdateService
{
    public function __construct(
        private readonly StockValidationService $stockValidationService,
        private readonly \App\Services\Inventory\InventoryManager $inventoryManager,
        private readonly \App\Services\FinancialService $financialService,
        private readonly \App\Services\Ventas\VentaItemsProcessor $ventaItemsProcessor,
        private readonly \App\Services\PrecioService $precioService
    ) {
    }

    /**
     * Update an existing venta
     *
     * @param Venta $venta The venta to update
     * @param array $data Validated data from request
     * @return Venta
     * @throws \Exception
     */
    public function updateVenta(Venta $venta, array $data): Venta
    {
        return DB::transaction(function () use ($venta, $data) {
            // Store old data for event
            $oldData = $venta->toArray();

            // 1. Calculate new totals before modifying anything (Validation)
            $itemsForCalc = array_merge($data['productos'] ?? [], $data['servicios'] ?? []);
            
            // Re-evaluate if we need ISR based on customer type (moral vs física)
            $clienteId = $data['cliente_id'] ?? $venta->cliente_id;
            
            $totals = $this->financialService->calculateDocumentTotals(
                $itemsForCalc,
                (float) ($data['descuento_general'] ?? 0),
                $clienteId,
                [
                    'aplicar_retencion_iva' => isset($data['retencion_iva']),
                    'aplicar_retencion_isr' => isset($data['retencion_isr']),
                    'mode' => 'sales'
                ]
            );

            // 2. Integrity check: cannot reduce total below already paid amount
            if ($venta->cuentaPorCobrar) {
                $montoPagadoActual = (float) ($venta->cuentaPorCobrar->monto_pagado ?? 0);
                if ($totals['total'] < $montoPagadoActual) {
                    throw new \Exception(
                        "El nuevo total ($" . number_format($totals['total'], 2) .
                        ") es menor al monto ya pagado ($" . number_format($montoPagadoActual, 2) .
                        "). Por favor, registre una devolución o nota de crédito primero."
                    );
                }
            }

            // 3. Temporarily return stock to "virtual pool" for validation
            // This ensures if we change 10 items for 10 other items, we don't fail if stock is exactly 10.
            $this->returnPreviousInventory($venta);

            // 4. Validate and lock new stock
            $stockValidation = $this->stockValidationService->validateAndLockStock(
                $data['productos'] ?? [],
                $venta->almacen_id
            );

            if (!$stockValidation['valid']) {
                throw new \Exception('Stock insuficiente para la actualización: ' . implode(', ', $stockValidation['errors']));
            }

            // 5. Delete old items and clean up associations
            // We use forceDelete for items to avoid accumulation of trashed items during multiple edits
            $venta->items()->each(function ($item) {
                $item->series()->delete();
                $item->forceDelete();
            });

            // 6. Update venta record
            $metodoPagoInput = $data['metodo_pago'] ?? $venta->metodo_pago;
            $metodoPagoEnum = \App\Enums\MetodoPago::tryFrom($metodoPagoInput);
            if (!$metodoPagoEnum) {
                foreach (\App\Enums\MetodoPago::cases() as $case) {
                    if (strcasecmp($case->value, $metodoPagoInput) === 0) {
                        $metodoPagoEnum = $case;
                        break;
                    }
                }
            }
            $metodoPagoNormalized = $metodoPagoEnum ? $metodoPagoEnum->value : $metodoPagoInput;

            $payload = [
                'cliente_id' => $clienteId,
                'numero_venta' => $data['numero_venta'] ?? $venta->numero_venta,
                'fecha' => $data['fecha'] ?? $venta->fecha,
                'subtotal' => $totals['subtotal'],
                'descuento_general' => $totals['descuento_general'],
                'iva' => $totals['iva'],
                'isr' => $totals['isr'] ?? 0,
                'retencion_iva' => $totals['retencion_iva'] ?? 0,
                'retencion_isr' => $totals['retencion_isr'] ?? 0,
                'total' => $totals['total'],
                'notas' => $data['notas'] ?? $venta->notas,
                'metodo_pago' => $metodoPagoNormalized,
                'metodo_pago_sat' => $data['metodo_pago_sat'] ?? $venta->metodo_pago_sat,
                'forma_pago_sat' => $data['forma_pago_sat'] ?? $venta->forma_pago_sat,
                'cuenta_bancaria_id' => array_key_exists('cuenta_bancaria_id', $data)
                    ? ($data['cuenta_bancaria_id'] ?: null)
                    : $venta->cuenta_bancaria_id,
            ];

            if (array_key_exists('vendedor_id', $data) && $data['vendedor_id'] !== null && $data['vendedor_id'] !== '') {
                $resolved = app(\App\Services\Ventas\VentaCreationService::class)
                    ->resolveVendedorAttribution($data, Auth::user());
                $payload['vendedor_id'] = $resolved['vendedor_id'];
                $payload['vendedor_type'] = $resolved['vendedor_type'];
            }

            $venta->update($payload);

            // 7. Process new products via unified processor
            $this->ventaItemsProcessor->processProducts(
                $venta, 
                $data['productos'] ?? [], 
                $venta->almacen_id, 
                $data['price_list_id'] ?? null,
                true // Usar precios fijos del formulario
            );

            // 8. Process new services via unified processor
            $this->ventaItemsProcessor->processServices($venta, $data['servicios'] ?? []);

            // 9. Update CuentasPorCobrar
            if ($venta->cuentaPorCobrar) {
                $cuenta = $venta->cuentaPorCobrar;
                $montoPagadoActual = (float) ($cuenta->monto_pagado ?? 0);
                
                $cuenta->update([
                    'cliente_id' => $clienteId,
                    'monto_total' => $totals['total'],
                    'monto_pendiente' => max(0, $totals['total'] - $montoPagadoActual),
                ]);
                $cuenta->actualizarEstado();
            }

            // 10. Dispatch VentaUpdated event
            event(new \App\Events\VentaUpdated($venta->fresh() ?? $venta, $oldData));

            return $venta->fresh() ?? $venta;
        });
    }

    /**
     * Return inventory and series from previous sale state
     */
    protected function returnPreviousInventory(Venta $venta): void
    {
        // Get previous items before deleting - ENHANCED with eager loading to avoid N+1
        $itemsAnteriores = $venta->items()
            ->where('ventable_type', Producto::class)
            ->with(['ventable' => function ($query) {
                // Ensure we get even if they are soft-deleted now, as we need to return their stock
                $query->withTrashed();
            }])
            ->get();

        foreach ($itemsAnteriores as $item) {
            /** @var \App\Models\VentaItem $item */
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
                    $this->inventoryManager->incrementStock($producto, $item->cantidad, $venta->almacen_id);
                }
            }
        }

        // Return series to stock (Observer handles inventory sync)
        $seriesVendidas = ProductoSerie::where('venta_id', $venta->id)->get();
        foreach ($seriesVendidas as $serie) {
            /** @var ProductoSerie $serie */
            $serie->update([
                'estado' => 'en_stock',
                'venta_id' => null
            ]);
        }
    }

    /**
     * Return inventory for kit components when editing a kit sale
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
                $this->inventoryManager->incrementStock($componente, $cantidadNecesaria, $venta->almacen_id);
            }
            // Series are handled automatically by ProductoSerieObserver
        }
    }

    /**
     * Actualiza solo notas y/o vendedor/técnico asignado sin tocar líneas ni totales (API o correcciones puntuales).
     *
     * @param  array<string, mixed>  $data  Debe incluir al menos notas o vendedor_id con valor.
     */
    public function patchVentaMeta(Venta $venta, array $data): Venta
    {
        $payload = [];

        if (array_key_exists('notas', $data)) {
            $payload['notas'] = $data['notas'];
        }

        if (array_key_exists('vendedor_id', $data) && $data['vendedor_id'] !== null && $data['vendedor_id'] !== '') {
            $resolved = app(\App\Services\Ventas\VentaCreationService::class)
                ->resolveVendedorAttribution($data, Auth::user());
            $payload['vendedor_id'] = $resolved['vendedor_id'];
            $payload['vendedor_type'] = $resolved['vendedor_type'];
        }

        if ($payload !== []) {
            $venta->update($payload);
        }

        return $venta->fresh() ?? $venta;
    }
}
