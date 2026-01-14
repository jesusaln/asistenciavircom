<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Enums\EstadoVenta;
use App\Models\CuentasPorCobrar;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoVentaController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService
    ) {
    }

    /**
     * Convertir un pedido en venta.
     * - Se unifican nombres con VentaController (numero_venta, fecha, ventable_*).
     * - Se valida y descuenta inventario de productos.
     */
    public function enviarAVenta(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $pedido = Pedido::with([
                'items.pedible',
                'cliente',
            ])->findOrFail($id);

            if (!$this->puedeEnviarseAVenta($pedido)) {
                return response()->json([
                    'success' => false,
                    'error' => 'El pedido no está en un estado válido para convertirse en venta',
                    'estado_actual' => $pedido->estado->value,
                    'requiere_confirmacion' => false
                ], 400);
            }

            if ($pedido->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'El pedido no contiene ítems para convertir en venta',
                    'requiere_confirmacion' => false
                ], 400);
            }

            if (!$pedido->cliente) {
                return response()->json([
                    'success' => false,
                    'error' => 'El pedido no tiene cliente asociado',
                    'requiere_confirmacion' => false
                ], 400);
            }

            $ventaExistente = Venta::where('pedido_id', $pedido->id)->first();

            if ($ventaExistente && !$request->has('forzar_reenvio')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Este pedido ya fue convertido en venta',
                    'requiere_confirmacion' => true,
                    'venta_id' => $ventaExistente->id,
                    'numero_venta' => $ventaExistente->numero_venta
                ], 409);
            }

            foreach ($pedido->items as $item) {
                if ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') {
                    $producto = Producto::with('inventarios')->find($item->pedible_id);
                    if (!$producto) {
                        return response()->json([
                            'success' => false,
                            'error' => "Producto con ID {$item->pedible_id} no encontrado",
                            'requiere_confirmacion' => false
                        ], 400);
                    }

                    $tieneReservas = in_array($pedido->estado, [EstadoPedido::Confirmado, EstadoPedido::EnPreparacion, EstadoPedido::ListoEntrega], true);

                    if (!$tieneReservas && $producto->stock_disponible < $item->cantidad) {
                        return response()->json([
                            'success' => false,
                            'error' => "Stock disponible insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_disponible}, Solicitado: {$item->cantidad}",
                            'requiere_confirmacion' => false
                        ], 400);
                    }
                }
            }

            $numeroVenta = $this->generarNumeroVenta();

            $almacenId = auth()->user()->almacen_venta_id ?? null;
            if (!$almacenId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo determinar el almacén de venta. Configure su almacén en su perfil de usuario.',
                    'requiere_confirmacion' => false
                ], 400);
            }

            $venta = new Venta();
            $venta->fill([
                'cliente_id' => $pedido->cliente_id,
                'pedido_id' => $pedido->id,
                'almacen_id' => $almacenId,
                'numero_venta' => $numeroVenta,
                'fecha' => now(),
                'estado' => EstadoVenta::Pendiente,
                'subtotal' => $pedido->subtotal,
                'descuento_general' => $pedido->descuento_general,
                'iva' => $pedido->iva,
                'isr' => $pedido->isr ?? 0,
                'total' => $pedido->total,
                'notas' => "Generado desde pedido #{$pedido->numero_pedido}",
                'user_id' => $request->user()->id ?? null,
            ]);
            $venta->save();

            if (!$venta->pagado) {
                CuentasPorCobrar::create([
                    'venta_id' => $venta->id,
                    'monto_total' => $venta->total,
                    'monto_pagado' => 0,
                    'monto_pendiente' => $venta->total,
                    'fecha_vencimiento' => now()->addDays(30),
                    'estado' => 'pendiente',
                    'notas' => 'Cuenta por cobrar generada automáticamente desde pedido #' . $pedido->numero_pedido,
                ]);
            }

            foreach ($pedido->items as $item) {
                $ventaItem = VentaItem::create([
                    'venta_id' => $venta->id,
                    'ventable_id' => $item->pedible_id,
                    'ventable_type' => $item->pedible_type,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                    'descuento_monto' => $item->descuento_monto,
                    'subtotal' => $item->subtotal,
                    'price_list_id' => $item->price_list_id,
                ]);

                if ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') {
                    $producto = Producto::where('id', $item->pedible_id)
                        ->lockForUpdate()
                        ->first();

                    if ($producto) {
                        $cantidadRestante = $item->cantidad;

                        // ✅ FIX: Procesar series si el producto las requiere
                        if ($producto->requiere_serie) {
                            $serialesInput = $request->input("series.{$item->pedible_id}", []);

                            if (count($serialesInput) < $item->cantidad) {
                                throw new \Exception("Se requieren " . $item->cantidad . " series para el producto '{$producto->nombre}', pero solo se enviaron " . count($serialesInput));
                            }

                            foreach ($serialesInput as $numeroSerie) {
                                $serie = \App\Models\ProductoSerie::where('producto_id', $producto->id)
                                    ->where('numero_serie', $numeroSerie)
                                    ->where('almacen_id', $almacenId)
                                    ->first();

                                if (!$serie) {
                                    throw new \Exception("La serie '{$numeroSerie}' no existe en el almacén de venta para el producto '{$producto->nombre}'");
                                }

                                if ($serie->estado !== 'en_stock') {
                                    throw new \Exception("La serie '{$numeroSerie}' no está disponible (Estado: {$serie->estado})");
                                }

                                // Actualizar serie (El ProductoSerieObserver disparará la salida de inventario)
                                $serie->update([
                                    'estado' => 'vendido',
                                    'venta_id' => $venta->id
                                ]);

                                // Vincular a la venta
                                \App\Models\VentaItemSerie::create([
                                    'venta_item_id' => $ventaItem->id,
                                    'producto_serie_id' => $serie->id,
                                    'numero_serie' => $numeroSerie,
                                ]);
                            }

                            // Limpiar reservas si existían (la salida de inventario ya la hizo el observador)
                            if ($producto->reservado > 0) {
                                $consumirReserva = min($producto->reservado, $item->cantidad);
                                $producto->decrement('reservado', $consumirReserva);
                            }
                        } else {
                            // Lógica normal para productos no serializados
                            if ($producto->reservado >= $cantidadRestante) {
                                $producto->decrement('reservado', $cantidadRestante);

                                $this->inventarioService->salida($producto, $cantidadRestante, [
                                    'motivo' => 'Consumo de reserva por conversión pedido a venta',
                                    'referencia' => $venta,
                                    'almacen_id' => $almacenId,
                                    'detalles' => [
                                        'pedido_id' => $pedido->id,
                                        'venta_id' => $venta->id,
                                        'tipo_operacion' => 'consumo_reserva'
                                    ],
                                ]);
                            } else {
                                $consumirReserva = min($producto->reservado, $cantidadRestante);
                                if ($consumirReserva > 0) {
                                    $producto->decrement('reservado', $consumirReserva);
                                }
                                $saldoASalir = $cantidadRestante - $consumirReserva;

                                if ($saldoASalir > 0) {
                                    $this->inventarioService->salida($producto, $saldoASalir, [
                                        'motivo' => 'Conversión de pedido a venta',
                                        'referencia' => $venta,
                                        'almacen_id' => $almacenId,
                                        'detalles' => [
                                            'pedido_id' => $pedido->id,
                                            'reserva_consumida' => $consumirReserva,
                                        ],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            $pedido->update(['estado' => EstadoPedido::EnviadoVenta]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada exitosamente con descuento de inventario',
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'items_count' => $venta->items()->count(),
                'total' => $venta->total
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al convertir pedido a venta: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno al procesar la conversión a venta',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Determina si el pedido puede convertirse en venta.
     */
    private function puedeEnviarseAVenta(Pedido $pedido): bool
    {
        $estadosValidos = [
            EstadoPedido::Confirmado,
            EstadoPedido::EnPreparacion,
            EstadoPedido::ListoEntrega,
        ];

        return in_array($pedido->estado, $estadosValidos, true);
    }

    /**
     * Genera un número de venta único.
     * (Formato local a este controlador: VEN-######)
     */
    private function generarNumeroVenta(): string
    {
        $ultimaVenta = Venta::withTrashed()
            ->where('numero_venta', 'LIKE', 'V%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if (!$ultimaVenta || !$ultimaVenta->numero_venta) {
            return 'V0001';
        }

        $matches = [];
        if (preg_match('/V(\d+)$/', $ultimaVenta->numero_venta, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $siguienteNumero = $ultimoNumero + 1;
            return 'V' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);
        }

        return 'V0001';
    }
}
