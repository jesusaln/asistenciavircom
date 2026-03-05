<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = ['users', 'empresas', 'asistencia_registros', 'prestamos', 'clientes', 'productos', 'servicios'];

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "Table: $table\n";
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            echo "  - $column\n";
        }
    } else {
        echo "Table: $table DOES NOT EXIST\n";
    }
    echo "\n";
}
