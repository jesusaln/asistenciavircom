<?php

/**
 * Script de corrección de stock para productos serializados
 * 
 * Este script recalcula el stock basándose en el conteo real de series en estado 'en_stock'
 * y ajusta tanto la tabla inventarios como productos.stock
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: include 'scripts/fix_series_stock.php';
 */

use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

echo "\n=== Corrección de Stock basado en Series ===\n\n";

// Obtener todos los productos que requieren serie
$productosConSerie = Producto::where('requiere_serie', true)->get();

$totalProductos = $productosConSerie->count();
$productosCorregidos = 0;
$cambios = [];

echo "Analizando {$totalProductos} productos serializados...\n\n";

DB::transaction(function () use ($productosConSerie, &$productosCorregidos, &$cambios) {
    foreach ($productosConSerie as $producto) {
        // Obtener distribución real de series por almacén
        $seriesPorAlmacen = DB::table('producto_series')
            ->select('almacen_id', DB::raw('COUNT(*) as cantidad'))
            ->where('producto_id', $producto->id)
            ->where('estado', 'en_stock')
            ->whereNull('deleted_at')
            ->groupBy('almacen_id')
            ->get()
            ->keyBy('almacen_id');
        
        // Calcular total de series en stock
        $totalSeriesEnStock = $seriesPorAlmacen->sum('cantidad');
        
        // Obtener inventarios actuales
        $inventariosActuales = Inventario::where('producto_id', $producto->id)->get();
        
        $cambiosProducto = [];
        $necesitaCorreccion = false;
        
        // Verificar cada almacén con inventario
        foreach ($inventariosActuales as $inventario) {
            $cantidadReal = $seriesPorAlmacen->get($inventario->almacen_id)->cantidad ?? 0;
            
            if ($inventario->cantidad != $cantidadReal) {
                $necesitaCorreccion = true;
                $cambiosProducto[] = [
                    'almacen_id' => $inventario->almacen_id,
                    'almacen_nombre' => $inventario->almacen->nombre ?? "Almacén {$inventario->almacen_id}",
                    'cantidad_anterior' => $inventario->cantidad,
                    'cantidad_correcta' => $cantidadReal,
                    'diferencia' => $cantidadReal - $inventario->cantidad,
                ];
                
                // Actualizar inventario
                $inventario->update(['cantidad' => $cantidadReal]);
            }
        }
        
        // Verificar si hay almacenes con series pero sin registro de inventario
        foreach ($seriesPorAlmacen as $almacenId => $data) {
            $existeInventario = $inventariosActuales->where('almacen_id', $almacenId)->first();
            if (!$existeInventario) {
                $necesitaCorreccion = true;
                $almacen = \App\Models\Almacen::find($almacenId);
                $cambiosProducto[] = [
                    'almacen_id' => $almacenId,
                    'almacen_nombre' => $almacen->nombre ?? "Almacén {$almacenId}",
                    'cantidad_anterior' => 0,
                    'cantidad_correcta' => $data->cantidad,
                    'diferencia' => $data->cantidad,
                    'nuevo' => true,
                ];
                
                // Crear inventario
                Inventario::create([
                    'producto_id' => $producto->id,
                    'almacen_id' => $almacenId,
                    'cantidad' => $data->cantidad,
                    'stock_minimo' => 0,
                ]);
            }
        }
        
        // Actualizar stock del producto
        if ($producto->stock != $totalSeriesEnStock) {
            $necesitaCorreccion = true;
            $cambiosProducto[] = [
                'tipo' => 'stock_producto',
                'cantidad_anterior' => $producto->stock,
                'cantidad_correcta' => $totalSeriesEnStock,
                'diferencia' => $totalSeriesEnStock - $producto->stock,
            ];
            
            $producto->update(['stock' => $totalSeriesEnStock]);
        }
        
        if ($necesitaCorreccion) {
            $productosCorregidos++;
            $cambios[$producto->id] = [
                'producto' => $producto->nombre,
                'codigo' => $producto->codigo,
                'cambios' => $cambiosProducto,
            ];
        }
    }
});

echo "\n=== Resultados ===\n";
echo "Total de productos analizados: {$totalProductos}\n";
echo "Productos corregidos: {$productosCorregidos}\n";
echo "Productos sin cambios: " . ($totalProductos - $productosCorregidos) . "\n\n";

if ($productosCorregidos > 0) {
    echo "=== Detalle de Correcciones ===\n\n";
    
    foreach ($cambios as $productoId => $info) {
        echo "📦 Producto ID {$productoId}: {$info['producto']} ({$info['codigo']})\n";
        
        foreach ($info['cambios'] as $cambio) {
            if (isset($cambio['tipo']) && $cambio['tipo'] === 'stock_producto') {
                echo "   ├─ Stock producto: {$cambio['cantidad_anterior']} → {$cambio['cantidad_correcta']} ";
                echo "(" . ($cambio['diferencia'] > 0 ? '+' : '') . "{$cambio['diferencia']})\n";
            } else {
                $nuevo = isset($cambio['nuevo']) ? ' [NUEVO]' : '';
                echo "   ├─ {$cambio['almacen_nombre']}{$nuevo}: {$cambio['cantidad_anterior']} → {$cambio['cantidad_correcta']} ";
                echo "(" . ($cambio['diferencia'] > 0 ? '+' : '') . "{$cambio['diferencia']})\n";
            }
        }
        echo "\n";
    }
    
    echo "✅ Todos los cambios han sido aplicados en la base de datos.\n";
} else {
    echo "✅ No se encontraron inconsistencias. Todos los productos están correctos.\n";
}

echo "\n=== Fin de Corrección ===\n";
