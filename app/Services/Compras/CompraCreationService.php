<?php

namespace App\Services\Compras;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\ProductoPrecioHistorial;
use App\Models\CuentasPorPagar;
use App\Models\CuentaBancaria;
use App\Services\InventarioService;
use App\Enums\EstadoCompra;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompraCreationService
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly CompraCuentasPagarService $cuentasPagarService
    ) {
    }

    /**
     * Create a new purchase document and handle all related records.
     *
     * @param array $data Purchase header data
     * @param array $items List of items to purchase
     * @return Compra
     */
    public function createCompra(array $data, array $items): Compra
    {
        return DB::transaction(function () use ($data, $items) {
            // 1. Generate Number if not provided
            if (empty($data['numero_compra'])) {
                $data['numero_compra'] = $this->generarNumeroCompra();
            }

            // 2. Create Compra Header
            $compraData = $this->prepareCompraData($data);
            $compra = Compra::create($compraData);

            Log::info('CompraCreationService: Compra Header Created', ['id' => $compra->id, 'numero' => $compra->numero_compra]);

            // 3. Create Accounts Payable (CXP)
            $cuentaPorPagar = $this->cuentasPagarService->crearCuentaPorPagar(
                $compra,
                $compra->total,
                $data['cfdi_fecha'] ?? null
            );

            // 4. Process Items
            foreach ($items as $itemData) {
                $this->processItem($compra, $itemData);
            }

            // 5. Handle Payment (Bank Movement & CXP Update)
            $this->handlePayment($compra, $cuentaPorPagar, $data);

            return $compra;
        });
    }

    private function prepareCompraData(array $data): array
    {
        return [
            'numero_compra' => $data['numero_compra'],
            'proveedor_id' => $data['proveedor_id'],
            'almacen_id' => $data['almacen_id'],
            'orden_compra_id' => $data['orden_compra_id'] ?? null,
            'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
            'cuenta_bancaria_id' => $data['cuenta_bancaria_id'] ?? null,

            // Totals
            'subtotal' => $data['subtotal'] ?? 0,
            'descuento_items' => $data['descuento_items'] ?? 0,
            'descuento_general' => $data['descuento_general'] ?? 0,
            'iva' => $data['iva'] ?? 0,
            'retencion_iva' => $data['retencion_iva'] ?? 0,
            'retencion_isr' => $data['retencion_isr'] ?? 0,
            'isr' => $data['isr'] ?? 0,
            'aplicar_retencion_iva' => $data['aplicar_retencion_iva'] ?? false,
            'aplicar_retencion_isr' => $data['aplicar_retencion_isr'] ?? false,
            'total' => $data['total'] ?? 0,

            // Status & Meta
            'estado' => $data['estado'] ?? EstadoCompra::Procesada->value,
            'fecha_compra' => $data['fecha_compra'] ?? now(),
            'user_id' => Auth::id() ?? $data['user_id'] ?? null,
            'inventario_procesado' => $data['inventario_procesado'] ?? true,
            'notas' => $data['notas'] ?? null,

            // CFDI Fields
            'cfdi_uuid' => $data['cfdi_uuid'] ?? null,
            'cfdi_folio' => $data['cfdi_folio'] ?? null,
            'cfdi_serie' => $data['cfdi_serie'] ?? null,
            'cfdi_fecha' => $data['cfdi_fecha'] ?? null,
            'cfdi_emisor_rfc' => $data['cfdi_emisor_rfc'] ?? null,
            'cfdi_emisor_nombre' => $data['cfdi_emisor_nombre'] ?? null,
            'origen_importacion' => $data['origen_importacion'] ?? (isset($data['cfdi_uuid']) ? 'manual_con_cfdi' : 'manual'),
        ];
    }

    private function processItem(Compra $compra, array $itemData): void
    {
        $producto = Producto::findOrFail($itemData['id']);

        // Check active status
        if ($producto->estado !== 'activo') {
            Log::warning('CompraCreationService: Skipping inactive product', ['id' => $producto->id]);
            return;
        }

        $cantidad = $itemData['cantidad'];
        $precio = $itemData['precio'];
        $descuento = $itemData['descuento'] ?? 0;

        // Calculate subtotal details
        $subtotal = $cantidad * $precio;
        $descuentoMonto = $subtotal * ($descuento / 100);
        $subtotalFinal = $subtotal - $descuentoMonto;

        // Update Price History
        if ($producto->precio_compra != $precio) {
            $this->updateProductPrice($producto, $precio, $compra);
        }

        // Create Compra Item
        CompraItem::create([
            'compra_id' => $compra->id,
            'comprable_id' => $producto->id,
            'comprable_type' => Producto::class,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'descuento' => $descuento,
            'subtotal' => $subtotalFinal,
            'descuento_monto' => $descuentoMonto,
            'descripcion' => $itemData['descripcion'] ?? $producto->descripcion,
            'unidad_medida' => $itemData['unidad_medida'] ?? $producto->unidad_medida,
        ]);

        // Inventory Entry
        $this->inventarioService->entrada($producto, $cantidad, [
            'skip_transaction' => true,
            'motivo' => 'Nueva compra ' . ($compra->orden_compra_id ? '(desde Orden)' : ''),
            'almacen_id' => $compra->almacen_id,
            'user_id' => Auth::id(),
            'referencia_type' => Compra::class,
            'referencia_id' => $compra->id,
            'detalles' => [
                'compra_id' => $compra->id,
                'producto_id' => $producto->id,
                'precio_unitario' => $precio,
            ],
            // Pass Lote info if present
            'numero_lote' => $itemData['numero_lote'] ?? null,
            'fecha_caducidad' => $itemData['fecha_caducidad'] ?? null,
            'costo_unitario' => $itemData['costo_unitario'] ?? null,
        ]);

        // Register Series
        if (($producto->requiere_serie ?? false) && !empty($itemData['seriales']) && is_array($itemData['seriales'])) {
            foreach ($itemData['seriales'] as $serie) {
                ProductoSerie::create([
                    'producto_id' => $producto->id,
                    'compra_id' => $compra->id,
                    'almacen_id' => $compra->almacen_id,
                    'numero_serie' => trim((string) $serie),
                    'estado' => 'en_stock',
                ]);
            }
        }
    }

    private function updateProductPrice(Producto $producto, float $precio, Compra $compra): void
    {
        $oldPrecioCompra = $producto->precio_compra;
        $producto->update(['precio_compra' => $precio]);

        ProductoPrecioHistorial::create([
            'producto_id' => $producto->id,
            'precio_compra_anterior' => $oldPrecioCompra,
            'precio_compra_nuevo' => $precio,
            'precio_venta_anterior' => null, // Not changing sales price automatically here
            'precio_venta_nuevo' => $producto->precio_venta,
            'tipo_cambio' => 'compra',
            'notas' => "Actualización por nueva compra #{$compra->numero_compra}",
            'user_id' => Auth::id(),
        ]);
    }

    private function handlePayment(Compra $compra, ?CuentasPorPagar $cuentaPorPagar, array $data): void
    {
        $metodoPago = $data['metodo_pago'] ?? 'efectivo';
        $pagoInmediato = !empty($data['cuenta_bancaria_id']) || $metodoPago === 'efectivo';

        // Check for specific Importation PUE logic
        if (!empty($data['pagado_importacion']) && $data['pagado_importacion']) {
            $pagoInmediato = true;
            $metodoPago = $data['pue_metodo_pago'] ?? $metodoPago;

            // If specific bank account for PUE was provided
            if (!empty($data['pue_cuenta_bancaria_id'])) {
                $compra->update([
                    'cuenta_bancaria_id' => $data['pue_cuenta_bancaria_id'],
                    'metodo_pago' => $metodoPago
                ]);
                $data['cuenta_bancaria_id'] = $data['pue_cuenta_bancaria_id'];
            }
        }

        // Register Bank Movement
        if (!empty($data['cuenta_bancaria_id'])) {
            $cuentaBancaria = CuentaBancaria::find($data['cuenta_bancaria_id']);

            if ($cuentaBancaria) {
                if ($cuentaBancaria->saldo_actual < $compra->total) {
                    // We throw exception here, handled by caller (Controller)
                    throw new \App\Exceptions\SaldoInsuficienteException(
                        $cuentaBancaria->banco,
                        $cuentaBancaria->saldo_actual,
                        $compra->total
                    );
                }

                $cuentaBancaria->registrarMovimiento(
                    'retiro',
                    $compra->total,
                    "Pago de compra #{$compra->numero_compra} - Proveedor: " . ($compra->proveedor->nombre_razon_social ?? 'N/A'),
                    'pago'
                );
            }
        }

        // Update CXP if Paid
        if ($pagoInmediato && $cuentaPorPagar) {
            $cuentaPorPagar->update([
                'pagado' => true,
                'estado' => 'pagado',
                'metodo_pago' => $metodoPago,
                'cuenta_bancaria_id' => $data['cuenta_bancaria_id'] ?? null,
                'fecha_pago' => now(),
                'pagado_por' => Auth::id(),
                'notas_pago' => 'Pagado al registrar la compra' . (!empty($data['pagado_importacion']) ? ' (Importación PUE)' : ''),
                'monto_pagado' => (string) $compra->total,
                'monto_pendiente' => 0,
                'pue_pagado' => !empty($data['pagado_importacion']) && (($compra->cfdi_metodo_pago ?? '') === 'PUE'),
            ]);
        }
    }

    public function getNextNumeroCompra(): string
    {
        return $this->generarNumeroCompra();
    }

    private function generarNumeroCompra(): string
    {
        $ultimaCompra = Compra::where('numero_compra', 'LIKE', 'C%')
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if (!$ultimaCompra || !$ultimaCompra->numero_compra) {
            return 'C0001';
        }

        $matches = [];
        if (preg_match('/C(\d+)$/', $ultimaCompra->numero_compra, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $siguienteNumero = $ultimoNumero + 1;
            return 'C' . str_pad((string) $siguienteNumero, 4, '0', STR_PAD_LEFT);
        }

        return 'C0001';
    }
}
