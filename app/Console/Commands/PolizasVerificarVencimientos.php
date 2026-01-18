<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PolizaServicio;
use App\Events\PolizaProximaAVencer;
use Illuminate\Support\Facades\Log;

class PolizasVerificarVencimientos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:verificar-vencimientos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica las pólizas próximas a vencer y dispara eventos de alerta.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando verificación de pólizas próximas a vencer...');

        $diasAlertas = [30, 15, 7, 3, 1]; // Días restantes para enviar alertas

        foreach ($diasAlertas as $diasRestantes) {
            $fechaLimite = now()->addDays($diasRestantes)->endOfDay();

            $polizas = PolizaServicio::activa()
                ->whereDate('fecha_fin', $fechaLimite)
                ->where(function ($query) use ($diasRestantes) {
                    // Evitar reenviar la misma alerta si ya se envió para este umbral
                    $query->whereNull('ultimo_aviso_vencimiento_at')
                        ->orWhere('ultimo_aviso_vencimiento_at', '<', now()->subDays($diasRestantes)->startOfDay());
                })
                ->get();

            if ($polizas->count() > 0) {
                $this->info("Se encontraron {$polizas->count()} pólizas que vencen en {$diasRestantes} días.");
                foreach ($polizas as $poliza) {
                    PolizaProximaAVencer::dispatch($poliza, $diasRestantes);
                    // Actualizar el campo para no enviar la misma alerta múltiples veces
                    $poliza->update(['ultimo_aviso_vencimiento_at' => now()]);
                    Log::info("Evento PolizaProximaAVencer despachado para póliza #{$poliza->folio} (vence en {$diasRestantes} días).");
                }
            }
        }

        $this->info('Verificación de pólizas finalizada.');
    }
}
