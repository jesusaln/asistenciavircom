<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AlertaMantenimientoService;
use Illuminate\Support\Facades\Log;

class VerificarAlertasMantenimiento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mantenimientos:verificar-alertas
                            {--dias=30 : Días de anticipación para enviar alertas}
                            {--force : Forzar envío de alertas incluso si ya fueron enviadas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar y enviar alertas para mantenimientos próximos a vencer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚗 Iniciando verificación de alertas de mantenimiento...');

        try {
            $alertaService = new AlertaMantenimientoService();
            $diasAnticipacion = $this->option('dias');
            $force = $this->option('force');

            // Verificar y enviar alertas
            $this->info("📋 Verificando mantenimientos con {$diasAnticipacion} días de anticipación...");
            $resultado = $alertaService->verificarYEnviarAlertas();

            // Mostrar resultados
            $this->newLine();
            $this->info('📊 Resultados de la verificación:');
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Alertas enviadas', $resultado['alertas_enviadas']],
                    ['Total procesados', $resultado['total_procesados']],
                    ['Errores', count($resultado['errores'])],
                ]
            );

            if (count($resultado['errores']) > 0) {
                $this->warn('⚠️ Errores encontrados:');
                foreach ($resultado['errores'] as $error) {
                    $this->error("ID {$error['mantenimiento_id']}: {$error['error']}");
                }
            }

            // Programar recordatorios automáticos
            $this->newLine();
            $this->info('🔄 Programando recordatorios automáticos...');
            $recordatoriosEnviados = $alertaService->programarRecordatoriosAutomaticos();

            $this->info("✅ {$recordatoriosEnviados} recordatorios automáticos programados");

            // Obtener estadísticas críticas
            $this->newLine();
            $this->info('🚨 Mantenimientos que requieren atención inmediata:');
            $criticos = $alertaService->getMantenimientosCriticos();

            $this->table(
                ['Tipo', 'Cantidad'],
                [
                    ['Críticos (alta prioridad)', $criticos['criticos']->count()],
                    ['Vencidos', $criticos['vencidos']->count()],
                    ['Próximos 3 días', $criticos['proximos_3_dias']->count()],
                ]
            );

            // Log de finalización
            Log::info('Comando verificar-alertas-mantenimiento ejecutado exitosamente', [
                'alertas_enviadas' => $resultado['alertas_enviadas'],
                'recordatorios_enviados' => $recordatoriosEnviados,
                'dias_anticipacion' => $diasAnticipacion,
                'force' => $force
            ]);

            $this->newLine();
            $this->info('✅ Proceso completado exitosamente');

        } catch (\Exception $e) {
            $this->error('❌ Error durante la ejecución: ' . $e->getMessage());
            Log::error('Error en comando verificar-alertas-mantenimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
