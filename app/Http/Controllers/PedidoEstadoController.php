<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoEstadoController extends Controller
{
    /**
     * Confirm the specified resource (reserve inventory).
     */
    public function confirmar($id)
    {
        $pedido = Pedido::with('items.pedible')->findOrFail($id);

        // Validar que no haya sido enviado a venta
        if ($pedido->estado === EstadoPedido::EnviadoVenta) {
            return response()->json([
                'success' => false,
                'error' => 'Este pedido ya fue convertido a venta y no puede confirmarse nuevamente'
            ], 400);
        }

        if (!in_array($pedido->estado, [EstadoPedido::Pendiente, EstadoPedido::Borrador])) {
            return response()->json([
                'success' => false,
                'error' => 'Solo pedidos pendientes o en borrador pueden ser confirmados'
            ], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($pedido->items as $item) {
                if ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') {
                    $producto = Producto::where('id', $item->pedible_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$producto) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "Producto con ID {$item->pedible_id} no encontrado"
                        ], 400);
                    }

                    if ($producto->stock_disponible < $item->cantidad) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'error' => "Stock disponible insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_disponible}, Solicitado: {$item->cantidad}"
                        ], 400);
                    }

                    $producto->increment('reservado', $item->cantidad);

                    Log::info("Inventario reservado para producto {$producto->id}", [
                        'producto_id' => $producto->id,
                        'pedido_id' => $pedido->id,
                        'cantidad_reservada' => $item->cantidad,
                        'reservado_anterior' => $producto->reservado - $item->cantidad,
                        'reservado_actual' => $producto->reservado,
                        'stock_disponible' => $producto->stock_disponible
                    ]);
                }
            }

            $pedido->update(['estado' => EstadoPedido::Confirmado]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado e inventario reservado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al confirmar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al confirmar el pedido',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cancel the specified resource (soft cancel).
     */
    public function cancel($id)
    {
        $pedido = Pedido::with(['items.pedible', 'ordenesCompra', 'cotizacion'])->findOrFail($id);

        if ($pedido->estado === EstadoPedido::Cancelado) {
            return response()->json([
                'success' => false,
                'error' => 'El pedido ya está cancelado'
            ], 400);
        }

        DB::beginTransaction();
        try {
            if (in_array($pedido->estado, [EstadoPedido::Confirmado, EstadoPedido::EnPreparacion, EstadoPedido::ListoEntrega])) {
                foreach ($pedido->items as $item) {
                    if ($item->pedible_type === Producto::class || $item->pedible_type === 'producto') {
                        $producto = Producto::where('id', $item->pedible_id)->lockForUpdate()->first();
                        if ($producto && $producto->reservado >= $item->cantidad) {
                            $producto->decrement('reservado', $item->cantidad);

                            Log::info("Reserva liberada para producto {$producto->id}", [
                                'producto_id' => $producto->id,
                                'pedido_id' => $pedido->id,
                                'cantidad_liberada' => $item->cantidad,
                                'reservado_anterior' => $producto->reservado + $item->cantidad,
                                'reservado_actual' => $producto->reservado,
                                'stock_disponible' => $producto->stock_disponible
                            ]);
                        }
                    }
                }
            }

            $ordenesCompraCanceladas = [];
            $estadosCancelables = ['pendiente', 'borrador'];

            foreach ($pedido->ordenesCompra as $ordenCompra) {
                if (in_array($ordenCompra->estado, $estadosCancelables)) {
                    $ordenCompra->update([
                        'estado' => 'cancelada',
                        'observaciones' => $ordenCompra->observaciones . " | Cancelada automáticamente al cancelar Pedido #{$pedido->numero_pedido}"
                    ]);

                    $ordenesCompraCanceladas[] = [
                        'id' => $ordenCompra->id,
                        'numero_orden' => $ordenCompra->numero_orden,
                        'estado_anterior' => $ordenCompra->getOriginal('estado')
                    ];

                    Log::info('Orden de compra cancelada automáticamente', [
                        'orden_compra_id' => $ordenCompra->id,
                        'numero_orden' => $ordenCompra->numero_orden,
                        'pedido_id' => $pedido->id,
                        'estado_anterior' => $ordenCompra->getOriginal('estado')
                    ]);
                }
            }

            if ($pedido->cotizacion) {
                $pedido->cotizacion->update(['estado' => EstadoCotizacion::Pendiente]);
                Log::info('Cotización revertida a pendiente al cancelar pedido', [
                    'pedido_id' => $pedido->id,
                    'cotizacion_id' => $pedido->cotizacion_id
                ]);
            }

            $pedido->update([
                'estado' => EstadoPedido::Cancelado,
                'deleted_by' => Auth::id(),
                'deleted_at' => now()
            ]);

            DB::commit();

            $mensaje = 'Pedido cancelado exitosamente';
            if (count($ordenesCompraCanceladas) > 0) {
                $mensaje .= '. Se cancelaron ' . count($ordenesCompraCanceladas) . ' orden(es) de compra pendiente(s).';
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'eliminado_por' => Auth::user()->name ?? 'Usuario actual',
                'ordenes_compra_canceladas' => $ordenesCompraCanceladas
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cancelar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al cancelar el pedido',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }
}
