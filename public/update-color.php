<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    Illuminate\Http\Request::capture()
);

use App\Models\EmpresaConfiguracion;

$config = EmpresaConfiguracion::first();
echo "Color actual: " . ($config->color_principal ?? 'no definido') . "\n";

// Actualizar a naranja premium
$config->color_principal = '#FF6B35';
$config->color_secundario = '#E55A2B';
$config->save();

// Limpiar caché
EmpresaConfiguracion::clearCache();

echo "Color actualizado a: #FF6B35 (naranja)\n";
echo "Color secundario: #E55A2B\n";
echo "Caché limpiada.\n";
