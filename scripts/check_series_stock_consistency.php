&lt;?php

/**
 * Script de verificación de consistencia entre series y stock
 * 
 * Este script verifica que para productos serializados:
 * 1. El conteo de series en estado 'en_stock' coincida con la suma de inventarios
 * 2. El stock del producto coincida con la suma de inventarios
 * 
 * Uso: php artisan tinker
 * Luego ejecutar: include 'scripts/check_series_stock_consistency.php';
 */

use App\Models\Producto;
use App\Models\ProductoSerie;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

echo "\n=== Verificación de Consistencia Series ↔ Stock ===\n\n";

// Obtener todos los productos que requieren serie
$productosConSerie = Producto::where('requiere_serie', true)->get();

$discrepancias = [];
$totalProductos = $productosConSerie->count();
$productosConProblemas = 0;

foreach ($productosConSerie as $producto) {
    // Contar series en stock
    $seriesEnStock = ProductoSerie::where('producto_id', $producto->id)
        ->where('estado', 'en_stock')
        ->whereNull('deleted_at')
        ->count();
    
    // Sumar inventario total
    $totalInventario = Inventario::where('producto_id', $producto->id)
        ->sum('cantidad');
    
    // Stock del producto
    $stockProducto = $producto->stock ?? 0;
    
    // Verificar consistencia
    $tieneDiscrepancia = false;
    $errores = [];
    
    if ($seriesEnStock != $totalInventario) {
        $tieneDiscrepancia = true;
        $errores[] = sprintf(
            "Series en stock (%d) != Inventario total (%d) [Diferencia: %d]",
            $seriesEnStock,
            $totalInventario,
            $totalInventario - $seriesEnStock
        );
    }
    
    if ($stockProducto != $totalInventario) {
        $tieneDiscrepancia = true;
        $errores[] = sprintf(
            "Stock producto (%d) != Inventario total (%d) [Diferencia: %d]",
            $stockProducto,
            $totalInventario,
            $stockProducto - $totalInventario
        );
    }
    
    if ($seriesEnStock != $stockProducto) {
        $tieneDiscrepancia = true;
        $errores[] = sprintf(
            "Series en stock (%d) != Stock producto (%d) [Diferencia: %d]",
            $seriesEnStock,
            $stockProducto,
            $stockProducto - $seriesEnStock
        );
    }
    
    if ($tieneDiscrepancia) {
        $productosConProblemas++;
        $discrepancias[] = [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'series_en_stock' => $seriesEnStock,
            'inventario_total' => $totalInventario,
            'stock_producto' => $stockProducto,
            'errores' => $errores,
        ];
        
        echo "❌ Producto ID {$producto->id} - {$producto->nombre} ({$producto->codigo})\n";
        foreach ($errores as $error) {
            echo "   • {$error}\n";
        }
        echo "\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Total de productos serializados: {$totalProductos}\n";
echo "Productos con discrepancias: {$productosConProblemas}\n";
echo "Productos consistentes: " . ($totalProductos - $productosConProblemas) . "\n";

if ($productosConProblemas > 0) {
    echo "\n⚠️  Se encontraron {$productosConProblemas} productos con inconsistencias.\n";
    echo "Revisa los detalles arriba para corregir manualmente o ejecutar script de corrección.\n";
} else {
    echo "\n✅ Todos los productos serializados están consistentes.\n";
}

// Opcional: Generar SQL para corrección automática
if ($productosConProblemas > 0) {
    echo "\n=== SQL de Corrección (REVISAR ANTES DE EJECUTAR) ===\n\n";
    
    foreach ($discrepancias as $disc) {
        $productoId = $disc['producto_id'];
        $seriesEnStock = $disc['series_en_stock'];
        
        echo "-- Producto ID {$productoId}: {$disc['nombre']}\n";
        echo "-- Ajustar productos.stock a {$seriesEnStock}\n";
        echo "UPDATE productos SET stock = {$seriesEnStock} WHERE id = {$productoId};\n";
        
        echo "-- Ajustar inventarios para que sumen {$seriesEnStock}\n";
        echo "-- NOTA: Esto requiere revisar manualmente qué almacenes tienen las series\n";
        
        // Obtener distribución real de series por almacén
        $seriesPorAlmacen = DB::table('producto_series')
            ->select('almacen_id', DB::raw('COUNT(*) as cantidad'))
            ->where('producto_id', $productoId)
            ->where('estado', 'en_stock')
            ->whereNull('deleted_at')
            ->groupBy('almacen_id')
            ->get();
        
        foreach ($seriesPorAlmacen as $dist) {
            echo "-- Almacén {$dist->almacen_id}: {$dist->cantidad} series\n";
            echo "UPDATE inventarios SET cantidad = {$dist->cantidad} ";
            echo "WHERE producto_id = {$productoId} AND almacen_id = {$dist->almacen_id};\n";
        }
        
        echo "\n";
    }
}

echo "\n=== Fin de Verificación ===\n";
