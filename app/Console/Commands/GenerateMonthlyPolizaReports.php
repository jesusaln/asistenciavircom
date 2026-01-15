<?php

namespace App\Console\Commands;

use App\Models\PolizaServicio;
use App\Models\Empresa;
use App\Notifications\PolizaReporteMensualNotification;
use App\Http\Controllers\PolizaServicioPDFController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Support\EmpresaResolver;

class GenerateMonthlyPolizaReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:enviar-reportes-mensuales
                            {--mes= : Mes del reporte (1-12)}
                            {--anio= : Año del reporte}
                            {--poliza_id= : ID de póliza específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar y enviar reportes mensuales de desempeño a los clientes con póliza';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mes = $this->option('mes') ?: now()->subMonth()->month;
        $anio = $this->option('anio') ?: now()->subMonth()->year;
        $poliza_id = $this->option('poliza_id');

        $this->info("Iniciando generación de reportes mensuales para {$mes}/{$anio}...");

        $query = PolizaServicio::where('estado', 'activa')
            ->whereHas('cliente', function ($q) {
                $q->whereNotNull('email');
            });

        if ($poliza_id) {
            $query->where('id', $poliza_id);
        }

        $polizas = $query->get();

        if ($polizas->isEmpty()) {
            $this->warn('No se encontraron pólizas activas con clientes con email.');
            return 0;
        }

        $pdfController = new PolizaServicioPDFController();
        $totalEnviados = 0;

        foreach ($polizas as $poliza) {
            try {
                $this->line("Procesando póliza: {$poliza->folio} - {$poliza->cliente->nombre_razon_social}");

                // Establecer contexto de empresa para EmpresaResolver
                EmpresaResolver::setContext($poliza->empresa_id);

                // Generar PDF
                $pdfResponse = $pdfController->reporteMensual($poliza, $mes, $anio);
                $pdfContent = $pdfResponse->output();

                // Enviar notificación
                $poliza->cliente->notify(new PolizaReporteMensualNotification($poliza, $pdfContent, $mes, $anio));

                $totalEnviados++;
                $this->info("  ✓ Reporte enviado correctamente.");

            } catch (\Exception $e) {
                $this->error("  ✗ Error procesando póliza {$poliza->folio}: " . $e->getMessage());
                Log::error("GenerateMonthlyPolizaReports Error: " . $e->getMessage(), [
                    'poliza_id' => $poliza->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("Proceso completado. Reportes enviados: {$totalEnviados}");

        return 0;
    }
}
