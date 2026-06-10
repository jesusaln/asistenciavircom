<?php

namespace App\Services\Ventas;

use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Servicio;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\PrecioService;
use App\Services\StockValidationService;
use App\Services\FinancialService;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;

class VentaItemsProcessor
{
    public function __construct(
        private readonly StockValidationService $stockValidationService,
        private readonly InventarioService $inventarioService,
        private readonly PrecioService $precioService,
        private readonly FinancialService $financialService
    ) {
    }

    /**
     * Process and create venta items for products
     */
    public function processProducts(Venta $venta, array $productos, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        $productoIds = array_column($productos, 'id');
        $productosModelos = Producto::with(['kitItems.item', 'inventarios'])
            ->whereIn('id', $productoIds)
            ->get()
            ->keyBy('id');

        foreach ($productos as $productoData) {
            $producto = $productosModelos->get($productoData['id']);

            if (!$producto) {
                throw new \Exception("Producto con ID {$productoData['id']} no encontrado.");
            }

            $cantidad = $productoData['cantidad'];

            if ($producto instanceof Producto && $producto->esKit()) {
                $this->processKitAsSingleItem($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId, $usarPreciosFijos);
            } else {
                $this->processSingleProduct($venta, $producto, $cantidad, $productoData, $almacenId, $priceListId, $usarPreciosFijos);
            }

            unset($producto);
        }

        unset($productosModelos);
    }

    /**
     * Process a single product (non-kit)
     */
    protected function processSingleProduct(Venta $venta, Producto $producto, int $cantidad, array $productoData, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        if ($producto->bloquear_venta_directa && ! (Auth::user()?->can('venta componentes sueltos') ?? false)) {
            throw new \Exception(
                "El producto «{$producto->nombre}» está configurado para venderse solo como parte de un kit. ".
                'Agregue el producto kit o solicite a un supervisor permiso «venta componentes sueltos».'
            );
        }

        if ($usarPreciosFijos && isset($productoData['precio']) && $productoData['precio'] !== null) {
            $precio = (float) $productoData['precio'];
            $priceListId = $productoData['price_list_id'] ?? $priceListId;
        } elseif (isset($productoData['precio']) && $productoData['precio'] !== null) {
            $precio = (float) $productoData['precio'];
            $priceListId = $productoData['price_list_id'] ?? $priceListId;
        } else {
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

        $costoHistorico = $this->stockValidationService->calcularCostoHistorico(
            $producto,
            $cantidad,
            $almacenId
        );

        $itemTotals = $this->financialService->calculateItemTotals($cantidad, $precio, $descuento);
        $subtotalItem = $itemTotals['subtotal_final'];
        $descuentoMonto = $itemTotals['descuento_monto'];

        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $producto->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem,
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoHistorico,
            'price_list_id' => $priceListId,
        ]);

        if (!empty($series)) {
            $this->processSeries($ventaItem, $producto, $series, $venta, $almacenId);
        } elseif ($producto->requiere_serie ?? false) {
            // ✅ POS AUTO-SERIES: Si no hay series pero el producto las requiere, 
            // tomamos las primeras disponibles para no bloquear la venta
            $availableSeries = \App\Models\ProductoSerie::where('producto_id', $producto->id)
                ->where('almacen_id', $almacenId)
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at')
                ->limit($cantidad)
                ->pluck('numero_serie')
                ->toArray();

            if (count($availableSeries) < $cantidad) {
                throw new \Exception("No hay suficientes números de serie disponibles en el almacén para {$producto->nombre}.");
            }

            $this->processSeries($ventaItem, $producto, $availableSeries, $venta, $almacenId);
        }

        if (!($producto->requiere_serie ?? false)) {
            $this->inventarioService->salida($producto, $cantidad, [
                'motivo' => 'Venta: ' . ($venta->numero_venta ?? 'Nueva'),
                'almacen_id' => $almacenId,
                'user_id' => Auth::id(),
                'referencia' => $venta,
            ]);
        }
    }

    /**
     * Process a kit as a single item but reduce inventory of components
     */
    protected function processKitAsSingleItem(Venta $venta, Producto $kit, int $cantidadKits, array $kitData, int $almacenId, ?int $priceListId = null, bool $usarPreciosFijos = false): void
    {
        if ($usarPreciosFijos && isset($kitData['precio']) && $kitData['precio'] !== null) {
            $precio = (float) $kitData['precio'];
            $priceListId = $kitData['price_list_id'] ?? $priceListId;
        } elseif (isset($kitData['precio']) && $kitData['precio'] !== null) {
            $precio = (float) $kitData['precio'];
            $priceListId = $kitData['price_list_id'] ?? $priceListId;
        } else {
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

        $costoTotalKit = $kit->calcularCostoKit($cantidadKits, $almacenId);

        $itemTotals = $this->financialService->calculateItemTotals($cantidadKits, $precio, $descuento);
        $subtotalItem = $itemTotals['subtotal_final'];
        $descuentoMonto = $itemTotals['descuento_monto'];

        $ventaItem = VentaItem::create([
            'venta_id' => $venta->id,
            'ventable_id' => $kit->id,
            'ventable_type' => Producto::class,
            'cantidad' => $cantidadKits,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalItem,
            'descuento_monto' => $descuentoMonto,
            'costo_unitario' => $costoTotalKit / $cantidadKits,
            'price_list_id' => $priceListId,
        ]);

        if (!empty($series)) {
            $this->processSeries($ventaItem, $kit, $series, $venta, $almacenId);
        }

        $componentesSeries = $kitData['componentes_series'] ?? [];
        $this->reducirInventarioComponentesKit($kit, $cantidadKits, $venta, $almacenId, $componentesSeries);
    }

    /**
     * Reduce inventory of all kit components
     */
    protected function reducirInventarioComponentesKit(Producto $kit, int $cantidadKits, Venta $venta, int $almacenId, array $componentesSeries = []): void
    {
        foreach ($kit->kitItems as $kitItem) {
            if (!$kitItem->esProducto()) {
                continue;
            }

            $componente = $kitItem->item;
            $cantidadNecesaria = $kitItem->cantidad * $cantidadKits;

            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if ($requiereSeries) {
                $seriesComponente = $componentesSeries[$componente->id] ?? [];

                if (empty($seriesComponente) || count($seriesComponente) !== $cantidadNecesaria) {
                    throw new \Exception(
                        "El componente '{$componente->nombre}' del kit '{$kit->nombre}' requiere {$cantidadNecesaria} series, pero " .
                        (empty($seriesComponente) ? 'no se proporcionaron' : 'se proporcionaron ' . count($seriesComponente))
                    );
                }

                $ventaItemComponente = VentaItem::create([
                    'venta_id' => $venta->id,
                    'ventable_id' => $componente->id,
                    'ventable_type' => Producto::class,
                    'cantidad' => $cantidadNecesaria,
                    'precio' => 0,
                    'descuento' => 0,
                    'subtotal' => 0,
                    'descuento_monto' => 0,
                    'costo_unitario' => $this->stockValidationService->calcularCostoHistorico($componente, $cantidadNecesaria, $almacenId),
                    'price_list_id' => null,
                ]);

                foreach ($seriesComponente as $numeroSerie) {
                    $this->procesarSerieProducto($componente, $numeroSerie, $venta, $almacenId, 'Venta de kit: ' . $kit->nombre, $ventaItemComponente);
                }
            } else {
                $this->inventarioService->salida($componente, $cantidadNecesaria, [
                    'motivo' => 'Venta (Kit: ' . $kit->nombre . '): ' . ($venta->numero_venta ?? 'Nueva'),
                    'almacen_id' => $almacenId,
                    'user_id' => Auth::id(),
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

        $serie->update([
            'estado' => 'vendido',
            'venta_id' => $venta->id
        ]);

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
    public function processServices(Venta $venta, array $servicios): void
    {
        // ✅ FIX (A-02): Preload services to avoid N+1 queries
        $servicioIds = array_column($servicios, 'id');
        $serviciosModelos = Servicio::whereIn('id', $servicioIds)->get()->keyBy('id');

        foreach ($servicios as $servicioData) {
            $servicio = $serviciosModelos->get($servicioData['id']);

            if (!$servicio) {
                throw new \Exception("Servicio con ID {$servicioData['id']} no encontrado.");
            }

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
                'price_list_id' => null,
            ]);
        }
    }
}
