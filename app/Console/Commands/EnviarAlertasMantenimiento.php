<?php

namespace App\Console\Commands;

use App\Services\AlertaMantenimientoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnviarAlertasMantenimiento extends Command
{
    protected $signature = 'mantenimiento:alertas {--dias=30 : Días de anticipación} {--km=500 : Km de anticipación}';

    protected $description = 'Verifica y envía alertas de mantenimientos próximos a vencer, programa recordatorios.';

    public function handle(AlertaMantenimientoService $service)
    {
        $dias = (int) $this->option('dias');
        $km = (int) $this->option('km');

        $this->info("Procesando alertas (anticipación: {$dias} días / {$km} km)...");

        $resultado = $service->verificarYEnviarAlertas($dias, $km);

        $this->info("Alertas enviadas: {$resultado['alertas_enviadas']}");
        $this->info("Total procesados: {$resultado['total_procesados']}");

        if (!empty($resultado['errores'])) {
            $this->warn('Se presentaron errores en algunos envíos:');
            foreach ($resultado['errores'] as $error) {
                $this->line("- Mantenimiento {$error['mantenimiento_id']}: {$error['error']}");
            }
        }

        $recordatorios = $service->programarRecordatoriosAutomaticos();
        $this->info("Recordatorios automáticos programados: {$recordatorios}");

        $criticos = $service->getMantenimientosCriticos();
        $this->info("Críticos: {$criticos['criticos']->count()} | Vencidos: {$criticos['vencidos']->count()} | Próximos 3 días: {$criticos['proximos_3_dias']->count()}");

        Log::info('Comando mantenimiento:alertas ejecutado', [
            'alertas_enviadas' => $resultado['alertas_enviadas'],
            'recordatorios' => $recordatorios,
            'dias' => $dias,
            'km' => $km,
        ]);

        if (!empty($resultado['errores'])) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
