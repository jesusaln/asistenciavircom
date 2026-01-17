<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\CuentasPorCobrar;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

$search = "MIGUEL";

echo "\n--- DIAGNÓSTICO DE BÚSQUEDA: '$search' ---\n";

// 1. Buscar Cliente
$clientes = Cliente::where('nombre_razon_social', 'ilike', "%{$search}%")->get();
echo "1. Clientes encontrados con ilike '%$search%': " . $clientes->count() . "\n";

if ($clientes->count() > 0) {
    foreach ($clientes as $c) {
        echo "   [ID: {$c->id}] {$c->nombre_razon_social}\n";

        // 2. Ventas del cliente
        $ventas_count = Venta::where('cliente_id', $c->id)->count();
        echo "      -> Ventas asociadas: $ventas_count\n";

        // 3. CxC directas
        $cxc_directas = CuentasPorCobrar::where('cliente_id', $c->id)->count();
        echo "      -> CxC con cliente_id directo: $cxc_directas\n";

        // 4. CxC via Venta (Relación cobrable)
        // Buscamos CxC donde cobrable_type sea Venta y cobrable_id sea una venta de este cliente
        $ventas_ids = Venta::where('cliente_id', $c->id)->pluck('id');
        if ($ventas_ids->count() > 0) {
            $cxc_via_venta = CuentasPorCobrar::whereIn('cobrable_id', $ventas_ids)
                ->where(function ($q) {
                    $q->where('cobrable_type', 'venta')
                        ->orWhere('cobrable_type', 'App\Models\Venta')
                        ->orWhere('cobrable_type', Venta::class);
                })
                ->get();

            echo "      -> CxC vinculadas a sus ventas: " . $cxc_via_venta->count() . "\n";
            if ($cxc_via_venta->count() > 0) {
                foreach ($cxc_via_venta as $cx) {
                    echo "         - CxC ID: {$cx->id}, Type real en DB: '{$cx->cobrable_type}'\n";
                }
            } else {
                echo "         (WARNING: El cliente tiene ventas pero NO tienen CxC asociadas, o el cobrable_type no coincide)\n";
            }
        }
    }
} else {
    echo "   (No se encontraron clientes, el problema es que el nombre no existe o está escrito muy diferente)\n";
    // Check raw sql without ilike just in case
    $raw = Cliente::where('nombre_razon_social', 'like', "%{$search}%")->count();
    echo "   (Prueba con LIKE normal: $raw)\n";
}

echo "\n--- FIN DIAGNÓSTICO ---\n";
