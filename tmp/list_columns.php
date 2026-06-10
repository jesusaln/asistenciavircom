<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cols = Illuminate\Support\Facades\DB::select("
    SELECT column_name, data_type
    FROM information_schema.columns
    WHERE table_name = 'compras'
    ORDER BY ordinal_position
");

foreach ($cols as $c) {
    echo "{$c->column_name} - {$c->data_type}\n";
}
