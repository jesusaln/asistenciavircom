<?php

use App\Models\Empresa;
use App\Http\Controllers\WhatsAppWebhookController;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Establecer contexto de empresa
    \App\Support\EmpresaResolver::setContext(1);

    $data = [
        'from' => '526622123456',
        'text' => ['body' => 'Hola, me interesa el servicio de limpieza de minisplit (TEST SCRIPT)'],
        'id' => 'wamid.TEST_ID_' . time()
    ];

    $controller = new WhatsAppWebhookController();
    $method = new ReflectionMethod($controller, 'processIncomingMessage');
    $method->setAccessible(true);
    $method->invoke($controller, $data);

    echo "¡ÉXITO! Se simuló el mensaje.\n";
    
    // Verificar si se creó el prospecto
    $prospecto = \App\Models\CrmProspecto::where('telefono', '6622123456')->first();
    if ($prospecto) {
        echo "✅ PROSPECTO ENCONTRADO: {$prospecto->nombre}\n";
        echo "📝 NOTAS: {$prospecto->notas}\n";
    } else {
        echo "❌ PROSPECTO NO ENCONTRADO.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
