<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Almacen;
use App\Models\ProductoSerie;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockValidationService
{
    /**
     * Validate stock availability before creating a sale
     *
     * @param array $productos Array of products with id, cantidad, almacen_id, series
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateStockForSale(array $productos, int $almacenId): array
    {
        $errors = [];

        foreach ($productos as $index => $productoData) {
            $producto = Producto::find($productoData['id']);

            if (!$producto) {
                $errors[] = "Producto con ID {$productoData['id']} no encontrado";
                continue;
            }

            $cantidad = $productoData['cantidad'];

            // Si es un kit, validar todos sus componentes
            if ($producto->esKit()) {
                $kitErrors = $this->validateKitStock(
                    $producto,
                    $cantidad,
                    $almacenId,
                    $productoData['componentes_series'] ?? []
                );
                if (!empty($kitErrors)) {
                    $errors = array_merge($errors, $kitErrors);
                }
            } else {
                // Validar producto normal
                $productErrors = $this->validateSingleProductStock($producto, $cantidad, $productoData, $almacenId);
                if (!empty($productErrors)) {
                    $errors = array_merge($errors, $productErrors);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate stock for a single product
     */
    private function validateSingleProductStock(Producto $producto, int $cantidad, array $productoData, int $almacenId): array
    {
        $errors = [];
        $series = $productoData['series'] ?? [];
        $requiereSeries = ($producto->requiere_serie ?? false) || ($producto->maneja_series ?? false) || ($producto->expires ?? false);

        // Validate stock availability
        $stockDisponible = $this->getStockDisponible($producto->id, $almacenId);

        // Get warehouse name for better error messages
        $almacen = Almacen::find($almacenId);
        $almacenNombre = $almacen ? $almacen->nombre : "Almacén {$almacenId}";

        if ($stockDisponible < $cantidad) {
            $errors[] = "Stock insuficiente para {$producto->nombre} en {$almacenNombre}. Disponible: {$stockDisponible}, Solicitado: {$cantidad}";
            return $errors;
        }

        // Validate series if product requires them
        if ($requiereSeries) {
            if (empty($series)) {
                $errors[] = "El producto {$producto->nombre} requiere números de serie";
                return $errors;
            }

            if (count($series) !== $cantidad) {
                $errors[] = "El producto {$producto->nombre} requiere {$cantidad} series, pero se proporcionaron " . count($series);
                return $errors;
            }

            // Validate each serie
            $serieErrors = $this->validateSeries($producto->id, $series, $almacenId);
            if (!empty($serieErrors)) {
                $errors = array_merge($errors, $serieErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate stock for a kit and all its components
     */
    public function validateKitStock(Producto $kit, int $cantidadKits, int $almacenId, array $componentesSeries = []): array
    {
        $errors = [];
        $empresaId = EmpresaResolver::resolveId();

        // Verificar que el kit tenga componentes
        if ($kit->kitItems->isEmpty()) {
            $errors[] = "El kit '{$kit->nombre}' no tiene componentes definidos";
            return $errors;
        }

        // Validar cada componente del kit
        foreach ($kit->kitItems as $kitItem) {
            // Solo validar stock para productos, no para servicios
            if (!$kitItem->esProducto()) {
                continue;
            }

            $componente = $kitItem->item;

            // Verificar que el componente existe (no soft-deleted)
            if (!$componente) {
                $errors[] = "El kit '{$kit->nombre}' contiene un componente que ya no existe (ID: {$kitItem->item_id})";
                continue;
            }

            $cantidadNecesaria = $kitItem->cantidad * $cantidadKits;

            // Verificar si el componente requiere series
            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if ($requiereSeries) {
                $seriesComponente = $componentesSeries[$componente->id] ?? [];
                if (count($seriesComponente) !== $cantidadNecesaria) {
                    $errors[] = "El componente '{$componente->nombre}' del kit '{$kit->nombre}' requiere {$cantidadNecesaria} series, pero se proporcionaron " . count($seriesComponente);
                    continue;
                }

                $seriesDisponibles = DB::table('producto_series')
                    ->where('producto_id', $componente->id)
                    ->where('almacen_id', $almacenId)
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->where('estado', 'en_stock')
                    ->whereNull('deleted_at')
                    ->pluck('numero_serie')
                    ->toArray();

                foreach ($seriesComponente as $numeroSerie) {
                    if (!in_array($numeroSerie, $seriesDisponibles, true)) {
                        $errors[] = "La serie {$numeroSerie} del componente '{$componente->nombre}' no está disponible en el almacén.";
                    }
                }
                continue;
            }

            // Validar stock del componente
            $stockDisponible = $this->getStockDisponible($componente->id, $almacenId);

            // Get warehouse name for better error messages
            $almacen = Almacen::find($almacenId);
            $almacenNombre = $almacen ? $almacen->nombre : "Almacén {$almacenId}";

            if ($stockDisponible < $cantidadNecesaria) {
                $errors[] = "Stock insuficiente '{$componente->nombre}' del kit '{$kit->nombre}' en {$almacenNombre}. Disponible: {$stockDisponible}, Necesario: {$cantidadNecesaria}";
            }
        }

        return $errors;
    }

    /**
     * Validate and lock stock INSIDE an active transaction
     * ✅ CRITICAL FIX #4: This method MUST be called inside DB::transaction()
     *
     * @param array $productos Array of products with id, cantidad, series
     * @param int $almacenId Warehouse ID
     * @return array ['valid' => bool, 'errors' => array]
     * @throws \Exception if called outside a transaction
     */
    public function validateAndLockStock(array $productos, int $almacenId): array
    {
        // Verify we're inside a transaction
        if (DB::transactionLevel() === 0) {
            throw new \Exception('validateAndLockStock() must be called inside a DB transaction');
        }

        $errors = [];

        foreach ($productos as $index => $productoData) {
            $producto = Producto::find($productoData['id']);

            if (!$producto) {
                $errors[] = "Producto con ID {$productoData['id']} no encontrado";
                continue;
            }

            if ($producto->estado !== 'activo') {
                $errors[] = "El producto {$producto->nombre} no está activo";
                continue;
            }

            $cantidad = $productoData['cantidad'];

            // Si es un kit, validar y bloquear todos sus componentes
            if ($producto->esKit()) {
                // ✅ FIX Error #1: Pasar componentes_series para validación específica
                $componentesSeries = $productoData['componentes_series'] ?? [];
                $kitErrors = $this->validateAndLockKitStock($producto, $cantidad, $almacenId, $componentesSeries);
                if (!empty($kitErrors)) {
                    $errors = array_merge($errors, $kitErrors);
                }
            } else {
                // Validar y bloquear producto normal
                $productErrors = $this->validateAndLockSingleProductStock($producto, $cantidad, $productoData, $almacenId);
                if (!empty($productErrors)) {
                    $errors = array_merge($errors, $productErrors);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate and lock stock for a single product
     */
    private function validateAndLockSingleProductStock(Producto $producto, int $cantidad, array $productoData, int $almacenId): array
    {
        $errors = [];
        $empresaId = EmpresaResolver::resolveId();
        $series = $productoData['series'] ?? [];
        $requiereSeries = ($producto->requiere_serie ?? false) || ($producto->maneja_series ?? false) || ($producto->expires ?? false);

        // ✅ CRITICAL: Lock and validate stock in one atomic operation
        // For serialized products, count actual series; for others use inventarios
        if ($requiereSeries) {
            // Lock available series rows and count them
            // Note: PostgreSQL doesn't allow FOR UPDATE with COUNT(), so we get() then count()
            $stockDisponible = DB::table('producto_series')
                ->where('producto_id', $producto->id)
                ->where('almacen_id', $almacenId)
                ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at')
                ->lockForUpdate() // Lock series rows
                ->get()
                ->count();
        } else {
            // Use inventarios table for non-serialized products: physical stock - active reservations
            $cantidadFisica = DB::table('inventarios')
                ->where('producto_id', $producto->id)
                ->where('almacen_id', $almacenId)
                ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                ->lockForUpdate() // This locks the row until transaction commits
                ->value('cantidad') ?? 0;

            // Get active reservations for this product (lock the product row to prevent concurrent changes)
            $reservasActivas = DB::table('productos')
                ->where('id', $producto->id)
                ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                ->lockForUpdate()
                ->value('reservado') ?? 0;

            $stockDisponible = max(0, $cantidadFisica - $reservasActivas);
        }

        // Get warehouse name for better error messages
        $almacen = Almacen::find($almacenId);
        $almacenNombre = $almacen ? $almacen->nombre : "Almacén {$almacenId}";

        if ($stockDisponible < $cantidad) {
            $errors[] = "Stock insuficiente para {$producto->nombre} en {$almacenNombre}. Disponible: {$stockDisponible}, Solicitado: {$cantidad}";
            return $errors;
        }

        // Validate series if product requires them
        if ($requiereSeries) {
            if (empty($series)) {
                $errors[] = "El producto {$producto->nombre} requiere números de serie";
                return $errors;
            }

            if (count($series) !== $cantidad) {
                $errors[] = "El producto {$producto->nombre} requiere {$cantidad} series, pero se proporcionaron " . count($series);
                return $errors;
            }

            // Validate each serie (already locks with lockForUpdate)
            $serieErrors = $this->validateSeries($producto->id, $series, $almacenId);
            if (!empty($serieErrors)) {
                $errors = array_merge($errors, $serieErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate and lock stock for a kit and all its components
     * ✅ FIX Error #1: Now validates specific series for serialized components
     */
    private function validateAndLockKitStock(Producto $kit, int $cantidadKits, int $almacenId, array $componentesSeries = []): array
    {
        $empresaId = EmpresaResolver::resolveId();
        $errors = [];

        // Verificar que el kit tenga componentes
        if ($kit->kitItems->isEmpty()) {
            $errors[] = "El kit '{$kit->nombre}' no tiene componentes definidos";
            return $errors;
        }

        // Validar y bloquear cada componente del kit
        foreach ($kit->kitItems as $kitItem) {
            // Solo validar stock para productos, no para servicios
            if (!$kitItem->esProducto()) {
                continue;
            }

            $componente = $kitItem->item;

            // Verificar que el componente existe (no soft-deleted)
            if (!$componente) {
                $errors[] = "El kit '{$kit->nombre}' contiene un componente que ya no existe (ID: {$kitItem->item_id})";
                continue;
            }

            $cantidadNecesaria = $kitItem->cantidad * $cantidadKits;

            // Verificar si el componente requiere series
            $requiereSeries = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);

            if ($requiereSeries) {
                // ✅ FIX Error #1: Validar series ESPECÍFICAS del componente
                $seriesComponente = $componentesSeries[$componente->id] ?? [];

                if (count($seriesComponente) !== $cantidadNecesaria) {
                    $errors[] = "El componente '{$componente->nombre}' del kit '{$kit->nombre}' requiere {$cantidadNecesaria} serie(s), pero se proporcionaron " . count($seriesComponente);
                    continue;
                }

                // ✅ FIX Error #1: Validar y bloquear cada serie específica (lockForUpdate)
                $serieErrors = $this->validateSeries($componente->id, $seriesComponente, $almacenId);
                if (!empty($serieErrors)) {
                    $errors = array_merge($errors, $serieErrors);
                }
            } else {
                // Para componentes no serializados: physical stock - active reservations
                $cantidadFisica = DB::table('inventarios')
                    ->where('producto_id', $componente->id)
                    ->where('almacen_id', $almacenId)
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->lockForUpdate()
                    ->value('cantidad') ?? 0;

                // Get active reservations for this component
                $reservasActivas = DB::table('productos')
                    ->where('id', $componente->id)
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->lockForUpdate()
                    ->value('reservado') ?? 0;

                $stockDisponible = max(0, $cantidadFisica - $reservasActivas);

                // Get warehouse name for better error messages
                $almacen = Almacen::find($almacenId);
                $almacenNombre = $almacen ? $almacen->nombre : "Almacén {$almacenId}";

                if ($stockDisponible < $cantidadNecesaria) {
                    $errors[] = "Stock insuficiente para componente '{$componente->nombre}' del kit '{$kit->nombre}' en {$almacenNombre}. Disponible: {$stockDisponible}, Necesario: {$cantidadNecesaria}";
                }
            }
        }

        return $errors;
    }

    /**
     * Get available stock for a product in a warehouse
     * ✅ ENHANCED: For serialized products, count actual series instead of relying on inventarios table
     * ✅ FIXED: For non-serialized products, subtract reservations from physical stock
     * This prevents synchronization issues between inventarios and producto_series tables
     *
     * This method is called in validateStockForSale() which runs OUTSIDE transactions
     * Locking is only done in validateAndLockStock() which runs INSIDE transactions
     */
    public function getStockDisponible(int $productoId, int $almacenId): int
    {
        $empresaId = EmpresaResolver::resolveId();
        // Check if product requires series
        $producto = Producto::find($productoId);

        if (!$producto) {
            return 0;
        }
        $requiereSeries = ($producto->requiere_serie ?? false) || ($producto->maneja_series ?? false) || ($producto->expires ?? false);

        // ✅ CRITICAL FIX: For serialized products, count actual available series
        // This ensures we always have accurate stock regardless of inventarios table sync
        if ($requiereSeries) {
            return DB::table('producto_series')
                ->where('producto_id', $productoId)
                ->where('almacen_id', $almacenId)
                ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                ->where('estado', 'en_stock')
                ->whereNull('deleted_at')
                ->count();
        }

        // For non-serialized products: physical stock - active reservations
        $cantidadFisica = DB::table('inventarios')
            ->where('producto_id', $productoId)
            ->where('almacen_id', $almacenId)
            ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
            ->value('cantidad') ?? 0;

        $reservasActivas = $producto->reservado ?? 0;

        return max(0, $cantidadFisica - $reservasActivas);
    }

    /**
     * Validate series availability
     */
    private function validateSeries(int $productoId, array $series, int $almacenId): array
    {
        $errors = [];

        // ✅ HIGH PRIORITY FIX #6: Validar almacén activo ANTES del loop
        $almacen = Almacen::find($almacenId);
        if (!$almacen) {
            $errors[] = "El almacén con ID {$almacenId} no existe";
            return $errors; // Retornar inmediatamente si el almacén no existe
        }

        if ($almacen->estado !== 'activo') {
            $errors[] = "El almacén '{$almacen->nombre}' no está activo";
            return $errors; // Retornar inmediatamente si el almacén no está activo
        }

        foreach ($series as $numeroSerie) {
            // ✅ FIX #7: Excluir series eliminadas (soft deleted)
            $serie = ProductoSerie::where('numero_serie', $numeroSerie)
                ->where('producto_id', $productoId)
                ->whereNull('deleted_at') // Excluir soft deleted
                ->lockForUpdate() // Lock the row to prevent concurrent sales
                ->first();

            if (!$serie) {
                $errors[] = "Serie {$numeroSerie} no encontrada para este producto";
                continue;
            }

            if ($serie->estado !== 'en_stock') {
                $errors[] = "Serie {$numeroSerie} no está disponible (estado: {$serie->estado})";
                continue;
            }

            // ✅ HIGH PRIORITY FIX #6: Validación de almacén corregida
            // Ahora valida correctamente si la serie está en el almacén correcto
            if ($serie->almacen_id != $almacenId) {
                $almacenSerie = Almacen::find($serie->almacen_id);
                $errors[] = "Serie {$numeroSerie} está en almacén '{$almacenSerie->nombre}', pero se intenta vender desde '{$almacen->nombre}'";
                continue;
            }
        }

        return $errors;
    }

    /**
     * Calculate historical cost using FIFO method
     * ✅ HIGH PRIORITY FIX: Enhanced validation and logging
     */
    public function calcularCostoHistorico(Producto $producto, int $cantidad, int $almacenId): float
    {
        $empresaId = EmpresaResolver::resolveId();
        // If product does not expire, calculate historical cost with warehouse filter
        if (!$producto->expires) {
            $costo = $producto->precio_compra ?? 0;

            if ($costo <= 0) {
                Log::warning("Producto sin precio de compra definido", [
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'almacen_id' => $almacenId,
                ]);
            }

            // ✅ IMPROVEMENT: Use warehouse-filtered historical cost calculation
            // This ensures costs are calculated per warehouse for non-expiring products
            if (method_exists($producto, 'calcularCostoHistorico')) {
                return $producto->calcularCostoHistorico($cantidad, $almacenId);
            }

            return $costo;
        }

        // Get inventory movements for this product in this warehouse
        $lotes = DB::table('lotes')
            ->where('producto_id', $producto->id)
            ->where('almacen_id', $almacenId)
            ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
            ->where('cantidad_actual', '>', 0)
            ->where(function ($q) {
                $q->whereNull('fecha_caducidad')
                    ->orWhere('fecha_caducidad', '>', now());
            })
            ->orderBy('fecha_caducidad', 'asc') // FIFO
            ->get();

        if ($lotes->isEmpty()) {
            // Fallback to purchase price
            Log::warning("No hay lotes disponibles para calcular costo histórico", [
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'almacen_id' => $almacenId,
                'usando_precio_compra' => $producto->precio_compra ?? 0,
            ]);
            return $producto->precio_compra ?? 0;
        }

        $costoTotal = 0;
        $cantidadRestante = $cantidad;

        foreach ($lotes as $lote) {
            if ($cantidadRestante <= 0) {
                break;
            }

            $cantidadDeLote = min($cantidadRestante, $lote->cantidad_actual);
            $costoTotal += $cantidadDeLote * ($lote->costo_unitario ?? 0);
            $cantidadRestante -= $cantidadDeLote;
        }

        // ✅ HIGH PRIORITY FIX #7: Usar precio_compra para cantidad faltante
        if ($cantidadRestante > 0) {
            $precioCompra = $producto->precio_compra ?? 0;

            Log::warning("No hay suficientes lotes, usando precio_compra para cantidad faltante", [
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'cantidad_solicitada' => $cantidad,
                'cantidad_faltante' => $cantidadRestante,
                'precio_compra_usado' => $precioCompra,
                'almacen_id' => $almacenId,
            ]);

            // Agregar el costo de la cantidad faltante usando precio_compra
            $costoTotal += $cantidadRestante * $precioCompra;
        }

        return $cantidad > 0 ? ($costoTotal / $cantidad) : 0;
    }
}
