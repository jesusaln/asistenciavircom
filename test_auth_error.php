<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;

try {
    $empresaId = EmpresaResolver::resolveId();
    echo "Empresa ID: $empresaId\n";
    
    $empresaModel = Empresa::find($empresaId);
    if ($empresaModel) {
        echo "Empresa found. Calling toArray()...\n";
        $empresaModel->toArray();
        echo "toArray() OK\n";
    } else {
        echo "Empresa NOT found (ID: $empresaId)\n";
    }
    
    $configuracion = EmpresaConfiguracion::getConfig($empresaId);
    if ($configuracion) {
        echo "Configuracion found. Calling toArray()...\n";
        $configuracion->toArray();
        echo "Configuracion toArray() OK\n";
    } else {
        echo "Configuracion NOT found\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
