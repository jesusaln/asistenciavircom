<?php

use App\Models\Cliente;
use App\Jobs\SendWhatsAppTemplate;
use App\Models\Empresa;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $nombre = "MOISES RAMIREZ RAMOS";
    $nuevoTelefono = "6635440274";
    
    // 1. Actualizar cliente si existe
    $cliente = Cliente::where('nombre_razon_social', 'LIKE', "%$nombre%")->first();
    if ($cliente) {
        $cliente->update(['telefono' => $nuevoTelefono]);
        echo "Teléfono actualizado para el cliente {$cliente->nombre_razon_social}.\n";
    } else {
        echo "No se encontró el cliente $nombre en la base de datos, procediendo solo con el envío.\n";
    }

    // 2. Enviar mensaje
    $empresa = Empresa::first(); // Generalmente ID 8 en tus pruebas
    SendWhatsAppTemplate::dispatchSync(
        $empresa->id,
        "52$nuevoTelefono",
        'servicios_minisplit',
        'es_MX',
        [$nombre],
        [
            'tipo' => 'manual_promo',
            'header_params' => [
                [
                    'type' => 'image',
                    'image' => ['link' => 'https://climasdeldesierto.com/storage/marketing/promo_mantenimiento.png']
                ]
            ]
        ]
    );

    echo "¡Mensaje de promoción enviado con éxito a Moises ($nuevoTelefono)!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
