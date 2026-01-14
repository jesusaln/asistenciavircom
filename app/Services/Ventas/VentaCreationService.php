<?php

namespace App\Services\Ventas;

use App\Models\Venta;
use App\Models\VentaItem;
use App\Models\ProductoSerie;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\CuentasPorCobrar;
use App\Services\StockValidationService;
use App\Services\InventarioService;
use App\Services\PrecioService;
use App\Services\EmpresaConfiguracionService;
use App\Services\Folio\FolioService;
use App\Services\PaymentService;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class VentaCreationService
{
    public function __construct(
        private readonly StockValidationService $stockValidationService,
        private readonly InventarioService $inventarioService,
        private readonly PrecioService $precioService,
        private readonly FolioService $folioService,
        private readonly PaymentService $paymentService,
        private readonly FinancialService $financialService
    ) {
    }

    /**
     * Calculate totals for a document (used in tests)
     * @param array $data
     * @return array
     */
    protected function calculateTotals(array $data): array
    {
        $items = array_merge($data['productos'] ?? [], $data['servicios'] ?? []);
        return $this->financialService->calculateDocumentTotals(
            $items,
            $data['descuento_general'] ?? 0,
            $data['cliente_id'] ?? null
        );
    }

    /**
     * Create a new venta with all its items and relationships
     *
     * @param array $data Validated data from request
     * @param bool $usarPreciosFijos Whether to use fixed prices from data instead of recalculating
     * @return Venta
     * @throws \Exception
     */
    public function createVenta(array $data, bool $usarPreciosFijos = false): Venta
    {
        // 0. Pre-calculate totals for validation using FinancialService
        // Combine products and services for calculation
        $itemsForCalc = array_merge($data['productos'] ?? [], $data['servicios'] ?? []);

        $totals = $this->financialService->calculateDocumentTotals(
            $itemsForCalc,
            (float) ($data['descuento_general'] ?? 0),
            $data['cliente_id'] ?? null,
            [
                'aplicar_retencion_iva' => !empty($data['retencion_iva']),
                'aplicar_retencion_isr' => !empty($data['retencion_isr']),
                'mode' => 'sales'
            ]
        );

        // 1. Validate Credit Limit
        if (isset($data['metodo_pago']) && $data['metodo_pago'] === 'credito') {
            $this->validateCreditLimit($data['cliente_id'] ?? null, $totals['total']);
        }

        // 1.1 Validate Bank Account for automated payments (Transfer/Card/Cheque)
        if (isset($data['metodo_pago']) && !in_array($data['metodo_pago'], ['credito', 'efectivo'])) {
            if (empty($data['cuenta_bancaria_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cuenta_bancaria_id' => 'Debe seleccionar una cuenta bancaria para pagos con ' . $data['metodo_pago']
                ]);
            }
        }

        return DB::transaction(function () use ($data, $usarPreciosFijos, $totals) {
            // 2. Validate and lock stock
            $stockValidation = $this->stockValidationService->validateAndLockStock(
                $data['productos'] ?? [],
                $data['almacen_id']
            );

            if (!$stockValidation['valid']) {
                throw new \Exception('Stock insuficiente: ' . implode(', ', $stockValidation['errors']));
            }

            // 3. Generate folio
            $numeroVenta = $this->folioService->getNextFolio('venta');

            // 4. Create venta record
            $venta = Venta::create([
                'cliente_id' => $data['cliente_id'] ?? null,
                'cotizacion_id' => $data['cotizacion_id'] ?? null, // Para conversiones de cotización
                'numero_venta' => $numeroVenta,
                'fecha' => now(),
                'estado' => \App\Enums\EstadoVenta::Aprobada,
                'subtotal' => $totals['subtotal'],
                'descuento_general' => $totals['descuento_general'],
                'iva' => $totals['iva'],
                'isr' => $totals['isr'] ?? 0, // Legacy cleaning
                'retencion_iva' => $totals['retencion_iva'] ?? 0,
                'retencion_isr' => $totals['retencion_isr'] ?? 0,
                'total' => $totals['total'],
                'notas' => $data['notas'] ?? null,
                'pagado' => false,
                'metodo_pago' => $data['metodo_pago'],
                'forma_pago_sat' => $data['forma_pago_sat'] ?? ($data['metodo_pago'] === 'credito' ? '99' : null),
                'metodo_pago_sat' => $data['metodo_pago_sat'] ?? ($data['metodo_pago'] === 'credito' ? 'PPD' : 'PUE'),
                'almacen_id' => $data['almacen_id'],
                'cita_id' => $data['cita_id'] ?? null,
            ]);

            // 5. Create CuentasPorCobrar
            $cuentaPorCobrar = CuentasPorCobrar::create([
                'empresa_id' => $venta->empresa_id,
                'cobrable_id' => $venta->id,
                'cobrable_type' => 'venta', // Use morph map alias
                'cliente_id' => $data['cliente_id'] ?? null,
                'monto_total' => $totals['total'],
                'monto_pagado' => 0,
                'monto_pendiente' => $totals['total'],
                'fecha_vencimiento' => now()->addDays(30), // Vence 30 días después
                'estado' => 'pendiente',
                'notas' => 'Cuenta generada automáticamente por venta',
            ]);

            // 6. Process products
            $this->processProducts($venta, $data['productos'] ?? [], $data['almacen_id'], $data['price_list_id'] ?? null, $usarPreciosFijos);

            // 7. Process services
            $this->processServices($venta, $data['servicios'] ?? []);

            // 8. Process automatic payment for non-credit sales
            if ($data['metodo_pago'] !== 'credito') {
                // ✅ FIX: Set relation manually to avoid loading issues in transaction
                $venta->setRelation('cuentaPorCobrar', $cuentaPorCobrar);

                $this->paymentService->registrarPagoContado(
                    $venta,
                    $data['metodo_pago'],
                    $data['notas'] ?? 'Pago automático al crear venta',
                    $data['cuenta_bancaria_id'] ?? null // âœ… Pass bank account if provided (for card/transfer)
                );

                // Refresh to get updated pagado status
                $venta->refresh();
            }

            // 9. Dispatch VentaCreated event
            event(new \App\Events\VentaCreated($venta));

            return $venta->fresh();
        });
    }



    /**
     * Process and create venta items for products
     */
    protected function processProducts(Venta $venta, array $productos, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        // Optimización: Cargar todos los productos de una vez para evitar N+1
        $productoIds = array_column($productos, 'id');
        // Eager load kitItems and their items (components) to prevent N+1 in kit processing
        $productosModelos = Producto::with(['kitItems.item'])
            ->whereIn('id', $productoIds)
            ->get()
            ->keyBy('id');

        foreach ($productos as $productoData) {
            $producto = $productosModelos->get($productoData['id']);

            if (!$producto) {
                throw new \Exception("Producto con ID {$productoData['id']} no encontrado.");
            }

            $cantidad = $productoData['cantidad'];

            // Si es un kit, procesarlo especialmente
            if ($producto->esKit()) {
                $this->processKitAsSingleItem($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId, $usarPreciosFijos);
            } else {
                // Procesar producto normal
                $this->processSingleProduct($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId, $usarPreciosFijos);
            }

            // Liberar memoria de la variable iterada si es muy grande (micro-optimización solicitada)
            unset($producto);
        }

        // Liberar la colección de productos
        unset($productosModelos);
    }

    /**
     * Process a single product (non-kit)
     */
    protected function processSingleProduct(Venta $venta, Producto $producto, int $cantidad, array $productoData, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        // ✅ FIX: Respetar precios fijos si el flag está activado (para conversiones de cotización)
        if ($usarPreciosFijos && isset($productoData['precio']) && $productoData['precio'] !== null) {
            // Usar precios fijos de la cotización, no recalcular
            $precio = (float) $productoData['precio'];
            $priceListId = $productoData['price_list_id'] ?? $priceListId;
        } elseif (isset($productoData['precio']) && $productoData['precio'] !== null) {
            // Usuario especificó precio manualmente, respetarlo
            $precio = (float) $productoData['precio'];
            $priceListId = $productoData['price_list_id'] ?? $priceListId;
        } else {
            // No hay precio en formulario, resolver dinámicamente con PrecioService
            $precioDetalles = $this->precioService->resolverPrecioConDetalles(
                $producto,
                $venta->cliente,
                $priceListId ? \App\Models\PriceList::find($priceListId) : null
            );
            $precio = $precioDetalles['precio'];
            $priceListId = $precioDetalles['price_list_id'];
        }

        $descuento = $productoData['descuento'] ?? 0;
        $series = $productoData['series'] ?? [];

        // Calculate historical cost using FIFO
        $costoHistorico = $this->stockValidationService->calcularCostoHistorico(
            $producto,
            $cantidad,
            $almacenId
        );

        $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
        $subtotalItem = $itemTotals['subtotal_final']; // already has discount subtracted
        $descuentoMonto = $itemTotals['descuento_monto'];

        // Create venta item
        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $producto->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem, // Fix: calculateItemTotals returns subtotal_final (subtotal - discount)
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoHistorico,
            'price_list_id' => $priceListId,  // Guardar lista usada para auditoría
        ]);

        // Process series if provided
        if (!empty($series)) {
            $this->processSeries($ventaItem, $producto, $series, $venta, $almacenId);
        }

        // Decrease inventory for non-serialized products
        if (!($producto->requiere_serie ?? false)) {
            $this->inventarioService->salida($producto, $cantidad, [
                'motivo' => 'Venta procesada',
                'almacen_id' => $almacenId,
                'user_id' => auth()->id(),
                'referencia' => $venta,
            ]);
        }
    }

    /**
     * Process a kit as a single item but reduce inventory of components
     */
    protected function processKitAsSingleItem(Venta $venta, Producto $kit, int $cantidadKits, array $kitData, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        // ✅ FIX: Respetar precios fijos si el flag está activado (para conversiones de cotización)
        if ($usarPreciosFijos && isset($kitData['precio']) && $kitData['precio'] !== null) {
            // Usar precios fijos de la cotización, no recalcular
            $precio = (float) $kitData['precio'];
            $priceListId = $kitData['price_list_id'] ?? $priceListId;
        } elseif (isset($kitData['precio']) && $kitData['precio'] !== null) {
            // Usuario especificó precio manualmente, respetarlo
            $precio = (float) $kitData['precio'];
            $priceListId = $kitData['price_list_id'] ?? $priceListId;
        } else {
            // No hay precio en formulario, resolver dinámicamente con PrecioService
            $precioDetalles = $this->precioService->resolverPrecioConDetalles(
                $kit,
                $venta->cliente,
                $priceListId ? \App\Models\PriceList::find($priceListId) : null
            );
            $precio = $precioDetalles['precio'];
            $priceListId = $precioDetalles['price_list_id'];
        }

        $descuento = $kitData['descuento'] ?? 0;
        $series = $kitData['series'] ?? [];

        // Calcular costo total del kit basado en componentes
        $costoTotalKit = $kit->calcularCostoKit($cantidadKits, $almacenId);

        $itemTotals = $this->financialService->calculateItemTotals($cantidadKits, $precio, $descuento);
        $subtotalItem = $itemTotals['subtotal_final'];
        $descuentoMonto = $itemTotals['descuento_monto'];

        // Create venta item for the kit (como un producto normal)
        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $kit->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidadKits,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem,
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoTotalKit / $cantidadKits, // Costo promedio por kit 
            'price_list_id' => $priceListId,  // Guardar lista usada para auditoría
        ]);

        // Process series if the kit requires them (kits generalmente no requieren series)
        if (!empty($series)) {
            $this->processSeries($ventaItem, $kit, $series, $venta, $almacenId);
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

                // Crear VentaItem para el componente (para trazabilidad)
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
     * Process series for a product
     */
    protected function processSeries(VentaItem $ventaItem, Producto $producto, array $series, Venta $venta, int $almacenId): void
    {
        foreach ($series as $numeroSerie) {
            $this->procesarSerieProducto($producto, $numeroSerie, $venta, $almacenId, 'Venta procesada', $ventaItem);
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
     * Process and create venta items for services
     */
    protected function processServices(Venta $venta, array $servicios): void
    {
        foreach ($servicios as $servicioData) {
            $servicio = Servicio::findOrFail($servicioData['id']);
            $cantidad = $servicioData['cantidad'];
            $precio = $servicioData['precio'];
            $descuento = $servicioData['descuento'] ?? 0;

            $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
            $subtotalItem = $itemTotals['subtotal_final'];
            $descuentoMonto = $itemTotals['descuento_monto'];

            VentaItem::create([
                'venta_id' => $venta->id,
                'ventable_id' => $servicio->id,
                'ventable_type' => Servicio::class,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'descuento' => $descuento,
                'subtotal' => $subtotalItem,
                'descuento_monto' => $descuentoMonto,
                'price_list_id' => null,  // ✅ FIX P1: Servicios no usan listas (por ahora)
            ]);
        }
    }

    /**
     * Validate if client has enough credit
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateCreditLimit(?int $clienteId, float $totalVenta): void
    {
        if (!$clienteId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cliente_id' => 'Se requiere un cliente registrado para ventas a crédito.'
            ]);
        }

        $cliente = \App\Models\Cliente::find($clienteId);

        if (!$cliente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'cliente_id' => 'Cliente no encontrado.'
            ]);
        }

        if (!$cliente->credito_activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'metodo_pago' => "El cliente {$cliente->nombre_razon_social} no tiene el crédito habilitado."
            ]);
        }

        // Calcular saldo pendiente actual
        $saldoPendiente = $cliente->saldo_pendiente; // Uses the accessor we added
        $nuevoSaldo = $saldoPendiente + $totalVenta;

        if ($nuevoSaldo > $cliente->limite_credito) {
            $disponible = max(0, $cliente->limite_credito - $saldoPendiente);
            $exceso = $nuevoSaldo - $cliente->limite_credito;

            throw \Illuminate\Validation\ValidationException::withMessages([
                'metodo_pago' => [
                    "Límite de crédito excedido.",
                    "Límite: $" . number_format((float) $cliente->limite_credito, 2),
                    "Saldo pendiente: $" . number_format((float) $saldoPendiente, 2),
                    "Disponible: $" . number_format((float) $disponible, 2),
                    "Intentando cargar: $" . number_format((float) $totalVenta, 2),
                ]
            ]);
        }
    }
}
