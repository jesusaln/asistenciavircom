<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\Ticket;
use App\Models\Cita;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PolizaAutomationService
{
    /**
     * Process policies that have scheduled maintenance due.
     */
    public function processScheduledMaintenances()
    {
        $today = Carbon::today();

        // We look for policies that have maintenance due today or in the past
        // and are active.
        $polizas = PolizaServicio::activa()
            ->whereNotNull('proximo_mantenimiento_at')
            ->where('proximo_mantenimiento_at', '<=', $today)
            ->where('generar_cita_automatica', true)
            ->with(['cliente', 'empresa'])
            ->get();

        Log::info("PolizaAutomationService: Processing " . $polizas->count() . " policies for maintenance.");

        foreach ($polizas as $poliza) {
            $this->generateMaintenanceService($poliza);
        }
    }

    /**
     * Generate a Ticket and a Cita for a policy maintenance.
     */
    public function generateMaintenanceService(PolizaServicio $poliza)
    {
        return DB::transaction(function () use ($poliza) {
            try {
                // 1. Create the Ticket
                $ticket = Ticket::create([
                    'empresa_id' => $poliza->empresa_id,
                    'cliente_id' => $poliza->cliente_id,
                    'poliza_id' => $poliza->id,
                    'titulo' => "Mantenimiento Preventivo Programado - {$poliza->folio}",
                    'description' => "Servicio de mantenimiento preventivo automático generado según los términos de la póliza {$poliza->folio}.",
                    'prioridad' => 'media',
                    'estado' => 'abierto',
                    'categoria_id' => $this->getMantenimientoCategoryId($poliza->empresa_id),
                ]);

                // 2. Create the Cita
                $cita = Cita::create([
                    'empresa_id' => $poliza->empresa_id,
                    'cliente_id' => $poliza->cliente_id,
                    'ticket_id' => $ticket->id,
                    'tipo_servicio' => 'mantenimiento',
                    'problema_reportado' => "Visita técnica para realizar mantenimiento preventivo de la póliza {$poliza->folio}.",
                    'descripcion' => "Generado automáticamente por sistema.",
                    'fecha_hora' => $poliza->proximo_mantenimiento_at,
                    'estado' => 'programada',
                ]);

                // 3. Update the next maintenance date
                if ($poliza->mantenimiento_frecuencia_meses > 0) {
                    $newDate = Carbon::parse($poliza->proximo_mantenimiento_at)
                        ->addMonths($poliza->mantenimiento_frecuencia_meses);

                    $poliza->update([
                        'proximo_mantenimiento_at' => $newDate,
                    ]);
                } else {
                    // If no frequency, clear the scheduled date
                    $poliza->update([
                        'proximo_mantenimiento_at' => null,
                        'generar_cita_automatica' => false,
                    ]);
                }

                Log::info("PolizaAutomationService: Generated Ticket #{$ticket->id} and Cita #{$cita->id} for Poliza #{$poliza->id}");

                // 4. Send Notification
                try {
                    if ($poliza->cliente && $poliza->cliente->email) {
                        $poliza->cliente->notify(new \App\Notifications\PolizaMantenimientoNotification($poliza, $ticket, $cita));
                    }
                } catch (\Exception $ne) {
                    Log::error("PolizaAutomationService Notification Error: " . $ne->getMessage());
                    // We don't throw here to not rollback the transaction if only notification fails
                }

                return ['ticket' => $ticket, 'cita' => $cita];

            } catch (\Exception $e) {
                Log::error("PolizaAutomationService Error: " . $e->getMessage(), [
                    'poliza_id' => $poliza->id,
                    'context' => 'generateMaintenanceService'
                ]);
                throw $e;
            }
        });
    }

    /**
     * Helper to get or create a "Mantenimiento" category for tickets.
     */
    private function getMantenimientoCategoryId($empresaId)
    {
        $category = \App\Models\TicketCategory::where('empresa_id', $empresaId)
            ->where(function ($q) {
                $q->where('nombre', 'ILIKE', '%mantenimiento%')
                    ->orWhere('nombre', 'ILIKE', '%preventivo%');
            })
            ->first();

        return $category ? $category->id : null;
    }
}
