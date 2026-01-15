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

        // Buscamos pólizas activas donde la fecha de mantenimiento esté cerca
        // (considerando los días de anticipación configurados o 7 por defecto)
        $polizas = PolizaServicio::activa()
            ->whereNotNull('proximo_mantenimiento_at')
            ->where('generar_cita_automatica', true)
            ->whereRaw("proximo_mantenimiento_at - INTERVAL '1 day' * COALESCE(mantenimiento_dias_anticipacion, 7) <= ?", [$today])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tickets')
                    ->whereColumn('tickets.poliza_id', 'polizas_servicio.id')
                    ->where('tickets.titulo', 'ILIKE', 'Mantenimiento Preventivo %')
                    ->whereIn('tickets.estado', ['abierto', 'en_progreso', 'pendiente']);
            })
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

                // 4. Send Email Notification
                try {
                    if ($poliza->cliente && $poliza->cliente->email) {
                        $poliza->cliente->notify(new \App\Notifications\PolizaMantenimientoNotification($poliza, $ticket, $cita));
                    }
                } catch (\Exception $ne) {
                    Log::error("PolizaAutomationService Email Notification Error: " . $ne->getMessage());
                }

                // 5. Send WhatsApp Notification
                try {
                    $empresa = \App\Models\Empresa::find($poliza->empresa_id);
                    if ($empresa && $empresa->whatsapp_enabled && $poliza->cliente && $poliza->cliente->telefono) {
                        $template = $empresa->whatsapp_template_maintenance ?? 'aviso_mantenimiento_poliza';

                        \App\Jobs\SendWhatsAppTemplate::dispatch(
                            $empresa->id,
                            $poliza->cliente->telefono,
                            $template,
                            $empresa->whatsapp_default_language ?? 'es_MX',
                            [
                                $poliza->cliente->nombre_razon_social,
                                Carbon::parse($poliza->proximo_mantenimiento_at)->format('d/m/Y'),
                                $poliza->folio
                            ],
                            [
                                'tipo' => 'mantenimiento_poliza',
                                'poliza_id' => $poliza->id,
                                'ticket_id' => $ticket->id
                            ]
                        );
                        Log::info("PolizaAutomationService: WhatsApp notification queued for Poliza #{$poliza->id}");
                    }
                } catch (\Exception $we) {
                    Log::error("PolizaAutomationService WhatsApp Notification Error: " . $we->getMessage());
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
