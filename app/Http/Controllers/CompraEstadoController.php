<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CuentaBancaria;
use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Services\Compras\CompraCuentasPagarService;
use App\Services\Compras\CompraSerieService;
use App\Services\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

class CompraEstadoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventarioService,
        private readonly CompraSerieService $serieService,
        private readonly CompraCuentasPagarService $cuentasPagarService
    ) {
    }

    /**
     * Cancelar la compra y disminuir inventario
     */
    public function cancel($id)
    {
        try {
            $compra = Compra::findOrFail($id);

            if ($compra->estado !== EstadoCompra::Procesada->value) {
                return Redirect::back()->with('error', 'Solo se pueden cancelar compras procesadas');
            }

            DB::transaction(function () use ($compra) {
                if ($this->serieService->existenSeriesVendidas($compra->id)) {
                    throw new \Exception('No se puede cancelar la compra porque algunos productos (series) ya han sido vendidos o dados de baja.');
                }

                $compraItems = CompraItem::where('compra_id', $compra->id)->get();

                foreach ($compraItems as $item) {
                    $producto = Producto::find($item->comprable_id);
                    if (!$producto) {
                        continue;
                    }

                    $inventario = \App\Models\Inventario::where('producto_id', $producto->id)
                        ->where('almacen_id', $compra->almacen_id)
                        ->first();

                    if (!$inventario || $inventario->cantidad < $item->cantidad) {
                        $almacenNombre = $compra->almacen->nombre ?? 'desconocido';
                        throw new \Exception("No se puede cancelar: Stock insuficiente del producto '{$producto->nombre}' en el almacén '{$almacenNombre}' para revertir la compra.");
                    }
                }

                foreach ($compraItems as $item) {
                    $producto = Producto::find($item->comprable_id);
                    if (!$producto) {
                        continue;
                    }

                    if ($producto->requiere_serie ?? false) {
                        $seriesQueDecrementaran = ProductoSerie::where('compra_id', $compra->id)
                            ->where('producto_id', $producto->id)
                            ->where('estado', 'en_stock')
                            ->whereNull('deleted_at')
                            ->count();

                        $cantidadEsperada = $item->cantidad;
                        $diferencia = $cantidadEsperada - $seriesQueDecrementaran;

                        if ($diferencia > 0) {
                            Log::warning('Compra cancelación: Series faltantes detectadas', [
                                'compra_id' => $compra->id,
                                'producto_id' => $producto->id,
                                'cantidad_esperada' => $cantidadEsperada,
                                'series_en_stock' => $seriesQueDecrementaran,
                                'diferencia' => $diferencia,
                            ]);

                            $this->inventarioService->salida($producto, $diferencia, [
                                'skip_transaction' => true,
                                'motivo' => 'Cancelación de compra - Ajuste por series faltantes/vendidas',
                                'almacen_id' => $compra->almacen_id,
                                'user_id' => Auth::id(),
                                'referencia' => $compra,
                                'detalles' => [
                                    'compra_id' => $compra->id,
                                    'compra_item_id' => $item->id,
                                    'series_faltantes' => $diferencia,
                                    'razon' => 'Series no en stock o ya eliminadas',
                                ],
                            ]);
                        }
                    } else {
                        $this->inventarioService->salida($producto, $item->cantidad, [
                            'skip_transaction' => true,
                            'motivo' => 'Cancelación de compra',
                            'almacen_id' => $compra->almacen_id,
                            'user_id' => Auth::id(),
                            'referencia' => $compra,
                            'detalles' => [
                                'compra_id' => $compra->id,
                                'compra_item_id' => $item->id,
                            ],
                        ]);
                    }
                }

                $this->serieService->eliminarTodasLasSeries($compra->id);
                $this->cuentasPagarService->cancelarCuentaPorPagar($compra);

                $compra->update([
                    'estado' => EstadoCompra::Cancelada->value,
                ]);

                if ($compra->cuenta_bancaria_id) {
                    $cuentaBancaria = CuentaBancaria::find($compra->cuenta_bancaria_id);

                    if ($cuentaBancaria) {
                        $cuentaBancaria->registrarMovimiento(
                            'deposito',
                            (float) $compra->total,
                            "Devolución por cancelación de compra #{$compra->numero_compra}",
                            'devolucion'
                        );

                        Log::info('Devolución bancaria registrada por cancelación de compra', [
                            'compra_id' => $compra->id,
                            'cuenta_bancaria_id' => $cuentaBancaria->id,
                            'monto_devuelto' => $compra->total,
                            'nuevo_saldo' => $cuentaBancaria->saldo_actual
                        ]);
                    }
                }

                if ($compra->orden_compra_id) {
                    $ordenCompra = \App\Models\OrdenCompra::find($compra->orden_compra_id);
                    if ($ordenCompra) {
                        $ordenCompra->update([
                            'estado' => 'pendiente',
                            'observaciones' => ($ordenCompra->observaciones ? $ordenCompra->observaciones . "\n\n" : '') .
                                '*** COMPRA CANCELADA - ORDEN REGRESADA A PENDIENTE *** ' . now()->format('d/m/Y H:i')
                        ]);
                    }
                }
            });

            $mensaje = 'Compra cancelada exitosamente.';
            if ($compra->orden_compra_id) {
                $mensaje .= ' La orden de compra asociada ha sido regresada a estado pendiente.';
            }

            return Redirect::route('compras.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            Log::error('Error al cancelar compra: ' . $e->getMessage());
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    /**
     * Eliminación definitiva (hard delete) - compras canceladas.
     */
    public function forceDestroy($id)
    {
        $compra = Compra::withTrashed()->findOrFail($id);

        if ($compra->estado !== EstadoCompra::Cancelada->value) {
            return redirect()->back()->with('error', 'Solo se pueden eliminar definitivamente compras canceladas.');
        }

        if (is_null($compra->deleted_at)) {
            $compra->delete();
        }

        $compra->forceDelete();

        return redirect()->route('compras.index')->with('success', 'Compra eliminada definitivamente de la base de datos.');
    }
}
