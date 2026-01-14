<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PolizaServicio;
use App\Models\CuentasPorCobrar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerarCobroPolizas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'polizas:generar-cobros';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera las cuentas por cobrar mensuales para las pólizas de servicio activas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoy = now();
        $diaActual = $hoy->day;
        $mesActual = $hoy->month;
        $anioActual = $hoy->year;

        $this->info("Iniciando generación de cobros de pólizas para el día: {$hoy->toDateString()}");

        // Obtenemos pólizas activas cuyo día de cobro sea hoy
        // O si hoy es el último día del mes e incluir pólizas cuyo día es mayor al total de días del mes
        $polizas = PolizaServicio::activa()
            ->where(function ($query) use ($diaActual, $hoy) {
                $query->where('dia_cobro', $diaActual);

                if ($hoy->isLastOfMonth()) {
                    $query->orWhere('dia_cobro', '>', $diaActual);
                }
            })
            ->get();

        if ($polizas->isEmpty()) {
            $this->info("No hay pólizas programadas para cobro el día de hoy.");
            return;
        }

        $bar = $this->output->createProgressBar(count($polizas));
        $bar->start();

        foreach ($polizas as $poliza) {
            // Usamos la nueva columna para verificar si ya se cobró este mes
            if ($poliza->ultimo_cobro_generado_at) {
                $ultimoCobro = Carbon::parse($poliza->ultimo_cobro_generado_at);
                if ($ultimoCobro->month == $mesActual && $ultimoCobro->year == $anioActual) {
                    $bar->advance();
                    continue;
                }
            }

            DB::beginTransaction();
            try {
                $this->generarCobro($poliza, $hoy);

                // Marcar como generado
                $poliza->ultimo_cobro_generado_at = $hoy;
                $poliza->save();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error generando cobro para póliza {$poliza->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Proceso finalizado correctamente.");
    }

    /**
     * Generar el registro de Cuenta por Cobrar para la póliza.
     */
    private function generarCobro(PolizaServicio $poliza, Carbon $hoy)
    {
        // Fecha de vencimiento: 5 días después del cobro
        $fechaVencimiento = $hoy->copy()->addDays(5);

        $poliza->cuentasPorCobrar()->create([
            'empresa_id' => $poliza->empresa_id,
            'cliente_id' => $poliza->cliente_id,
            'monto_total' => $poliza->monto_mensual,
            'monto_pagado' => 0,
            'monto_pendiente' => $poliza->monto_mensual,
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => 'pendiente',
            'notas' => "Cobro mensual automatizado - Póliza: {$poliza->folio} ({$poliza->nombre}) - Período: " . $hoy->translatedFormat('F Y'),
        ]);

        Log::info("Cobro automático generado para póliza {$poliza->folio} - Cliente: {$poliza->cliente->nombre_razon_social}");
    }
}
