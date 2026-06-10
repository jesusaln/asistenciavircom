<?php

namespace App\Console\Commands;

use App\Models\Cfdi;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Models\SatDescargaMasiva;
use App\Jobs\SatDescargaMasivaJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SatDailyDownload extends Command
{
    protected $signature = 'sat:daily-download';
    protected $description = 'Solicita automáticamente la descarga de CFDIs del SAT para el día actual y anterior';

    public function handle()
    {
        $this->info("Iniciando solicitud diaria de descarga SAT...");

        // Obtenemos todas las empresas que tengan RFC configurado
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->error("No se encontraron empresas configuradas.");
            return 1;
        }

        $staggerMinutes = 0;
        foreach ($empresas as $empresa) {
            // Establecer contexto para la empresa
            \App\Support\EmpresaResolver::setContext($empresa->id);

            // Verificar si la empresa tiene FIEL configurada
            $config = EmpresaConfiguracion::find($empresa->id);
            if (!$config || empty($config->fiel_cer_path) || empty($config->fiel_key_path) || empty($config->fiel_password)) {
                $this->line("  - [{$empresa->rfc}] Omitiendo por falta de FIEL.");
                continue;
            }

            $this->info("Procesando empresa: {$empresa->nombre_razon_social} ({$empresa->rfc})");

            // Rango: Ayer y hoy para asegurar que no se escape nada por temas de zona horaria o retrasos del SAT
            $inicio = Carbon::now()->subDays(1)->startOfDay();
            $fin = Carbon::now()->endOfDay();

            $this->solicitarDescarga($empresa, $inicio, $fin, Cfdi::DIRECCION_EMITIDO, $staggerMinutes);
            $staggerMinutes += 10;

            $this->solicitarDescarga($empresa, $inicio, $fin, Cfdi::DIRECCION_RECIBIDO, $staggerMinutes);
            $staggerMinutes += 10;
        }

        $this->info("Proceso de solicitud finalizado. Los Jobs se encargarán de la descarga en segundo plano.");
        return 0;
    }

    private function solicitarDescarga(Empresa $empresa, Carbon $inicio, Carbon $fin, string $direccion, int $delayMinutes = 0)
    {
        $tipoLabel = $direccion === Cfdi::DIRECCION_EMITIDO ? 'Emitidos' : 'Recibidos';
        
        // Verificar si ya existe una solicitud para este rango que esté pendiente o completada hoy
        $existe = SatDescargaMasiva::where('direccion', $direccion)
            ->where('fecha_inicio', $inicio->format('Y-m-d'))
            ->where('fecha_fin', $fin->format('Y-m-d'))
            ->whereIn('status', ['pendiente', 'verificando', 'completado'])
            ->where('created_at', '>=', Carbon::today())
            ->exists();

        if ($existe) {
            $this->line("  - [{$tipoLabel}] Ya existe una solicitud reciente para este rango. Omitiendo.");
            return;
        }

        $descarga = SatDescargaMasiva::create([
            'empresa_id' => $empresa->id,
            'direccion' => $direccion,
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
            'status' => 'solicitando',
        ]);

        // Despachamos el Job en modo 'request' con empresa_id y delay de staggering
        SatDescargaMasivaJob::dispatch($descarga->id, 'request', $empresa->id)
            ->delay(now()->addMinutes($delayMinutes));

        $this->info("  - [{$tipoLabel}] Solicitud creada y encolada con retraso de {$delayMinutes}m (ID: {$descarga->id})");
    }
}
