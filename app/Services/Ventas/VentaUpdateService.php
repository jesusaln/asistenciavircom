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
use Illuminate\Support\Facades\DB;

class VentaUpdateService
{
    public function __construct(
        private readonly StockValidationService $stockValidationService,
        private readonly InventarioService $inventarioService,
        private readonly PrecioService $precioService
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

            // 1. Return stock and inventory from previous sale
            $this->returnPreviousInventory($venta);

            // 2. Validate and lock new stock
            $stockValidation = $this->stockValidationService->validateAndLockStock(
                $data['productos'] ?? [],
                $venta->almacen_id // Use existing almacen (cannot be changed)
            );

            if (!$stockValidation['valid']) {
                throw new \Exception('Stock insuficiente: ' . implode(', ', $stockValidation['errors']));
            }

            // 3. Delete old items
            $venta->items()->delete();

            // 4. Calculate new totals
            $totals = $this->calculateTotals($data);

            // 5. Update venta record
            $venta->update([
                'cliente_id' => $data['cliente_id'] ?? $venta->cliente_id,
                'numero_venta' => $data['numero_venta'] ?? $venta->numero_venta,
                'fecha' => $data['fecha'] ?? $venta->fecha,
                'estado' => $data['estado'] ?? $venta->estado,
                'subtotal' => $totals['subtotal'],
                'descuento_general' => $totals['descuento_general'],
                'iva' => $totals['iva'],
                'isr' => $totals['isr'] ?? 0,
                'retencion_iva' => $totals['retencion_iva'] ?? 0,
                'retencion_isr' => $totals['retencion_isr'] ?? 0,
                'total' => $totals['total'],
                'notas' => $data['notas'] ?? $venta->notas,
                'metodo_pago' => $data['metodo_pago'] ?? $venta->metodo_pago,
                'metodo_pago_sat' => $data['metodo_pago_sat'] ?? $venta->metodo_pago_sat,
                'forma_pago_sat' => $data['forma_pago_sat'] ?? $venta->forma_pago_sat,
            ]);

            // 6. Process new products
            $this->processProducts($venta, $data['productos'] ?? [], $venta->almacen_id, $data['price_list_id'] ?? null);

            // 7. Process new services
            $this->processServices($venta, $data['servicios'] ?? []);

            // 8. Update CuentasPorCobrar
            if ($venta->cuentaPorCobrar) {
                $cuenta = $venta->cuentaPorCobrar;
                $montoPagadoActual = (float) ($cuenta->monto_pagado ?? 0);

                // Validación de integridad: total no puede ser menor a lo ya pagado sin nota de crédito
                if ($totals['total'] < $montoPagadoActual) {
                    throw new \Exception(
                        "El nuevo total ($" . number_format($totals['total'], 2) .
                        ") es menor al monto ya pagado ($" . number_format($montoPagadoActual, 2) .
                        "). Por favor, registre una devolución o nota de crédito primero."
                    );
                }

                $cuenta->update([
                    'monto_total' => $totals['total'],
                    'monto_pendiente' => max(0, $totals['total'] - $montoPagadoActual),
                ]);
                $cuenta->actualizarEstado();
            }

            // 9. Dispatch VentaUpdated event
            event(new \App\Events\VentaUpdated($venta->fresh(), $oldData));

            return $venta->fresh();
        });
    }

    /**
     * Return inventory and series from previous sale state
     */
    protected function returnPreviousInventory(Venta $venta): void
    {
        // Get previous items before deleting
        $itemsAnteriores = $venta->items()->where('ventable_type', Producto::class)->get();

        foreach ($itemsAnteriores as $item) {
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
                        'motivo' => 'Edición de venta (Devolución automática)',
                        'almacen_id' => $venta->almacen_id,
                        'user_id' => auth()->id(),
                        'referencia' => $venta,
                    ]);
                }
            }
        }

        // Return series to stock (Observer handles inventory sync)
        $seriesVendidas = ProductoSerie::where('venta_id', $venta->id)->get();
        foreach ($seriesVendidas as $serie) {
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
                $this->inventarioService->entrada($componente, $cantidadNecesaria, [
                    'motivo' => 'Edición de venta de kit: ' . $kit->nombre,
                    'almacen_id' => $venta->almacen_id,
                    'user_id' => auth()->id(),
                    'referencia' => $venta,
                ]);
            }
            // Series are handled automatically by ProductoSerieObserver
        }
    }

    /**
     * Calculate all totals for the venta
     * ✅ FIX: Added ISR calculation for Persona Moral clients
     */
    protected function calculateTotals(array $data): array
    {
        $subtotal = 0;
        $descuentoItems = 0;
        $descuentoGeneral = $data['descuento_general'] ?? 0;

        // Calculate products subtotal
        foreach ($data['productos'] ?? [] as $productoData) {
            $cantidad = $productoData['cantidad'];
            $precio = $productoData['precio'];
            $descuento = $productoData['descuento'] ?? 0;

            $subtotalProducto = $cantidad * $precio;
            $descuentoMonto = $subtotalProducto * ($descuento / 100);

            $subtotal += $subtotalProducto;
            $descuentoItems += $descuentoMonto;
        }

        // Calculate services subtotal
        foreach ($data['servicios'] ?? [] as $servicioData) {
            $cantidad = $servicioData['cantidad'];
            $precio = $servicioData['precio'];
            $descuento = $servicioData['descuento'] ?? 0;

            $subtotalServicio = $cantidad * $precio;
            $descuentoMonto = $subtotalServicio * ($descuento / 100);

            $subtotal += $subtotalServicio;
            $descuentoItems += $descuentoMonto;
        }

        // Apply general discount
        $subtotalDespuesDescuento = $subtotal - $descuentoItems - $descuentoGeneral;

        // Calculate IVA
        $ivaRate = \App\Services\EmpresaConfiguracionService::getIvaPorcentaje() / 100;
        $iva = $subtotalDespuesDescuento * $ivaRate;

        // Calculate Retencion IVA
        $retencionIva = 0;
        if (\App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled()) {
            $retencionIva = isset($data['retencion_iva']) ? (float) $data['retencion_iva'] : 0;
        }

        // Calculate Retencion ISR
        $retencionIsr = 0;
        if (\App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled()) {
            $retencionIsr = isset($data['retencion_isr']) ? (float) $data['retencion_isr'] : 0;
        } elseif (\App\Services\EmpresaConfiguracionService::isIsrEnabled()) {
            // Fallback a lógica automática para Personas Morales
            $clienteId = $data['cliente_id'] ?? null;
            if ($clienteId) {
                $cliente = \App\Models\Cliente::find($clienteId);
                if ($cliente && $cliente->tipo_persona === 'moral') {
                    $isrRate = \App\Services\EmpresaConfiguracionService::getIsrPorcentaje() / 100;
                    $retencionIsr = $subtotalDespuesDescuento * $isrRate;
                }
            }
        }

        // Total = Subtotal + IVA - Retenciones
        $total = $subtotalDespuesDescuento + $iva - $retencionIva - $retencionIsr;

        return [
            'subtotal' => $subtotal,
            'descuento_items' => $descuentoItems,
            'descuento_general' => $descuentoGeneral,
            'iva' => $iva,
            'isr' => 0,
            'retencion_iva' => $retencionIva,
            'retencion_isr' => $retencionIsr,
            'total' => $total,
        ];
    }

    /**
     * Process and create venta items for products
     */
    protected function processProducts(Venta $venta, array $productos, int $almacenId, ?int $priceListId = null): void
    {
        foreach ($productos as $productoData) {
            $producto = Producto::findOrFail($productoData['id']);
            $cantidad = $productoData['cantidad'];

            // Si es un kit, procesarlo especialmente
            if ($producto->esKit()) {
                $this->processKitAsSingleItem($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId);
            } else {
                $this->processSingleProduct($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId);
            }
        }
    }

    /**
     * Process a single product (non-kit)
     */
    protected function processSingleProduct(Venta $venta, Producto $producto, int $cantidad, array $productoData, int $almacenId, ?int $priceListId = null): void
    {
        // ✅ FIX: Respetar precio del formulario o del item original
        $precio = $productoData['precio'] ?? null;
        $priceListIdFinal = $productoData['price_list_id'] ?? $priceListId;

        if ($precio === null) {
            // Buscar precio en item existente si es edición
            $itemExistente = $venta->items()
                ->where('ventable_id', $producto->id)
                ->where('ventable_type', Producto::class)
                ->first();

            if ($itemExistente) {
                // Usar precio y lista del item existente
                $precio = $itemExistente->precio;
                $priceListIdFinal = $itemExistente->price_list_id ?? $priceListIdFinal;
            } else {
                // Nuevo item, resolver precio dinámicamente
                $precioDetalles = $this->precioService->resolverPrecioConDetalles(
                    $producto,
                    $venta->cliente,
                    $priceListIdFinal ? \App\Models\PriceList::find($priceListIdFinal) : null
                );
                $precio = $precioDetalles['precio'];
                $priceListIdFinal = $precioDetalles['price_list_id'];
            }
        }

        $descuento = $productoData['descuento'] ?? 0;
        $series = $productoData['series'] ?? [];

        // Validate series count
        if ($producto->requiere_serie) {
            if (count($series) !== $cantidad) {
                throw new \Exception(
                    "El producto '{$producto->nombre}' requiere {$cantidad} serie(s), pero solo se proporcionaron " . count($series)
                );
            }
        }

        // Calculate historical cost using FIFO
        $costoHistorico = $this->stockValidationService->calcularCostoHistorico(
            $producto,
            $cantidad,
            $almacenId
        );

        $subtotalItem = $cantidad * $precio;
        $descuentoMonto = $subtotalItem * ($descuento / 100);

        // Create venta item
        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $producto->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem - $descuentoMonto,
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoHistorico,
            'price_list_id' => $priceListIdFinal,  // Guardar lista usada para auditoría
        ]);

        // Process series if provided
        if (!empty($series)) {
            $this->processSeries($ventaItem, $producto, $series, $venta->id, $almacenId);
        }

        // Decrease inventory for non-serialized products
        if (!($producto->requiere_serie ?? false)) {
            $this->inventarioService->salida($producto, $cantidad, [
                'motivo' => 'Venta actualizada',
                'almacen_id' => $almacenId,
                'user_id' => auth()->id(),
                'referencia' => $venta,
            ]);
        }
    }

    /**
     * Process a kit as a single item but reduce inventory of components
     */
    protected function processKitAsSingleItem(Venta $venta, Producto $kit, int $cantidadKits, array $kitData, int $almacenId, ?int $priceListId = null): void
    {
        // ✅ FIX: Respetar precio del formulario o del item original
        $precio = $kitData['precio'] ?? null;
        $priceListIdFinal = $kitData['price_list_id'] ?? $priceListId;

        if ($precio === null) {
            // Buscar precio en item existente si es edición
            $itemExistente = $venta->items()
                ->where('ventable_id', $kit->id)
                ->where('ventable_type', Producto::class)
                ->first();

            if ($itemExistente) {
                // Usar precio y lista del item existente
                $precio = $itemExistente->precio;
                $priceListIdFinal = $itemExistente->price_list_id ?? $priceListIdFinal;
            } else {
                // Nuevo item, resolver precio dinámicamente
                $precioDetalles = $this->precioService->resolverPrecioConDetalles(
                    $kit,
                    $venta->cliente,
                    $priceListIdFinal ? \App\Models\PriceList::find($priceListIdFinal) : null
                );
                $precio = $precioDetalles['precio'];
                $priceListIdFinal = $precioDetalles['price_list_id'];
            }
        }

        $descuento = $kitData['descuento'] ?? 0;
        $series = $kitData['series'] ?? [];

        // Calcular costo total del kit basado en componentes
        $costoTotalKit = $kit->calcularCostoKit($cantidadKits, $almacenId);

        $subtotalItem = $cantidadKits * $precio;
        $descuentoMonto = $subtotalItem * ($descuento / 100);

        // Create venta item for the kit (como un producto normal)
        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $kit->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidadKits,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem - $descuentoMonto,
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoTotalKit / $cantidadKits, // Costo promedio por kit
            'price_list_id' => $priceListIdFinal,  // Guardar lista usada para auditoría
        ]);

        // Process series if the kit requires them (kits generalmente no requieren series)
        if (!empty($series)) {
            $this->processSeries($ventaItem, $kit, $series, $venta->id, $almacenId);
        }

        // IMPORTANTE: Reducir inventario de TODOS los componentes del kit
        $componentesSeries = $kitData['componentes_series'] ?? [];
        $this->reducirInventarioComponentesKit($kit, $cantidadKits, $venta, $almacenId, $componentesSeries);
    }

    /**
     * Reduce inventory of all kit components
     */
    protected function reducirInventarioComponentesKit(Producto $kit, int $cantidadKits, Venta $venta, int $almacenId, array $componentesSeries = []): void
    {
        foreach ($kit->kitItems as $kitItem) {
            // Solo procesar productos, no servicios
            if (!$kitItem->esProducto()) {
                continue;
            }

            $componente = $kitItem->item;

            $cantidadNecesaria = $kitItem->cantidad * $cantidadKits;

            // Verificar si el componente requiere series
            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if ($requiereSeries) {
                // Procesar series para componentes serializados
                $seriesComponente = $componentesSeries[$componente->id] ?? [];

                if (empty($seriesComponente) || count($seriesComponente) !== $cantidadNecesaria) {
                    throw new \Exception(
                        "El componente '{$componente->nombre}' del kit '{$kit->nombre}' requiere {$cantidadNecesaria} series, pero " .
                        (empty($seriesComponente) ? 'no se proporcionaron' : 'se proporcionaron ' . count($seriesComponente))
                    );
                }

                // ✅ FIX: Create VentaItem for the component (for traceability) - same as VentaCreationService
                $ventaItemComponente = VentaItem::create([
                    'venta_id' => $venta->id,
                    'ventable_id' => $componente->id,
                    'ventable_type' => Producto::class,
                    'cantidad' => $cantidadNecesaria,
                    'precio' => 0, // Componente de kit, precio ya incluido en el kit
                    'descuento' => 0,
                    'subtotal' => 0,
                    'descuento_monto' => 0,
                    'costo_unitario' => $this->stockValidationService->calcularCostoHistorico($componente, $cantidadNecesaria, $almacenId),
                    'price_list_id' => null,
                ]);

                // Procesar cada serie del componente
                foreach ($seriesComponente as $numeroSerie) {
                    $this->procesarSerieProducto($componente, $numeroSerie, $venta, $almacenId, 'Venta de kit: ' . $kit->nombre, $ventaItemComponente);
                }
            } else {
                // Reducir inventario del componente no serializado
                $this->inventarioService->salida($componente, $cantidadNecesaria, [
                    'motivo' => 'Venta de kit: ' . $kit->nombre,
                    'almacen_id' => $almacenId,
                    'user_id' => auth()->id(),
                    'referencia' => $venta,
                ]);
            }
        }
    }

    /**
     * Process a single product series (used for both individual products and kit components)
     */
    protected function procesarSerieProducto(Producto $producto, string $numeroSerie, Venta $venta, int $almacenId, string $motivo, ?VentaItem $ventaItem = null): void
    {
        $serie = ProductoSerie::where('numero_serie', $numeroSerie)
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->first();

        if (!$serie) {
            throw new \Exception("Serie {$numeroSerie} no encontrada para el producto {$producto->nombre}");
        }

        if ($serie->estado !== 'en_stock') {
            throw new \Exception("Serie {$numeroSerie} no está disponible (estado: {$serie->estado})");
        }

        if ($serie->almacen_id != $almacenId) {
            $almacenActual = \App\Models\Almacen::find($almacenId);
            $almacenSerie = \App\Models\Almacen::find($serie->almacen_id);
            throw new \Exception("La serie {$numeroSerie} está en el almacén '{$almacenSerie->nombre}', pero estás vendiendo desde '{$almacenActual->nombre}'");
        }

        // Update series as sold
        $serie->update([
            'estado' => 'vendido',
            'venta_id' => $venta->id
        ]);

        // Create venta_item_series record if ventaItem is provided
        if ($ventaItem) {
            \App\Models\VentaItemSerie::create([
                'venta_item_id' => $ventaItem->id,
                'producto_serie_id' => $serie->id,
                'numero_serie' => $numeroSerie,
            ]);
        }
    }

    /**
     * Process series for a product
     */
    protected function processSeries(VentaItem $ventaItem, Producto $producto, array $series, int $ventaId, int $almacenId): void
    {
        foreach ($series as $numeroSerie) {
            $serie = ProductoSerie::where('numero_serie', $numeroSerie)
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->first();

            if (!$serie) {
                throw new \Exception("Serie {$numeroSerie} no encontrada para el producto {$producto->nombre}");
            }

            if ($serie->estado !== 'en_stock') {
                throw new \Exception("Serie {$numeroSerie} no está disponible (estado: {$serie->estado})");
            }

            if ($serie->almacen_id != $almacenId) {
                $almacenActual = \App\Models\Almacen::find($almacenId);
                $almacenSerie = \App\Models\Almacen::find($serie->almacen_id);
                throw new \Exception("La serie {$numeroSerie} está en el almacén '{$almacenSerie->nombre}', pero la venta es desde '{$almacenActual->nombre}'");
            }

            // Update series as sold
            $serie->update([
                'estado' => 'vendido',
                'venta_id' => $ventaId
            ]);

            // Create venta_item_series record
            \App\Models\VentaItemSerie::create([
                'venta_item_id' => $ventaItem->id,
                'producto_serie_id' => $serie->id,
                'numero_serie' => $numeroSerie,
            ]);
        }
    }

    /**
     * Process and create venta items for services
     */
    protected function processServices(Venta $venta, array $servicios): void
    {
        foreach ($servicios as $servicioData) {
            $servicio = Servicio::findOrFail($servicioData['id']);
            $cantidad = $servicioData['cantidad'];
            $precio = $servicioData['precio'];
            $descuento = $servicioData['descuento'] ?? 0;

            $subtotalItem = $cantidad * $precio;
            $descuentoMonto = $subtotalItem * ($descuento / 100);

            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_id' => $servicio->id,
                'ventable_type' => Servicio::class,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento' => $descuento,
                'subtotal' => $subtotalItem - $descuentoMonto,
                'descuento_monto' => $descuentoMonto,
            ]);
        }
    }
}
