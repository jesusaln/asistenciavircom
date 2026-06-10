<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compra = App\Models\Compra::with('compraItems')->where('numero_compra', 'C0010')->first();

if (!$compra) {
    echo "Compra C0010 not found\n";
    exit;
}

echo "Estado: {$compra->estado}\n";
echo "Updated_at: {$compra->updated_at}\n";
foreach ($compra->compraItems as $item) {
    echo "Item {$item->id} prod {$item->comprable_id} qty {$item->cantidad}\n";
}
