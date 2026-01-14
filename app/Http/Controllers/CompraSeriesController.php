<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\ProductoSerie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompraSeriesController extends Controller
{
    /**
     * Actualizar series de productos en una compra.
     */
    public function actualizarSeries(Request $request, $id)
    {
        $compra = Compra::with('productos')->findOrFail($id);

        if ($compra->estado !== EstadoCompra::Procesada->value) {
            return redirect()->back()->with('error', 'Solo se pueden editar series de compras procesadas.');
        }

        $productosConSerie = $compra->productos->filter(function ($producto) {
            return (bool) ($producto->requiere_serie ?? false);
        });

        if ($productosConSerie->isEmpty()) {
            return redirect()->back()->with('info', 'Esta compra no tiene productos que requieran series.');
        }

        $rules = [
            'productos' => 'required|array|min:1',
        ];

        $productoIdsEsperados = $productosConSerie->pluck('id')->toArray();

        foreach ($productosConSerie->values() as $index => $producto) {
            $requiredSize = max(1, (int) ($producto->pivot->cantidad ?? 1));
            $rules["productos.{$index}.id"] = 'required|integer|in:' . $producto->id;
            $rules["productos.{$index}.seriales"] = ['required', 'array', 'size:' . $requiredSize];
            $rules["productos.{$index}.seriales.*"] = 'required|string|max:191|distinct';
        }

        $validated = $request->validate($rules);

        $serialesPorProducto = [];
        foreach ($validated['productos'] as $productoData) {
            $pid = (int) $productoData['id'];
            $seriales = array_map(function ($serie) {
                return trim((string) $serie);
            }, $productoData['seriales'] ?? []);

            $serialesPorProducto[$pid] = $seriales;
        }

        sort($productoIdsEsperados);
        $productoIdsRecibidos = array_keys($serialesPorProducto);
        sort($productoIdsRecibidos);
        if ($productoIdsEsperados !== $productoIdsRecibidos) {
            return redirect()->back()->with('error', 'Debes capturar series para todos los productos que lo requieren.');
        }

        $seriesVendidas = ProductoSerie::where('compra_id', $compra->id)
            ->where('estado', '!=', 'en_stock')
            ->exists();

        if ($seriesVendidas) {
            return redirect()->back()->with('error', 'No se pueden editar las series porque algunas ya fueron usadas (vendidas o dadas de baja).');
        }

        try {
            DB::transaction(function () use ($compra, $serialesPorProducto) {
                foreach ($serialesPorProducto as $productoId => $nuevasSeriales) {
                    $seriesActuales = ProductoSerie::where('compra_id', $compra->id)
                        ->where('producto_id', $productoId)
                        ->get()
                        ->keyBy('numero_serie');

                    $seriesActualesNumeros = $seriesActuales->pluck('numero_serie')->toArray();

                    $seriesAEliminar = array_diff($seriesActualesNumeros, $nuevasSeriales);
                    $seriesAAgregar = array_diff($nuevasSeriales, $seriesActualesNumeros);

                    if (!empty($seriesAEliminar)) {
                        ProductoSerie::where('compra_id', $compra->id)
                            ->where('producto_id', $productoId)
                            ->whereIn('numero_serie', $seriesAEliminar)
                            ->delete();
                    }

                    foreach ($seriesAAgregar as $serie) {
                        $existe = ProductoSerie::where('producto_id', $productoId)
                            ->where('numero_serie', $serie)
                            ->where('compra_id', '!=', $compra->id)
                            ->exists();

                        if ($existe) {
                            throw new \Exception("La serie '{$serie}' ya existe para este producto en otra compra.");
                        }

                        ProductoSerie::create([
                            'producto_id' => $productoId,
                            'compra_id' => $compra->id,
                            'almacen_id' => $compra->almacen_id,
                            'numero_serie' => $serie,
                            'estado' => 'en_stock',
                        ]);
                    }
                }
            });

            return redirect()->back()->with('success', 'Series actualizadas correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar series: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
