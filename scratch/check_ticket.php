<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\Cliente;
use App\Models\PolizaServicio;

$ticket = Ticket::where('folio', 'T020')->orWhere('id', 20)->first();
if (!$ticket) {
    echo "Ticket no encontrado.\n";
    exit;
}

echo "Ticket: #{$ticket->folio} (ID: {$ticket->id})\n";
echo "Titulo: {$ticket->titulo}\n";
echo "Tipo de Servicio: {$ticket->tipo_servicio}\n";
echo "Poliza ID en Ticket: " . ($ticket->poliza_id ?? 'Nulo') . "\n";
echo "Cliente ID: " . ($ticket->cliente_id ?? 'Nulo') . "\n";

if ($ticket->cliente_id) {
    $cliente = Cliente::find($ticket->cliente_id);
    echo "Cliente: {$cliente->nombre_razon_social}\n";
    
    $polizas = PolizaServicio::where('cliente_id', $ticket->cliente_id)->get();
    echo "Polizas encontradas para el cliente: " . $polizas->count() . "\n";
    foreach ($polizas as $poliza) {
        echo " - Poliza ID: {$poliza->id}, Folio: {$poliza->folio}, Nombre: {$poliza->nombre}, Estado: {$poliza->estado}, Fecha Fin: " . ($poliza->fecha_fin ? $poliza->fecha_fin->format('Y-m-d') : 'N/A') . "\n";
    }
}
