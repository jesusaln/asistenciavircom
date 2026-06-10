<?php

namespace App\Services;

use App\Models\PolizaServicio;
use App\Models\EmpresaConfiguracion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PolizaRentabilidadService
{
    protected float $costoHoraTecnico;

    public function __construct()
    {
        $config = EmpresaConfiguracion::getConfig();
        $this->costoHoraTecnico = (float) ($config->costo_promedio_hora_tecnico ?? 150.00);
    }

    /**
     * Analizar rentabilidad de una póliza específica.
     */
    public function analizarPoliza(PolizaServicio $poliza): array
    {
        $periodo = $this->getPeriodoActual($poliza);

        // Ingresos: monto mensual de la póliza
        $ingreso = (float) $poliza->monto_mensual;

        // Costos: horas trabajadas * costo por hora del técnico
        $horasConsumidas = (float) $poliza->horas_consumidas_mes;
        $costoOperativo = $horasConsumidas * $this->costoHoraTecnico;

        // Rentabilidad
        $utilidad = $ingreso - $costoOperativo;
        $margen = $ingreso > 0 ? round(($utilidad / $ingreso) * 100, 1) : 0;

        // Clasificación
        $clasificacion = $this->clasificarRentabilidad($margen);

        return [
            'poliza_id' => $poliza->id,
            'folio' => $poliza->folio,
            'cliente' => $poliza->cliente?->nombre_razon_social ?? 'N/A',
            'plan' => $poliza->planPoliza?->nombre ?? $poliza->nombre,
            'ingreso_mensual' => $ingreso,
            'horas_consumidas' => $horasConsumidas,
            'costo_operativo' => $costoOperativo,
            'utilidad' => $utilidad,
            'margen_porcentaje' => $margen,
            'clasificacion' => $clasificacion,
            'periodo' => $periodo,
        ];
    }

    /**
     * Obtener reporte de rentabilidad de todas las pólizas activas.
     */
    public function getReporteGeneral(): Collection
    {
        return PolizaServicio::with(['cliente', 'planPoliza'])
            ->activa()
            ->get()
            ->map(fn($poliza) => $this->analizarPoliza($poliza))
            ->sortBy('margen_porcentaje');
    }

    /**
     * Obtener resumen ejecutivo de rentabilidad.
     */
    public function getResumenEjecutivo(): array
    {
        $polizas = $this->getReporteGeneral();

        $rentables = $polizas->filter(fn($p) => $p['clasificacion'] === 'rentable');
        $marginales = $polizas->filter(fn($p) => $p['clasificacion'] === 'marginal');
        $perdida = $polizas->filter(fn($p) => $p['clasificacion'] === 'perdida');

        return [
            'total_polizas' => $polizas->count(),
            'total_ingresos' => $polizas->sum('ingreso_mensual'),
            'total_costos' => $polizas->sum('costo_operativo'),
            'utilidad_neta' => $polizas->sum('utilidad'),
            'margen_promedio' => round($polizas->avg('margen_porcentaje'), 1),
            'costo_hora_tecnico' => $this->costoHoraTecnico,
            'resumen' => [
                'rentables' => [
                    'cantidad' => $rentables->count(),
                    'ingresos' => $rentables->sum('ingreso_mensual'),
                    'utilidad' => $rentables->sum('utilidad'),
                ],
                'marginales' => [
                    'cantidad' => $marginales->count(),
                    'ingresos' => $marginales->sum('ingreso_mensual'),
                    'utilidad' => $marginales->sum('utilidad'),
                ],
                'en_perdida' => [
                    'cantidad' => $perdida->count(),
                    'ingresos' => $perdida->sum('ingreso_mensual'),
                    'utilidad' => $perdida->sum('utilidad'),
                ],
            ],
            'top_rentables' => $polizas->sortByDesc('margen_porcentaje')->take(5)->values(),
            'top_perdida' => $polizas->filter(fn($p) => $p['utilidad'] < 0)->sortBy('utilidad')->take(5)->values(),
        ];
    }

    /**
     * Clasificar rentabilidad según el margen.
     */
    protected function clasificarRentabilidad(float $margen): string
    {
        if ($margen >= 30)
            return 'rentable';
        if ($margen >= 0)
            return 'marginal';
        return 'perdida';
    }

    /**
     * Obtener el periodo actual de la póliza.
     */
    protected function getPeriodoActual(PolizaServicio $poliza): string
    {
        $año = now()->year;
        $mes = now()->format('F');
        return "{$mes} {$año}";
    }
}
