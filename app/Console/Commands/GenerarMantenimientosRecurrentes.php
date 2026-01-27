<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PolizaMantenimientoService;
use Illuminate\Support\Facades\Log;

class GenerarMantenimientosRecurrentes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:run-maintenance-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera ejecuciones de mantenimiento basadas en los planes definidos por póliza (checklist, frecuencia, etc)';

    /**
     * Execute the console command.
     */
    public function handle(PolizaMantenimientoService $service)
    {
        $this->info('🚀 Iniciando generación de mantenimientos recurrentes detallados...');

        try {
            $count = $service->generarMantenimientos();

            $this->info("✅ Se generaron {$count} ejecuciones de mantenimiento.");
            Log::info("Comando polizas:run-maintenance-plans finalizado. Generados: {$count}");

            if ($count > 0) {
                $admins = \App\Models\User::role('super-admin')->pluck('email')->toArray();
                if (!empty($admins)) {
                    \Illuminate\Support\Facades\Mail::raw("Se han generado automáticamente {$count} nuevas órdenes de mantenimiento preventivo hoy " . now()->format('d/m/Y') . ".", function ($message) use ($admins) {
                        $message->to($admins)->subject('✅ Mantenimientos Automáticos Generados');
                    });
                    $this->info("📧 Notificación enviada a " . count($admins) . " administradores.");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error en polizas:run-maintenance-plans: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
