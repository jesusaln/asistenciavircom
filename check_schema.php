<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['cuentas_por_pagar', 'productos', 'clientes', 'empresas', 'users'];

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "Table $table DOES NOT EXIST\n";
        continue;
    }
    echo "Table $table exists. Checking columns...\n";
    $columns = Schema::getColumnListing($table);
    sort($columns);
    echo "Columns: " . implode(', ', $columns) . "\n\n";
}

// Check some specific missing columns mentioned in logs
$specific = [
    ['table' => 'cuentas_por_pagar', 'column' => 'saldo_favor_generado'],
    ['table' => 'cuentas_por_pagar', 'column' => 'saldo_favor_utilizado'],
    ['table' => 'productos', 'column' => 'destacado'],
    ['table' => 'productos', 'column' => 'catalogo_web'],
    ['table' => 'clientes', 'column' => 'destacado'],
    ['table' => 'clientes', 'column' => 'owned_by'],
    ['table' => 'empresas', 'column' => 'nombre_razon_social'],
];

foreach ($specific as $item) {
    $table = $item['table'];
    $column = $item['column'];
    $exists = Schema::hasColumn($table, $column) ? "YES" : "NO";
    echo "Checking $table.$column: $exists\n";
}
