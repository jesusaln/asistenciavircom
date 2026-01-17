<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CuentasPorCobrar;
use App\Models\Cliente;
use App\Models\Venta;
use App\Models\Renta;
use App\Models\PolizaServicio;
use Illuminate\Support\Facades\DB;

$search = "MIGUEL";

echo "\n--- DIAGNÓSTICO DE BÚSQUEDA: '$search' ---\n";

// 1. Buscar Cliente (incluyendo eliminados)
$clientes = Cliente::withTrashed()->where('nombre_razon_social', 'ilike', "%{$search}%")->get();
echo "1. Clientes encontrados con ilike '%$search%' (incluyendo eliminados): " . $clientes->count() . "\n";

if ($clientes->count() > 0) {
    foreach ($clientes as $c) {
        $estado = $c->deleted_at ? "[ELIMINADO]" : "[ACTIVO]";
        echo "   [ID: {$c->id}] {$c->nombre_razon_social} $estado\n";

        // Ventas
        $ventas_count = Venta::where('cliente_id', $c->id)->count();
        echo "      -> Ventas: $ventas_count\n";

        // Rentas
        // Check if Renta model exists appropriately
        if (class_exists(Renta::class)) {
            $rentas_count = Renta::where('cliente_id', $c->id)->count();
            echo "      -> Rentas: $rentas_count\n";
        }

        // Polizas
        if (class_exists(PolizaServicio::class)) {
            $polizas_count = PolizaServicio::where('cliente_id', $c->id)->count();
            echo "      -> Polizas: $polizas_count\n";
        }

        // CxC directas
        $cxc_directas = CuentasPorCobrar::where('cliente_id', $c->id)->count();
        echo "      -> CxC con cliente_id directo: $cxc_directas\n";

        // Búsqueda de CxC via polimorfismo
        $ids = [$c->id];
        $cxc_poly = CuentasPorCobrar::whereHasMorph('cobrable', '*', function ($q, $type) use ($ids) {
            if (method_exists($type, 'cliente')) { // Ojo: esto evalua la clase estática, method_exists necesita objeto o string class.
                // Mejor usar whereHas si Laravel lo permite en closure morph type
                // Pero simplifiquemos:
                $q->whereIn('cliente_id', $ids);
            }
        })->count();
        // Nota: whereHasMorph con '*' y whereIn('cliente_id') asume que todos los cobrables tienen columna cliente_id
        // Venta, Renta, Poliza la tienen.

        echo "      -> CxC detectadas via cobrable->cliente_id: $cxc_poly\n";
    }
} else {
    echo "   (No se encontraron clientes)\n";
}

echo "\n--- FIN DIAGNÓSTICO ---\n";
