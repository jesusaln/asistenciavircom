<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$total = DB::table('inventarios')->where('producto_id', 42)->sum('cantidad');
DB::table('productos')->where('id', 42)->update(['stock' => $total]);
echo "Stock de producto 42 actualizado a: $total\n";
