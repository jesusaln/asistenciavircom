<?php

namespace App\Console\Commands;

use App\Models\PolizaServicio;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerarMantenimientosPoliza extends Command
{
    protected $signature = 'polizas:generar-mantenimientos';

    protected $description = 'Genera tickets de mantenimiento preventivo para pólizas activas según los meses configurados';

    public function handle()
    {
        $this->info('Iniciando generación de mantenimientos preventivos...');

        $categoria = TicketCategory::firstOrCreate(
            ['nombre' => 'Mantenimiento Preventivo'],
            [
                'descripcion' => 'Tickets generados automáticamente por pólizas de servicio',
                'color' => '#10B981',
                'prioridad_default' => 'media',
                'sla_horas' => 48,
            ]
        );

        $polizas = PolizaServicio::where('estado', PolizaServicio::ESTADO_ACTIVA)
            ->whereNotNull('meses_mantenimiento')
            ->where('meses_mantenimiento', '!=', '[]')
            ->get();

        $count = 0;

        foreach ($polizas as $poliza) {
            try {
                $this->procesarPoliza($poliza, $categoria);
                $count++;
            } catch (\Exception $e) {
                $this->error("Error procesando póliza {$poliza->folio}: " . $e->getMessage());
                Log::error("Error generando mantenimiento póliza {$poliza->id}", ['exception' => $e]);
            }
        }

        $this->info("Proceso finalizado. Se procesaron {$count} pólizas.");
    }

    private function procesarPoliza(PolizaServicio $poliza, TicketCategory $categoria)
    {
        $meses = $poliza->meses_mantenimiento;

        if (empty($meses) || !is_array($meses)) {
            return;
        }

        $hoy = Carbon::today();
        $anioActual = $hoy->year;

        foreach ($meses as $mesNumero) {
            $fechaMantenimiento = Carbon::create($anioActual, $mesNumero, 1);

            $dia = min($poliza->mantenimiento_dias_anticipacion ?? 15, $fechaMantenimiento->daysInMonth);
            $fechaMantenimiento->day($dia);

            if ($fechaMantenimiento->isPast()) {
                continue;
            }

            $ticketExiste = Ticket::where('poliza_servicio_id', $poliza->id)
                ->whereMonth('fecha_limite', $mesNumero)
                ->whereYear('fecha_limite', $anioActual)
                ->where('categoria_id', $categoria->id)
                ->exists();

            if ($ticketExiste) {
                $this->line("  [SKIP] {$poliza->folio} - ya hay ticket para {$fechaMantenimiento->format('M Y')}");
                continue;
            }

            $titulo = "Mantenimiento Preventivo {$fechaMantenimiento->format('F Y')} - {$poliza->nombre}";
            $descripcion = "Mantenimiento preventivo correspondiente a {$fechaMantenimiento->format('F Y')}.\n\n"
                . "Generado automáticamente por la Póliza {$poliza->folio}.\n"
                . "Cliente: {$poliza->cliente->nombre_razon_social}\n"
                . "Fecha sugerida: {$fechaMantenimiento->toDateString()}\n"
                . "Meses contratados: " . implode(', ', array_map(fn($m) => Carbon::create()->month($m)->monthName, $meses));

            $ticket = Ticket::create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'cliente_id' => $poliza->cliente_id,
                'user_id' => null,
                'categoria_id' => $categoria->id,
                'prioridad' => 'media',
                'estado' => 'abierto',
                'origen' => 'sistema',
                'poliza_servicio_id' => $poliza->id,
                'fecha_limite' => $fechaMantenimiento,
            ]);

            $poliza->update(['proximo_mantenimiento_at' => $fechaMantenimiento]);

            $this->info("Ticket #{$ticket->id} generado para {$poliza->folio} -> {$fechaMantenimiento->format('d/m/Y')}");
        }
    }
}
