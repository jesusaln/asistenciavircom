<?php

namespace App\Console\Commands;

use App\Models\PolizaServicio;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerarMantenimientosPoliza extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:generar-mantenimientos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera tickets de mantenimiento preventivo para pólizas activas según su frecuencia';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando generación de mantenimientos preventivos...');

        // 1. Obtener la categoría de Mantenimiento Preventivo (o crearla si no existe)
        $categoria = TicketCategory::firstOrCreate(
            ['nombre' => 'Mantenimiento Preventivo'],
            [
                'descripcion' => 'Tickets generados automáticamente por pólizas de servicio',
                'color' => '#10B981', // Emerald 500
                'prioridad_default' => 'media',
                'sla_horas' => 48
            ]
        );

        // 2. Obtener pólizas activas con mantenimientos programados
        $polizas = PolizaServicio::where('estado', PolizaServicio::ESTADO_ACTIVA)
            ->where('mantenimientos_anuales', '>', 0)
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
        // Calcular intervalo en meses
        $intervaloMeses = 12 / max(1, $poliza->mantenimientos_anuales);

        // Si no tiene fecha próxima, inicializarla
        if (!$poliza->proximo_mantenimiento_at) {
            // Si la póliza es nueva, el primer mantenimiento es en +intervalo/2 si se desea, o +intervalo.
            // O podemos ponerlo desde la fecha de inicio.
            // Estrategia: Si fecha_inicio + intervalo < hoy, buscar el siguiente slot futuro.

            $fechaBase = $poliza->fecha_inicio ? Carbon::parse($poliza->fecha_inicio) : now();
            $proximo = $fechaBase->copy()->addMonths((int) $intervaloMeses);

            // Si ya pasó, ajustarlo al futuro inmediato
            while ($proximo->isPast()) {
                $proximo->addMonths((int) $intervaloMeses);
            }

            $poliza->update(['proximo_mantenimiento_at' => $proximo]);
            $this->comment("Inicializada fecha próximo mantenimiento para {$poliza->folio}: {$proximo->toDateString()}");
            return;
        }

        $fechaProgramada = Carbon::parse($poliza->proximo_mantenimiento_at);

        // Verificar si es hoy o ya pasó (y no se ha generado)
        // Damos un margen de un par de días hacia atrás por si el cron falló
        if ($fechaProgramada->isToday() || $fechaProgramada->isPast()) {

            // Generar Ticket
            $titulo = "Mantenimiento Preventivo Programado - {$poliza->nombre}";
            $descripcion = "Mantenimiento preventivo correspondiente al periodo. Generado automáticamente por la Póliza {$poliza->folio}. \n\n" .
                "Frecuencia: {$poliza->mantenimientos_anuales} al año.\n" .
                "Fecha programada: {$fechaProgramada->toDateString()}";

            $ticket = Ticket::create([
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'cliente_id' => $poliza->cliente_id,
                'user_id' => null, // Sin asignar aún, o asignar a un coordinador
                'categoria_id' => $categoria->id,
                'prioridad' => 'media',
                'estado' => 'abierto',
                'origen' => 'sistema', // Nuevo origen 'sistema' si existiera, o 'web'
                'poliza_servicio_id' => $poliza->id,
                'fecha_limite' => Carbon::now()->addHours($poliza->sla_horas_resolucion ?? 48), // Usar SLA de la póliza
            ]);

            $this->info("Ticket generado: #{$ticket->id} para Póliza {$poliza->folio}");

            // Calcular y guardar la SIGUIENTE fecha
            $siguienteFecha = $fechaProgramada->copy()->addMonths((int) $intervaloMeses);

            // Si la siguiente fecha calculado sigue siendo pasado (caso extraño de atraso masivo), mover al futuro
            while ($siguienteFecha->isPast()) {
                $siguienteFecha->addMonths((int) $intervaloMeses);
            }

            $poliza->update(['proximo_mantenimiento_at' => $siguienteFecha]);

            // Opcional: Notificar al cliente o admin aquí
        }
    }
}
