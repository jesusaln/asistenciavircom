<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class CheckSlaBreaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica tickets próximos a vencer o vencidos según SLA y envía alertas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando SLAs de tickets...');

        $tickets = Ticket::whereIn('estado', ['abierto', 'en_progreso', 'pendiente'])
            ->whereNotNull('fecha_limite')
            ->get();

        $count = 0;

        foreach ($tickets as $ticket) {
            $now = now();
            $fechaLimite = Carbon::parse($ticket->fecha_limite);

            // 1. Alerta de Vencimiento (Ya venció)
            if ($now->greaterThan($fechaLimite)) {
                if (!Cache::has("sla_breach_alert_{$ticket->id}")) {
                    $this->alertBreach($ticket, $fechaLimite);
                    Cache::put("sla_breach_alert_{$ticket->id}", true, now()->addDays(7));
                    $count++;
                }
            }
            // 2. Alerta de Advertencia (Faltan menos de 2 horas)
            elseif ($fechaLimite->diffInHours($now, true) <= 2) {
                if (!Cache::has("sla_warning_alert_{$ticket->id}")) {
                    $this->alertWarning($ticket, $fechaLimite);
                    Cache::put("sla_warning_alert_{$ticket->id}", true, now()->addDays(2));
                    $count++;
                }
            }
        }

        $this->info("Proceso completado. {$count} alertas enviadas.");
    }

    private function alertBreach(Ticket $ticket, $fechaLimite)
    {
        $this->error("Ticket #{$ticket->folio} ha vencido el SLA.");

        $mensaje = "🚨 *SLA VENCIDO*\nTicket: #{$ticket->folio}\nCliente: {$ticket->cliente->nombre_razon_social}\nLímite: {$fechaLimite->format('d/m/Y H:i')}\nHace: {$fechaLimite->diffForHumans()}";

        // Notificar técnico asignado
        if ($ticket->asignado) {
            // Enviar notificación (Email o Sistema)
            // $ticket->asignado->notify(new SlaBreachNotification($ticket));
            Log::warning("SLA Alert (Breach) sent to " . $ticket->asignado->email);
        }

        // Notificar Admins (Opcional)
        // Log::channel('slack')->critical($mensaje); // Si hubiera slack
        Log::warning($mensaje);
    }

    private function alertWarning(Ticket $ticket, $fechaLimite)
    {
        $horasRestantes = $fechaLimite->diffInHours(now(), true);
        $tiempoRestante = $fechaLimite->diffForHumans();

        $this->comment("Ticket #{$ticket->folio} está próximo a vencer ({$tiempoRestante}).");

        $mensaje = "⚠️ *SLA Próximo a Vencer*\nTicket: #{$ticket->folio}\nVence: {$fechaLimite->format('d/m/Y H:i')}\nRestante: {$tiempoRestante}";

        if ($ticket->asignado) {
            // $ticket->asignado->notify(new SlaWarningNotification($ticket));
            Log::info("SLA Alert (Warning) sent to " . $ticket->asignado->email);
        }
    }
}
