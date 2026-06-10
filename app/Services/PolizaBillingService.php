<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\PolizaCargo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PolizaBillingService
{
    /**
     * Procesar facturación para todas las pólizas activas.
     */
    public function processDailyBilling()
    {
        $polizas = PolizaServicio::whereIn('estado', [PolizaServicio::ESTADO_ACTIVA, PolizaServicio::ESTADO_VENCIDA_EN_GRACIA])
            ->with('planPoliza')
            ->get();
        $count = 0;

        foreach ($polizas as $poliza) {
            if ($this->shouldGenerateCharge($poliza)) {
                $this->generateCharge($poliza);
                $count++;
            }

            $this->updatePaymentStatus($poliza);
        }

        return $count;
    }

    /**
     * Determina si se debe generar un cargo hoy para la póliza.
     */
    public function shouldGenerateCharge(PolizaServicio $poliza): bool
    {
        $plan = $poliza->planPoliza;
        if (!$plan)
            return false;

        $hoy = Carbon::today();
        $diaCobro = $poliza->dia_cobro ?: ($plan->limit_dia_pago ?: 5);

        // Si hoy es el día de cobro
        if ($hoy->day == $diaCobro) {
            // Verificar si ya se generó un cargo este mes
            $ultimoCargo = $poliza->cargos()
                ->whereMonth('fecha_emision', $hoy->month)
                ->whereYear('fecha_emision', $hoy->year)
                ->first();

            return !$ultimoCargo;
        }

        return false;
    }

    /**
     * Genera un nuevo cargo basado en la configuración de la póliza y el plan.
     */
    public function generateCharge(PolizaServicio $poliza): PolizaCargo
    {
        $plan = $poliza->planPoliza;
        $hoy = Carbon::today();

        return DB::transaction(function () use ($poliza, $plan, $hoy) {
            // Determinar monto según el plan (por ahora por defecto mensual)
            $subtotal = $poliza->monto_mensual > 0 ? $poliza->monto_mensual : ($plan->precio_mensual ?? 0);

            $tasaIva = $plan->iva_tasa / 100;
            $iva = $plan->iva_incluido ? 0 : round($subtotal * $tasaIva, 2);
            $total = $plan->iva_incluido ? $subtotal : ($subtotal + $iva);

            if ($plan->iva_incluido) {
                // Si está incluido, el subtotal real es menor
                $subtotal = round($total / (1 + $tasaIva), 2);
                $iva = $total - $subtotal;
            }

            $vencimiento = $hoy->copy()->addDays($plan->dias_gracia_cobranza ?: 3);

            $cargo = PolizaCargo::create([
                'poliza_id' => $poliza->id,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'moneda' => $plan->moneda ?? 'MXN',
                'concepto' => "Servicio de Póliza {$poliza->folio} - " . $hoy->isoFormat('MMMM YYYY'),
                'tipo_ciclo' => 'mensual',
                'fecha_emision' => $hoy,
                'fecha_vencimiento' => $vencimiento,
                'estado' => 'pendiente',
                'notas' => "Generado automáticamente por el motor de cobranza.",
            ]);

            $poliza->update(['ultimo_cobro_generado_at' => now()]);

            Log::info("Cargo generado para Póliza {$poliza->folio}: ID {$cargo->id}");

            return $cargo;
        });
    }

    /**
     * Actualizar el estado de la póliza basado en sus cargos pendientes.
     */
    public function updatePaymentStatus(PolizaServicio $poliza)
    {
        $cargosVencidos = $poliza->cargos()->vencidos()->count();

        if ($cargosVencidos > 0) {
            if ($poliza->estado === PolizaServicio::ESTADO_ACTIVA) {
                // Si tiene días de gracia configurados, pasar a "Vencida en Gracia"
                $poliza->update(['estado' => PolizaServicio::ESTADO_VENCIDA_EN_GRACIA]);
                Log::warning("Póliza {$poliza->folio} marcada como Vencida en Gracia.");
            }
        } else {
            // Si estaba vencida y ya no hay cargos, reactivar si corresponde
            if ($poliza->estado === PolizaServicio::ESTADO_VENCIDA_EN_GRACIA || $poliza->estado === PolizaServicio::ESTADO_PENDIENTE_PAGO) {
                $poliza->update(['estado' => PolizaServicio::ESTADO_ACTIVA]);
            }
        }
    }
}
