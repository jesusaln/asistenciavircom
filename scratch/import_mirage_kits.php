<?php

use App\Models\Producto;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\DB;

$empresaId = EmpresaResolver::resolveId() ?: 1;
$categoriaId = 12; // Aires acondicionados
$marcaId = 2; // MIRAGE

$productos = [
    // X5 ON/OFF
    ['nombre' => 'X5 1 TON Solo Frío 110V', 'codigo' => 'SETCHF120T', 'precio' => 4226.50],
    ['nombre' => 'X5 1 TON Solo Frío 220V', 'codigo' => 'SETCHF121T', 'precio' => 4226.50],
    ['nombre' => 'X5 1.5 TON Solo Frío', 'codigo' => 'SETCHF181T', 'precio' => 7276.00],
    ['nombre' => 'X5 2 TON Solo Frío', 'codigo' => 'SETCHF261T', 'precio' => 9416.00],
    ['nombre' => 'X5 1 TON Solo Frío 110V (C)', 'codigo' => 'SETCHF120C', 'precio' => 4226.50],
    ['nombre' => 'X5 1 TON Solo Frío 220V (C)', 'codigo' => 'SETCHF121C', 'precio' => 4226.50],
    
    // XLIFE
    ['nombre' => 'XLIFE 1 TON Solo Frío 110V', 'codigo' => 'SETCLF120J', 'precio' => 4494.00],
    ['nombre' => 'XLIFE 1 TON Solo Frío 220V', 'codigo' => 'SETCLF121J', 'precio' => 4280.00],
    ['nombre' => 'XLIFE 1.5 TON Solo Frío', 'codigo' => 'SETCLF181J', 'precio' => 8239.00],
    ['nombre' => 'XLIFE 2 TON Solo Frío', 'codigo' => 'SETCLF261J', 'precio' => 10593.00],
    
    // LIFE 12+
    ['nombre' => 'LIFE 12+ 1 TON Solo Frío 110V', 'codigo' => 'SETCLF120T', 'precio' => 5082.50],
    ['nombre' => 'LIFE 12+ 1 TON Solo Frío 220V', 'codigo' => 'SETCLF121T', 'precio' => 4333.50],
    ['nombre' => 'LIFE 12+ 1.5 TON Solo Frío', 'codigo' => 'SETCLF181T', 'precio' => 8292.50],
    ['nombre' => 'LIFE 12+ 2 TON Solo Frío', 'codigo' => 'SETCVF261Q', 'precio' => 10807.00],
    
    // INVERTER V32
    ['nombre' => 'INVERTER V32 1 TON Solo Frío 110V', 'codigo' => 'SETCVF120E', 'precio' => 5510.50],
    ['nombre' => 'INVERTER V32 1 TON Solo Frío 220V', 'codigo' => 'SETCVF121E', 'precio' => 5350.00],
    ['nombre' => 'INVERTER V32 1.5 TON Solo Frío', 'codigo' => 'SETCVF181E', 'precio' => 8292.50],
    ['nombre' => 'INVERTER V32 2 TON Solo Frío', 'codigo' => 'SETCVF261E', 'precio' => 10914.00],
    
    // INVERTER X32
    ['nombre' => 'INVERTER X32 1 TON Solo Frío 110V', 'codigo' => 'SETCWF120E', 'precio' => 5885.00],
    ['nombre' => 'INVERTER X32 1 TON Solo Frío 220V', 'codigo' => 'SETCWF121E', 'precio' => 5671.00],
    ['nombre' => 'INVERTER X32 1.5 TON Solo Frío', 'codigo' => 'SETCWF181E', 'precio' => 8506.50],
    ['nombre' => 'INVERTER X32 2 TON Solo Frío', 'codigo' => 'SETCWF261E', 'precio' => 11235.00],
    
    // INVERTER X
    ['nombre' => 'INVERTER X 1 TON Solo Frío 110V', 'codigo' => 'SETCMF120J/X', 'precio' => 6099.00],
    ['nombre' => 'INVERTER X 1 TON Solo Frío 220V', 'codigo' => 'SETCMF121J/X', 'precio' => 5885.00],
    ['nombre' => 'INVERTER X 1.5 TON Solo Frío', 'codigo' => 'SETCMF181J/X', 'precio' => 8827.50],
    ['nombre' => 'INVERTER X 2 TON Solo Frío', 'codigo' => 'SETCMF261J/X', 'precio' => 11663.00],
    
    // MAGNUM 19
    ['nombre' => 'MAGNUM 19 1 TON Solo Frío 110V', 'codigo' => 'SETCMX090J', 'precio' => 6250.00],
    ['nombre' => 'MAGNUM 19 1 TON Solo Frío 220V', 'codigo' => 'SETCMX120J', 'precio' => 6250.00],
    ['nombre' => 'MAGNUM 19 1.5 TON Solo Frío', 'codigo' => 'SETCMX121J', 'precio' => 8950.00],
    ['nombre' => 'MAGNUM 19 2 TON Solo Frío', 'codigo' => 'SETCMX181J', 'precio' => 12400.00],
    ['nombre' => 'MAGNUM 19 3 TON Solo Frío', 'codigo' => 'SETCMX261J', 'precio' => 18900.00],
];

$count = 0;
foreach ($productos as $p) {
    // Verificar si ya existe para evitar duplicados
    $exists = DB::table('productos')
        ->where('codigo', $p['codigo'])
        ->where('empresa_id', $empresaId)
        ->exists();
        
    if (!$exists) {
        DB::table('productos')->insert([
            'empresa_id' => $empresaId,
            'categoria_id' => $categoriaId,
            'marca_id' => $marcaId,
            'nombre' => $p['nombre'],
            'descripcion' => $p['nombre'] . ' - Equipo Minisplit Mirage',
            'codigo' => $p['codigo'],
            'sku' => $p['codigo'],
            'codigo_barras' => $p['codigo'],
            'precio_compra' => 0,
            'precio_venta' => $p['precio'],
            'stock' => 0,
            'stock_minimo' => 0,
            'stock_cedis' => 0,
            'tipo_producto' => 'kit',
            'estado' => 'activo',
            'unidad_medida' => 'SET',
            'incluye_iva' => true,
            'catalogo_web' => true,
            'sat_objeto_imp' => '02',
            'origen' => 'local',
            'margen_ganancia' => 0,
            'comision_vendedor' => 0,
            'expires' => false,
            'requiere_serie' => false,
            'maneja_series' => false,
            'dias_garantia' => 365,
            'destacado' => false,
            'bloquear_venta_directa' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $count++;
    }
}

echo "Se agregaron {$count} nuevos equipos como Kits.";
