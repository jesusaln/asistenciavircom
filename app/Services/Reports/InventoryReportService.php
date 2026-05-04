<?php

namespace App\Services\Reports;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public function getInventoryData(array $filters): array
    {
        $categoriaId = $filters['categoria_id'] ?? null;
        $marcaId = $filters['marca_id'] ?? null;
        $tipo = $filters['tipo'] ?? 'todos';

        // Fix #49: Seleccionar solo columnas necesarias para evitar hidroración masiva en reportes
        $productosQuery = Producto::select([
            'id',
            'nombre',
            'codigo',
            'codigo_barras',
            'stock',
            'stock_minimo',
            'precio_compra',
            'precio_venta',
            'categoria_id',
            'marca_id',
            'proveedor_id'
        ])->with([
                    'categoria:id,nombre',
                    'marca:id,nombre',
                    'proveedor:id,nombre_razon_social'
                ]);

        if ($categoriaId) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if ($marcaId) {
            $productosQuery->where('marca_id', $marcaId);
        }

        // Apply type filter in SQL
        if ($tipo === 'bajos') {
            $productosQuery->where('stock', '>', 0)->whereColumn('stock', '<=', 'stock_minimo');
        } elseif ($tipo === 'sin_stock') {
            $productosQuery->where('stock', '<=', 0);
        }

        // Stats using distinct queries for efficiency over huge datasets
        // Note: We use a separate query builder for stats to avoid messing with the select/limit of the main list
        $statsQuery = Producto::query();
        if ($categoriaId)
            $statsQuery->where('categoria_id', $categoriaId);
        if ($marcaId)
            $statsQuery->where('marca_id', $marcaId);
        // Note: stats are usually "global" for the current filter context, but ignoring the 'type' filter 
        // allows showing "Total: X, Bajos: Y" even when viewing only "Bajos".
        // However, the original code calculated stats on the FILTERED collection. 
        // Let's assume stats should reflect the CURRENT view + generally useful info.
        // Actually, the original code returned 'total', 'bajos', 'sin_stock' counts based on the 'productos' collection 
        // which was derived from category/mark filters but BEFORE 'tipo' filter (mostly).
        // Wait, original code: 
        // $productos = $productosQuery->get()->map(...);
        // THEN if ($tipo...) filter. 
        // So the stats were calculated on the subset matched by Category/Marca.

        $totalProductos = (clone $statsQuery)->count();
        $productosBajos = (clone $statsQuery)->where('stock', '>', 0)->whereColumn('stock', '<=', 'stock_minimo')->count();
        $productosSinStock = (clone $statsQuery)->where('stock', '<=', 0)->count();

        // Value of inventory for the current filtered view
        // If we apply the 'tipo' filter to the main query, we should probably output the value of THAT selection.
        $valorInventarioQuery = (clone $productosQuery);
        $valorInventario = $valorInventarioQuery->sum(DB::raw('stock * COALESCE(precio_compra, 0)'));

        // Main results with limit
        $productos = $productosQuery
            ->limit(1000) // Explicit limit to prevent OOM
            ->get()
            ->map(function ($producto) {
                return [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'codigo' => $producto->codigo,
                    'categoria' => $producto->categoria?->nombre,
                    'marca' => $producto->marca?->nombre,
                    'proveedor' => $producto->proveedor?->nombre_razon_social,
                    'stock' => $producto->stock,
                    'stock_minimo' => $producto->stock_minimo,
                    'precio_compra' => $producto->precio_compra,
                    'precio_venta' => $producto->precio_venta,
                    'estado' => $this->getStockStatus($producto),
                ];
            });

        $estadisticas = [
            'total_productos' => $totalProductos,
            'productos_bajos' => $productosBajos,
            'productos_sin_stock' => $productosSinStock,
            'valor_inventario' => $valorInventario,
        ];

        return [
            'productos' => $productos,
            'estadisticas' => $estadisticas,
            // Optimizing catalogs: only select needed fields. 
            // In a real scenario with 10k categories, this should be an autocomplete endpoint, not a full dump.
            // But for now, select is better than *
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
            'marcas' => Marca::select('id', 'nombre')->orderBy('nombre')->get(),
            'filtros' => [
                'categoria_id' => $categoriaId,
                'marca_id' => $marcaId,
                'tipo' => $tipo,
            ],
        ];
    }

    private function getStockStatus($producto): string
    {
        if ($producto->stock <= 0)
            return 'sin_stock';
        if ($producto->stock <= $producto->stock_minimo)
            return 'bajo';
        return 'normal';
    }
}
