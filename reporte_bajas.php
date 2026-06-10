<?php

use App\Models\Cliente;
use App\Models\CrmProspecto;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // 1. Clientes con flag de opt-out
    $clientes = Cliente::whereNotNull('opt_out_at')->get();
    
    // 2. Prospectos con la nota de baja
    $prospectos = CrmProspecto::where('notas', 'LIKE', '%SOLICITÓ BAJA POR WHATSAPP%')->get();

    if ($clientes->isEmpty() && $prospectos->isEmpty()) {
        echo "Afortunadamente, NADIE se ha dado de baja todavía. Tus clientes están contentos con la información.\n";
    } else {
        echo "=== LISTA DE PERSONAS QUE SOLICITARON BAJA ===\n";
        foreach ($clientes as $c) {
            echo "🔴 CLIENTE: {$c->nombre_razon_social} ({$c->telefono}) - Fecha: {$c->opt_out_at}\n";
        }
        foreach ($prospectos as $p) {
            echo "🟠 PROSPECTO: {$p->nombre} ({$p->telefono})\n";
        }
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
