<?php

namespace App\Console\Commands;

use App\Services\AlertaMantenimientoService;
use Illuminate\Console\Command;

class EnviarAlertasMantenimiento extends Command
{
    protected $signature = 'mantenimiento:alertas {--dias=30 : Días de anticipación} {--km=500 : Km de anticipación}';

    protected $description = 'Verifica y envía alertas de mantenimientos próximos a vencer.';

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
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
