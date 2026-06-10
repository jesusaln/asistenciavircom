<?php

use App\Models\Cliente;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Buscar clientes llamados MOISES creados hoy
    $clientes = Cliente::where('nombre_razon_social', 'LIKE', '%MOISES%')
        ->whereDate('created_at', date('Y-m-d'))
        ->get();
    
    $count = $clientes->count();
    
    foreach ($clientes as $cliente) {
        $cliente->forceDelete(); // Usar forceDelete si tienen softdeletes para limpiar bien
    }
    
    echo "¡Limpieza completada! Se eliminaron permanentemente $count registros de prueba de MOISES.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
