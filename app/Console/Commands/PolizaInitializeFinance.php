<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PolizaServicio;
use App\Models\PolizaConsumo;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PolizaInitializeFinance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:initialize-finance {--default-cost=250 : Costo por hora por defecto si el técnico no lo tiene}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicializa los campos financieros (IFRS15 y Costos) para pólizas existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultCost = $this->option('default-cost');
        $this->info("Iniciando inicialización financiera (Default Cost: {$defaultCost}/hr)...");

        // 1. Procesar Consumos Históricos
        $this->info("Paso 1: Recalculando costos internos en consumos...");
        $consumos = PolizaConsumo::where('costo_interno', 0)->get();
        $this->withProgressBar($consumos, function ($consumo) use ($defaultCost) {
            $consumible = $consumo->consumible;
            if (!$consumible)
                return;

            $tecnico = null;
            if ($consumo->consumible_type === Ticket::class) {
                $tecnico = $consumible->technician;
            } elseif ($consumo->consumible_type === Cita::class) {
                $tecnico = $consumible->tecnico;
            }

            if ($tecnico) {
                $tarifa = $tecnico->costo_hora_interno > 0 ? $tecnico->costo_hora_interno : $defaultCost;

                $horas = 1;
                if ($consumo->tipo === PolizaConsumo::TIPO_HORA) {
                    $horas = (float) $consumo->cantidad;
                }

                $consumo->update([
                    'costo_interno' => $tarifa * $horas,
                    'tecnico_id' => $tecnico->id
                ]);
            }
        });
        $this->newLine();

        // 2. Procesar Pólizas (Acumulados e IFRS15)
        $this->info("Paso 2: Inicializando acumulados y estados IFRS15 en pólizas...");
        $polizas = PolizaServicio::all();
        $this->withProgressBar($polizas, function ($poliza) {
            DB::transaction(function () use ($poliza) {
                // Sincronizar costo acumulado
                $costoTotal = PolizaConsumo::where('poliza_id', $poliza->id)->sum('costo_interno');

                // Inicializar IFRS15
                $ingresoDevengado = 0;
                $ingresoDiferido = 0;

                if ($poliza->monto_total_contrato > 0) {
                    $fechaInicio = Carbon::parse($poliza->fecha_inicio);
                    $fechaFin = Carbon::parse($poliza->fecha_fin);
                    $hoy = Carbon::today();

                    $mesesTotales = max(1, $fechaInicio->diffInMonths($fechaFin));
                    $mesesTranscurridos = $fechaInicio->diffInMonths($hoy->min($fechaFin));

                    $montoMensual = $poliza->monto_total_contrato / $mesesTotales;
                    $ingresoDevengado = round($montoMensual * $mesesTranscurridos, 2);
                    $ingresoDiferido = max(0, $poliza->monto_total_contrato - $ingresoDevengado);
                } else if ($poliza->monto_mensual > 0) {
                    // Para mensuales, el acumulado es lo consumido este mes (o meses anteriores si queremos histórico full)
                    // Por simplicidad en inicialización, asumimos que el devengado histórico es la suma de sus meses activos
                    $fechaInicio = Carbon::parse($poliza->fecha_inicio);
                    $mesesTranscurridos = $fechaInicio->diffInMonths(Carbon::now());
                    $ingresoDevengado = $poliza->monto_mensual * $mesesTranscurridos;
                    $ingresoDiferido = 0;
                }

                $poliza->update([
                    'costo_acumulado_tecnico' => $costoTotal,
                    'ingreso_devengado' => $ingresoDevengado,
                    'ingreso_diferido' => $ingresoDiferido,
                    'ultima_emision_fac_at' => now()->startOfMonth()
                ]);
            });
        });

        $this->newLine(2);
        $this->info('¡Inicialización financiera completada con éxito!');
        $this->info('Las pólizas existentes ahora tienen datos de rentabilidad para el reporte.');
    }
}
