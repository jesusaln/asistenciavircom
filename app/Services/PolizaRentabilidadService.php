<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\PolizaConsumo;
use App\Models\User;
use App\Notifications\PolizaLowMarginNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PolizaRentabilidadService
{
    /**
     * Analizar rentabilidad de una póliza específica.
     * MÉTODOS MEJORADOS: Ahora usa costos reales por técnico e ingresos devengados (IFRS15).
     */
    public function analizarPoliza(PolizaServicio $poliza): array
    {
        // Ingresos: Usamos el ingreso devengado (ya reconocido)
        // Si no hay devengado (porque es mensual y no se ha corrido el comando), 
        // usamos el monto mensual como fallback para el reporte de "mes actual"
        $ingresoTotal = (float) ($poliza->ingreso_devengado > 0 ? $poliza->ingreso_devengado : $poliza->monto_mensual);

        // Costos: Usamos el costo acumulado real trackeado en los consumos
        $costoReal = (float) $poliza->costo_acumulado_tecnico;

        // Rentabilidad
        $utilidad = $ingresoTotal - $costoReal;
        $margen = $ingresoTotal > 0 ? round(($utilidad / $ingresoTotal) * 100, 1) : 0;

        // Clasificación
        $clasificacion = $this->clasificarRentabilidad($margen);

        return [
            'poliza_id' => $poliza->id,
            'folio' => $poliza->folio,
            'cliente' => $poliza->cliente?->nombre_razon_social ?? 'N/A',
            'plan' => $poliza->planPoliza?->nombre ?? $poliza->nombre,
            'monto_mensual' => $poliza->monto_mensual,
            'ingreso_reconocido' => (float) $poliza->ingreso_devengado,
            'ingreso_diferido' => (float) $poliza->ingreso_diferido,
            'costo_tecnico_real' => $costoReal,
            'utilidad' => $utilidad,
            'margen_porcentaje' => $margen,
            'clasificacion' => $clasificacion,
            'horas_consumidas' => (float) $poliza->horas_consumidas_mes,
            'salud_financiera' => $this->getHealthIndicator($margen),
        ];
    }

    /**
     * Obtener el resumen ejecutivo para el Dashboard Financiero.
     */
    public function getResumenEjecutivo(): array
    {
        $polizas = $this->getReporteGeneral();

        return [
            'stats' => [
                'total_ingresos_reconocidos' => $polizas->sum('ingreso_reconocido'),
                'total_ingresos_diferidos' => $polizas->sum('ingreso_diferido'),
                'total_costos_reales' => $polizas->sum('costo_tecnico_real'),
                'utilidad_neta' => $polizas->sum('utilidad'),
                'margen_global' => $polizas->sum('ingreso_reconocido') > 0
                    ? round(($polizas->sum('utilidad') / $polizas->sum('ingreso_reconocido')) * 100, 1)
                    : 0,
            ],
            'por_clasificacion' => $polizas->groupBy('clasificacion')->map(fn($group) => [
                'count' => $group->count(),
                'utilidad' => $group->sum('utilidad')
            ]),
            'top_oportunidades' => $polizas->sortByDesc('margen_porcentaje')->take(5)->values(),
            'top_riesgos' => $polizas->filter(fn($p) => $p['margen_porcentaje'] < 10)->sortBy('margen_porcentaje')->take(5)->values(),
            'eficiencia_tecnica' => $this->getEficienciaTecnica(),
        ];
    }

    /**
     * Reporte de eficiencia por técnico basado en consumos de pólizas.
     */
    public function getEficienciaTecnica(): Collection
    {
        return PolizaConsumo::with('tecnico')
            ->whereNotNull('tecnico_id')
            ->select(
                'tecnico_id',
                DB::raw('SUM(costo_interno) as costo_total'),
                DB::raw('SUM(CASE WHEN tipo = "hora" THEN cantidad ELSE 1 END) as horas_equivalentes'),
                DB::raw('COUNT(*) as total_servicios')
            )
            ->groupBy('tecnico_id')
            ->get()
            ->map(function ($data) {
                return [
                    'tecnico' => $data->tecnico?->name ?? 'Desconocido',
                    'costo_total' => (float) $data->costo_total,
                    'horas' => (float) $data->horas_equivalentes,
                    'servicios' => (int) $data->total_servicios,
                    'costo_promedio_servicio' => $data->total_servicios > 0
                        ? round($data->costo_total / $data->total_servicios, 2)
                        : 0,
                ];
            })->sortBy('costo_promedio_servicio')->values();
    }

    public function getReporteGeneral(): Collection
    {
        return PolizaServicio::with(['cliente', 'planPoliza'])
            ->activa()
            ->get()
            ->map(fn($poliza) => $this->analizarPoliza($poliza));
    }

    /**
     * IFRS15: Procesar reconocimiento mensual de ingresos.
     */
    public function recognizeMonthlyRevenue()
    {
        $hoy = Carbon::today();

        $polizas = PolizaServicio::where('estado', PolizaServicio::ESTADO_ACTIVA)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('ultima_emision_fac_at')
                    ->orWhere('ultima_emision_fac_at', '<', $hoy->startOfMonth());
            })
            ->get();

        $totalProcesado = 0;

        foreach ($polizas as $poliza) {
            DB::transaction(function () use ($poliza, &$totalProcesado) {
                $monto = 0;

                if ($poliza->monto_mensual > 0) {
                    $monto = $poliza->monto_mensual;
                } else if ($poliza->monto_total_contrato > 0) {
                    $meses = Carbon::parse($poliza->fecha_inicio)->diffInMonths(Carbon::parse($poliza->fecha_fin)) ?: 1;
                    $monto = round($poliza->monto_total_contrato / $meses, 2);
                }

                if ($monto > 0) {
                    $poliza->increment('ingreso_devengado', $monto);
                    if ($poliza->ingreso_diferido >= $monto) {
                        $poliza->decrement('ingreso_diferido', $monto);
                    }
                    $poliza->update(['ultima_emision_fac_at' => now()]);
                    $totalProcesado++;
                }
            });
        }

        return $totalProcesado;
    }

    /**
     * Verificar salud financiera y notificar si el margen es crítico.
     */
    public function checkHealthAndNotify(PolizaServicio $poliza)
    {
        $analisis = $this->analizarPoliza($poliza);
        $margen = $analisis['margen_porcentaje'];

        // Umbral de alerta: 10%
        if ($margen < 10) {
            $admins = User::role(['admin', 'super-admin'])->get();

            // Usamos un lock o cache para no spamear alertas en cada consumo si ya está en riesgo
            $lockKey = "poliza_alert_sent_{$poliza->id}_" . now()->format('Y-m');

            if (!cache()->has($lockKey)) {
                Notification::send($admins, new PolizaLowMarginNotification($poliza, $margen));
                cache()->put($lockKey, true, now()->addDays(7));

                Log::warning("Alerta de Rentabilidad enviada para Póliza {$poliza->folio}", ['margen' => $margen]);
            }
        }
    }

    protected function clasificarRentabilidad(float $margen): string
    {
        if ($margen >= 40)
            return 'altamente_rentable';
        if ($margen >= 20)
            return 'rentable';
        if ($margen >= 5)
            return 'marginal';
        return 'bajo_margen';
    }

    protected function getHealthIndicator(float $margen): string
    {
        if ($margen >= 30)
            return 'success';
        if ($margen >= 15)
            return 'warning';
        return 'danger';
    }
}
