<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $p = App\Models\PolizaServicio::find(14);
    if ($p) {
        echo "Poliza encontrada: " . $p->id . "\n";
        echo "Cliente ID: " . $p->cliente_id . "\n";
        $p->load('cliente');
        if ($p->cliente) {
            echo "Cliente Relation: FOUND - " . $p->cliente->nombre_razon_social . "\n";
            echo "Cliente Object ID: " . $p->cliente->id . "\n";
        } else {
            echo "Cliente Relation: NULL\n";
            $client = App\Models\Cliente::withTrashed()->find($p->cliente_id);
            if ($client) {
                echo "Cliente existe pero esta SOFT DELETED.\n";
            } else {
                echo "Cliente ID " . $p->cliente_id . " NO existe en BD.\n";
            }
        }
    } else {
        echo "Poliza 8 no encontrada\n";
        $any = App\Models\PolizaServicio::first();
        if ($any)
            echo "Primera poliza disponible ID: " . $any->id . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
