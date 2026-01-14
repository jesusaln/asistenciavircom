<?php

namespace App\Services\Reports;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;

class InventoryReportService
{
    public function getInventoryData(array $filters): array
    {
        $categoriaId = $filters['categoria_id'] ?? null;
        $marcaId = $filters['marca_id'] ?? null;
        $tipo = $filters['tipo'] ?? 'todos';

        $productosQuery = Producto::with(['categoria', 'marca', 'proveedor']);

        if ($categoriaId) {
            $productosQuery->where('categoria_id', $categoriaId);
        }

        if ($marcaId) {
            $productosQuery->where('marca_id', $marcaId);
        }

        $productos = $productosQuery->get()->map(function ($producto) {
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

        if ($tipo === 'bajos') {
            $productos = $productos->filter(fn($p) => $p['estado'] === 'bajo');
        } elseif ($tipo === 'sin_stock') {
            $productos = $productos->filter(fn($p) => $p['estado'] === 'sin_stock');
        }

        $estadisticas = [
            'total_productos' => $productos->count(),
            'productos_bajos' => $productos->where('estado', 'bajo')->count(),
            'productos_sin_stock' => $productos->where('estado', 'sin_stock')->count(),
            'valor_inventario' => $productos->sum(fn($p) => $p['stock'] * $p['precio_compra']),
        ];

        return [
            'productos' => $productos->values(),
            'estadisticas' => $estadisticas,
            'categorias' => Categoria::select('id', 'nombre')->get(),
            'marcas' => Marca::select('id', 'nombre')->get(),
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
