<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CuentasPorCobrar;
use Illuminate\Support\Facades\DB;

$search = "MIGUEL";

echo "\n--- SIMULACIÓN DE BÚSQUEDA DEL CONTROLLER ---\n\n";

// Habilitar query log
DB::enableQueryLog();

// Simular exactamente la lógica del controller
$query = CuentasPorCobrar::query()
    ->with(['cobrable', 'cliente'])
    ->where('estado', '!=', 'cancelada');

$query->where(function ($q) use ($search) {
    $q->whereHasMorph('cobrable', '*', function ($sq, $type) use ($search) {
        $sq->whereHas('cliente', function ($cq) use ($search) {
            $cq->where('clientes.nombre_razon_social', 'ilike', "%{$search}%")
                ->orWhere('clientes.rfc', 'ilike', "%{$search}%")
                ->orWhere('clientes.telefono', 'ilike', "%{$search}%");
        });

        $typeLower = strtolower($type);
        if (str_contains($typeLower, 'venta')) {
            $sq->orWhere('numero_venta', 'ilike', "%{$search}%");
        } elseif (str_contains($typeLower, 'renta')) {
            $sq->orWhere('numero_contrato', 'ilike', "%{$search}%");
        } elseif (str_contains($typeLower, 'poliza')) {
            $sq->orWhere('folio', 'ilike', "%{$search}%");
        }
    })
        ->orWhereHas('cliente', function ($cq) use ($search) {
            $cq->where('clientes.nombre_razon_social', 'ilike', "%{$search}%");
        });
});

$results = $query->get();

echo "Resultados encontrados: " . $results->count() . "\n\n";

foreach ($results->take(5) as $cxc) {
    echo "CxC ID: {$cxc->id} | Tipo: {$cxc->cobrable_type} | Monto: {$cxc->monto_total} | Estado: {$cxc->estado}\n";
    if ($cxc->cobrable && $cxc->cobrable->cliente) {
        echo "   -> Cliente: {$cxc->cobrable->cliente->nombre_razon_social}\n";
    }
}

echo "\n--- QUERIES EJECUTADAS ---\n";
$queries = DB::getQueryLog();
foreach ($queries as $q) {
    echo "\n" . $q['query'] . "\n";
    echo "Bindings: " . json_encode($q['bindings']) . "\n";
}

echo "\n--- FIN ---\n";
