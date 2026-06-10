<?php

use App\Models\CrmProspecto;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $count = CrmProspecto::whereDate('created_at', date('Y-m-d'))->count();
    $ultimos = CrmProspecto::whereDate('created_at', date('Y-m-d'))
        ->orderBy('created_at', 'desc')
        ->get(['nombre', 'etapa', 'created_at']);
    
    echo "TOTAL HOY: $count\n";
    foreach ($ultimos as $u) {
        echo "- {$u->nombre} | Etapa: {$u->etapa} | Hora: {$u->created_at}\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
