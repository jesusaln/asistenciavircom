<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$search = "MIGUEL";

echo "\n--- ANÁLISIS EXHAUSTIVO DE CUENTAS POR COBRAR ---\n";

// 1. LISTAR LAS PRIMERAS CXC DE LA TABLA SIN NINGÚN FILTRO
echo "\n1. Todas las CxC en la tabla (primeras 10):\n";
$todas = DB::table('cuentas_por_cobrar')
    ->whereNull('deleted_at')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'cliente_id', 'cobrable_id', 'cobrable_type', 'empresa_id', 'monto_total', 'estado']);

foreach ($todas as $cxc) {
    echo "   ID: {$cxc->id} | cliente_id: " . ($cxc->cliente_id ?: 'NULL') . " | cobrable_type: {$cxc->cobrable_type} | cobrable_id: {$cxc->cobrable_id} | empresa_id: {$cxc->empresa_id} | estado: {$cxc->estado}\n";
}

// 2. Buscar clientes con MIGUEL
echo "\n2. Buscando clientes con MIGUEL:\n";
$clientes = DB::table('clientes')
    ->whereNull('deleted_at')
    ->whereRaw("nombre_razon_social ILIKE ?", ["%{$search}%"])
    ->get(['id', 'nombre_razon_social', 'empresa_id']);

foreach ($clientes as $c) {
    echo "   Cliente ID: {$c->id} | Nombre: {$c->nombre_razon_social} | empresa_id: {$c->empresa_id}\n";
}

if ($clientes->count() > 0) {
    $clienteIds = $clientes->pluck('id')->toArray();

    // 3. Buscar CxC con cliente_id directo
    echo "\n3. CxC con cliente_id directo de Miguel:\n";
    $cxcDirectas = DB::table('cuentas_por_cobrar')
        ->whereIn('cliente_id', $clienteIds)
        ->whereNull('deleted_at')
        ->get(['id', 'cliente_id', 'cobrable_type', 'cobrable_id', 'monto_total', 'estado']);
    echo "   Encontradas: " . $cxcDirectas->count() . "\n";
    foreach ($cxcDirectas as $cxc) {
        echo "   -> ID: {$cxc->id} | Type: {$cxc->cobrable_type} | Monto: {$cxc->monto_total} | Estado: {$cxc->estado}\n";
    }

    // 4. Buscar Rentas de Miguel
    echo "\n4. Rentas del cliente Miguel:\n";
    $rentas = DB::table('rentas')
        ->whereIn('cliente_id', $clienteIds)
        ->whereNull('deleted_at')
        ->get(['id', 'cliente_id', 'numero_contrato']);
    echo "   Encontradas: " . $rentas->count() . "\n";
    foreach ($rentas as $r) {
        echo "   -> Renta ID: {$r->id} | Contrato: {$r->numero_contrato}\n";

        // Buscar CxC donde cobrable_id = esta renta
        $cxcRenta = DB::table('cuentas_por_cobrar')
            ->where('cobrable_id', $r->id)
            ->whereNull('deleted_at')
            ->get(['id', 'cobrable_type', 'monto_total', 'estado', 'cliente_id']);
        echo "      CxC asociadas a esta renta (por cobrable_id = {$r->id}):\n";
        if ($cxcRenta->count() > 0) {
            foreach ($cxcRenta as $cxc) {
                echo "         -> CxC ID: {$cxc->id} | Type: '{$cxc->cobrable_type}' | cliente_id: " . ($cxc->cliente_id ?: 'NULL') . " | Monto: {$cxc->monto_total} | Estado: {$cxc->estado}\n";
            }
        } else {
            echo "         (NINGUNA)\n";
        }
    }

    // 5. Buscar Ventas de Miguel
    echo "\n5. Ventas del cliente Miguel:\n";
    $ventas = DB::table('ventas')
        ->whereIn('cliente_id', $clienteIds)
        ->whereNull('deleted_at')
        ->get(['id', 'cliente_id', 'numero_venta', 'total']);
    echo "   Encontradas: " . $ventas->count() . "\n";
    foreach ($ventas as $v) {
        echo "   -> Venta ID: {$v->id} | Número: {$v->numero_venta} | Total: {$v->total}\n";

        // Buscar CxC donde cobrable_id = esta venta
        $cxcVenta = DB::table('cuentas_por_cobrar')
            ->where('cobrable_id', $v->id)
            ->whereNull('deleted_at')
            ->get(['id', 'cobrable_type', 'monto_total', 'estado']);
        echo "      CxC asociadas (por cobrable_id = {$v->id}):\n";
        if ($cxcVenta->count() > 0) {
            foreach ($cxcVenta as $cxc) {
                echo "         -> CxC ID: {$cxc->id} | Type: '{$cxc->cobrable_type}' | Monto: {$cxc->monto_total} | Estado: {$cxc->estado}\n";
            }
        } else {
            echo "         (NINGUNA)\n";
        }
    }
}

echo "\n--- FIN ANÁLISIS ---\n";
