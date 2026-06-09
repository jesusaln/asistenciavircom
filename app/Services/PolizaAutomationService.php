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
     * Procesar pólizas próximas a vencer y enviar notificaciones.
     * FASE 1 - Mejora 1.4: Ahora maneja el periodo de gracia antes de marcar como vencida.
     * Se ejecuta diariamente.
     */
    public function processExpiringPolicies()
    {
        Log::info("PolizaAutomationService: Checking expiring policies...");

        // Umbrales de notificación (días antes de vencer)
        $thresholds = [30, 15, 7, 3, 1];

        // =====================================================
        // FASE 1 - Mejora 1.4: Procesar periodo de gracia
        // =====================================================

        // 1. Primero: Marcar como "vencida_en_gracia" las que pasaron su fecha_fin pero están en gracia
        $polizasParaGracia = PolizaServicio::where('estado', 'activa')
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '<', Carbon::today()) // Ya pasó la fecha de vencimiento
            ->get();

        foreach ($polizasParaGracia as $poliza) {
            $poliza->update(['estado' => PolizaServicio::ESTADO_VENCIDA_EN_GRACIA]);
            Log::info("Póliza {$poliza->folio} entró en periodo de gracia", [
                'fecha_fin' => $poliza->fecha_fin->format('Y-m-d'),
                'dias_gracia' => $poliza->dias_gracia ?? 5,
            ]);
        }

        // 2. Segundo: Marcar como "vencida" definitivamente las que pasaron el periodo de gracia
        $polizasVencidas = PolizaServicio::where('estado', PolizaServicio::ESTADO_VENCIDA_EN_GRACIA)
            ->whereNotNull('fecha_fin')
            ->get()
            ->filter(function ($poliza) {
                // Verificar si ya pasó el periodo de gracia
                return !$poliza->estaEnPeriodoGracia();
            });

        foreach ($polizasVencidas as $poliza) {
            $poliza->update(['estado' => PolizaServicio::ESTADO_VENCIDA]);
            Log::warning("Póliza {$poliza->folio} marcada como VENCIDA (terminó periodo de gracia)", [
                'cliente' => $poliza->cliente?->nombre_razon_social,
            ]);
        }

        // =====================================================
        // Proceso original: Enviar alertas de vencimiento próximo
        // =====================================================
        $polizas = PolizaServicio::whereIn('estado', ['activa', 'vencida_en_gracia'])
            ->whereNotNull('fecha_fin')
            ->where('fecha_fin', '>=', Carbon::today()->subDays(5)) // Incluir las que están en gracia
            ->where('fecha_fin', '<=', Carbon::today()->addDays(31))
            ->with(['cliente', 'empresa'])
            ->get();

        $count = 0;

        foreach ($polizas as $poliza) {
            $diasRestantes = $poliza->dias_para_vencer;

            // Verificar si cae en uno de los umbrales
            if (in_array($diasRestantes, $thresholds)) {

                // Evitar duplicados el mismo día
                if (
                    $poliza->ultimo_aviso_vencimiento_at &&
                    $poliza->ultimo_aviso_vencimiento_at->isSameDay(Carbon::today())
                ) {
                    continue;
                }

                $this->sendVencimientoNotification($poliza, $diasRestantes);
                $count++;
            }
            // Notificar el día del vencimiento (0 días)
            elseif ($diasRestantes === 0) {
                if (
                    $poliza->ultimo_aviso_vencimiento_at &&
                    $poliza->ultimo_aviso_vencimiento_at->isSameDay(Carbon::today())
                ) {
                    continue;
                }
                $this->sendVencimientoNotification($poliza, 0);
                $count++;
            }
            // NUEVO: Notificar días de gracia restantes (valores negativos)
            elseif ($diasRestantes < 0 && $diasRestantes >= -5) {
                // Está en periodo de gracia, notificar
                if (
                    $poliza->ultimo_aviso_vencimiento_at &&
                    $poliza->ultimo_aviso_vencimiento_at->isSameDay(Carbon::today())
                ) {
                    continue;
                }
                $this->sendVencimientoNotification($poliza, $diasRestantes);
                $count++;
            }
        }

        Log::info("PolizaAutomationService: Processed expiring policies. Notifications sent: $count");
    }

    /**
     * Enviar notificación de vencimiento vía Email y WhatsApp
     */
    protected function sendVencimientoNotification(PolizaServicio $poliza, int $diasRestantes)
    {
        try {
            DB::transaction(function () use ($poliza, $diasRestantes) {

                // 1. Email Notification
                if ($poliza->cliente && $poliza->cliente->email) {
                    // $poliza->cliente->notify(new \App\Notifications\PolizaVencimientoNotification($poliza));
                    Log::info("Email notification would be sent for Poliza #{$poliza->id} (Expiring in $diasRestantes days)");
                }

                // 2. WhatsApp Notification
                $empresa = \App\Models\Empresa::find($poliza->empresa_id);
                if ($empresa && $empresa->whatsapp_enabled && $poliza->cliente && $poliza->cliente->telefono) {

                    $template = 'aviso_vencimiento_poliza'; // Template específico en Meta

                    // Variables para el template: {{1}}=NombreCliente, {{2}}=Folio, {{3}}=FechaVencimiento, {{4}}=DiasRestantes
                    $params = [
                        $poliza->cliente->nombre_razon_social,
                        $poliza->folio,
                        Carbon::parse($poliza->fecha_fin)->format('d/m/Y'),
                        (string) $diasRestantes
                    ];

                    \App\Jobs\SendWhatsAppTemplate::dispatch(
                        $empresa->id,
                        $poliza->cliente->telefono,
                        $template,
                        $empresa->whatsapp_default_language ?? 'es_MX',
                        $params,
                        [
                            'tipo' => 'vencimiento_poliza',
                            'poliza_id' => $poliza->id,
                            'dias_restantes' => $diasRestantes
                        ]
                    );

                    Log::info("PolizaAutomationService: WhatsApp expiration warning queued for Poliza #{$poliza->id}");
                }

                // Actualizar timestamp
                $poliza->update(['ultimo_aviso_vencimiento_at' => now()]);
            });

        } catch (\Exception $e) {
            Log::error("Error sending expiration notification for Poliza #{$poliza->id}: " . $e->getMessage());
        }
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
