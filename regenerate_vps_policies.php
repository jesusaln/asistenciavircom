<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Contab\PolizaContable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$policies = PolizaContable::where('created_at', '>=', '2026-05-22 00:00:00')
    ->where('estado', 'borrador')
    ->get();

echo "Se encontraron " . $policies->count() . " pólizas para regenerar.\n";

if ($policies->isEmpty()) {
    echo "Nada que regenerar.\n";
    exit(0);
}

DB::transaction(function() use ($policies) {
    foreach ($policies as $poliza) {
        echo "Eliminando póliza " . $poliza->numero . " - " . $poliza->concepto . "\n";
        $poliza->asientos()->forceDelete();
        $poliza->forceDelete();
    }
});

echo "Pólizas eliminadas. Ejecutando bulk sync para regenerarlas...\n";
Artisan::call('contab:bulk-sync-cfdis', ['--limit' => 500]);
echo Artisan::output();
echo "\nRegeneración completada.\n";
