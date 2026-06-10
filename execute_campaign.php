<?php

use App\Models\MarketingCampana;
use App\Models\MarketingDestinatario;
use App\Services\Marketing\CampaignService;
use App\Models\Cliente;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $campaniaId = 'c9fafc05-5d4c-477f-89e6-00700743d52f';
    $campania = MarketingCampana::find($campaniaId);
    
    if (!$campania) {
        die("No se encontró la campaña $campaniaId\n");
    }

    // 1. Asegurar que tenemos la imagen y el estado correcto
    $data = $campania->data_plantilla;
    $data['header_image_url'] = 'https://climasdeldesierto.com/storage/marketing/promo_mantenimiento.png';
    $campania->update([
        'data_plantilla' => $data,
        'estado' => 'borrador'
    ]);

    // 2. Agregar todos los clientes válidos a la campaña
    $service = app(CampaignService::class);
    $countAdded = $service->addRecipients($campania);
    echo "Recipientes nuevos agregados: $countAdded\n";

    // 3. Resetear status de todos a pendiente para el envío masivo
    MarketingDestinatario::where('campana_id', $campaniaId)->update([
        'estado' => 'pendiente',
        'error_mensaje' => null
    ]);

    // 4. Iniciar ejecución con el seguro de velocidad (4 seg entre cada uno)
    $service->executeCampaign($campania);
    
    echo "¡Proceso de envío iniciado exitosamente!\n";
    echo "Hora de inicio: " . date('Y-m-d H:i:s') . "\n";
    echo "Los mensajes se están enviando cada 4 segundos para seguridad de tu cuenta.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
