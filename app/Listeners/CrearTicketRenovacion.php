<?php

namespace App\Listeners;

use App\Events\PolizaProximaAVencer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CrearTicketRenovacion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PolizaProximaAVencer $event): void
    {
        $poliza = $event->poliza;
        $diasRestantes = $event->diasRestantes;

        Log::info("Intentando crear ticket de renovación para póliza #{$poliza->folio} (vence en {$diasRestantes} días).");

        // Buscar categoría de 'Renovación / Ventas'
        $categoriaRenovacion = TicketCategory::where('nombre', 'Renovación / Ventas')->first();
        
        // Si no existe, usar una categoría genérica o la primera disponible.
        // Opcional: Crear la categoría si no existe. Para este MVP, asumimos que existe o se usa fallback.
        if (!$categoriaRenovacion) {
            $categoriaRenovacion = TicketCategory::first(); // Fallback a la primera categoría
            if (!$categoriaRenovacion) {
                Log::error("No se encontró categoría 'Renovación / Ventas' ni categoría de fallback para crear ticket de póliza #{$poliza->folio}.");
                return;
            }
            Log::warning("No se encontró categoría 'Renovación / Ventas', usando categoría de fallback '{$categoriaRenovacion->nombre}'.");
        }

        // Buscar agente para asignar (primero rol 'ventas', luego 'super-admin')
        $agenteVentas = User::role('ventas')->first();
        if (!$agenteVentas) {
            $agenteVentas = User::role('super-admin')->first();
        }

        if (!$agenteVentas) {
            Log::error("No se encontró ningún agente con rol 'ventas' o 'super-admin' para asignar el ticket de póliza #{$poliza->folio}.");
            return;
        }

        // Crear el ticket
        try {
            $ticket = Ticket::create([
                'empresa_id' => $poliza->empresa_id,
                'cliente_id' => $poliza->cliente_id,
                'poliza_id' => $poliza->id,
                'categoria_id' => $categoriaRenovacion->id,
                'asignado_id' => $agenteVentas->id,
                'titulo' => "Seguimiento de renovación - Póliza #{$poliza->folio}",
                'descripcion' => "La póliza #{$poliza->folio} del cliente {$poliza->cliente->nombre_razon_social} vence en {$diasRestantes} días ({$poliza->fecha_fin->format('d/m/Y')}). Favor de contactar para gestión de renovación.",
                'prioridad' => 'baja', // O 'media' dependiendo de la política
                'estado' => 'abierto',
                'origen' => 'sistema_automatico',
            ]);

            Log::info("Ticket de renovación #{$ticket->folio} creado exitosamente para póliza #{$poliza->folio}, asignado a {$agenteVentas->name}.");
        } catch (\Exception $e) {
            Log::error("Error al crear ticket de renovación para póliza #{$poliza->folio}: " . $e->getMessage());
        }
    }
}
