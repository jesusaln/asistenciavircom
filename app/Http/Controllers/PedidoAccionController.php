<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoAccionController extends Controller
{
    /**
     * Duplicate a pedido.
     */
    public function duplicate(Request $request, $id)
    {
        try {
            $pedido = Pedido::with('cliente', 'items.pedible')->findOrFail($id);

            if ($pedido->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede duplicar un pedido sin ítems.'
                ], 400);
            }

            DB::beginTransaction();

            $nuevoPedido = new Pedido([
                'cliente_id' => $pedido->cliente_id,
                'cotizacion_id' => $pedido->cotizacion_id,
                'numero_pedido' => $this->generarNumeroPedido(),
                'subtotal' => $pedido->subtotal,
                'descuento_general' => $pedido->descuento_general,
                'iva' => $pedido->iva,
                'isr' => $pedido->isr ?? 0,
                'total' => $pedido->total,
                'notas' => $pedido->notas,
                'estado' => EstadoPedido::Borrador,
                'fecha' => now(),
            ]);

            $nuevoPedido->save();

            $itemsDuplicados = 0;
            foreach ($pedido->items as $item) {
                $modelo = $item->pedible;
                if (!$modelo) {
                    Log::warning('Producto/Servicio no encontrado al duplicar pedido', [
                        'pedido_id' => $id,
                        'pedible_id' => $item->pedible_id,
                        'pedible_type' => $item->pedible_type
                    ]);
                    continue;
                }

                $nuevoPedido->items()->create([
                    'pedible_id' => $item->pedible_id,
                    'pedible_type' => $item->pedible_type,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,
                    'descuento' => $item->descuento,
                    'subtotal' => $item->subtotal,
                    'descuento_monto' => $item->descuento_monto,
                    'price_list_id' => $item->price_list_id,
                ]);

                $itemsDuplicados++;
            }

            if ($itemsDuplicados === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudieron duplicar los ítems del pedido.'
                ], 400);
            }

            DB::commit();

            Log::info('Pedido duplicado exitosamente', [
                'pedido_original_id' => $id,
                'pedido_nuevo_id' => $nuevoPedido->id,
                'items_duplicados' => $itemsDuplicados
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido duplicado correctamente.',
                'pedido_id' => $nuevoPedido->id,
                'numero_pedido' => $nuevoPedido->numero_pedido,
                'items_count' => $itemsDuplicados
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Error de base de datos al duplicar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error de base de datos al duplicar el pedido.',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error general al duplicar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno al duplicar el pedido.',
                'details' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Genera un número de pedido único.
     */
    private function generarNumeroPedido()
    {
        $ultimoPedido = Pedido::withTrashed()
            ->where('numero_pedido', 'LIKE', 'P%')
            ->orderBy('id', 'desc')
            ->lockForUpdate()
            ->first();

        if (!$ultimoPedido || !$ultimoPedido->numero_pedido) {
            return 'P0001';
        }

        $matches = [];
        if (preg_match('/P(\d+)$/', $ultimoPedido->numero_pedido, $matches)) {
            $ultimoNumero = (int) $matches[1];
            $siguienteNumero = $ultimoNumero + 1;
            return 'P' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);
        }

        return 'P0001';
    }
}
