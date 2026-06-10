<?php
$clientes = \App\Models\Cliente::where('nombre_razon_social', 'like', '%SHEYLA%')->withCount('citas')->get();
$keep = null;
$delete = [];

foreach($clientes as $cliente) {
    if (!$keep) {
        $keep = $cliente;
        continue;
    }
    
    // Si el actual tiene más citas, nos quedamos con el actual
    // En caso de empate, nos quedamos con el más antiguo (id menor)
    if ($cliente->citas_count > $keep->citas_count || 
       ($cliente->citas_count == $keep->citas_count && $cliente->id < $keep->id)) {
        $delete[] = $keep;
        $keep = $cliente;
    } else {
        $delete[] = $cliente;
    }
}

if ($keep && count($clientes) > 0) {
    echo "Manteniendo cliente con ID: " . $keep->id . "\n";
    foreach($delete as $d) {
        echo "Moviendo datos y eliminando duplicado ID: " . $d->id . "\n";
        \App\Models\Cita::where('cliente_id', $d->id)->update(['cliente_id' => $keep->id]);
        \App\Models\Ticket::where('cliente_id', $d->id)->update(['cliente_id' => $keep->id]);
        \App\Models\PolizaServicio::where('cliente_id', $d->id)->update(['cliente_id' => $keep->id]);
        $d->delete();
    }
    echo "Proceso completado.\n";
} else {
    echo "No hay clientes SHEYLA duplicados.\n";
}
