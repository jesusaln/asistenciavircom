<?php

namespace App\Services\Reports;

use App\Models\Venta;
use App\Models\Cobranza;
use App\Models\Compra;
use App\Models\Cita;
use App\Models\Renta;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public function getCorteDiarioData(array $params): array
    {
        $periodo = $params['periodo'] ?? 'diario';
        $fecha = $params['fecha'] ?? now()->format('Y-m-d');

        $range = $this->calculateDateRange($periodo, $fecha, $params);
        $fecha_inicio = $range['inicio'];
        $fecha_fin = $range['fin'];
        $periodoLabel = $range['label'];

        $ventasPagadas = Venta::with(['cliente', 'items.ventable', 'pagadoPor'])
            ->where('pagado', true)
            ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'])
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $cobranzasPagadas = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->whereIn('estado', ['pagado', 'parcial'])
            ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'])
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $totalesPorMetodo = $this->calculateTotalesPorMetodo($ventasPagadas, $cobranzasPagadas);

        $pagosFormateados = $this->formatPagos($ventasPagadas, $cobranzasPagadas);

        return [
            'pagos' => $pagosFormateados,
            'totalesPorMetodo' => $totalesPorMetodo['por_metodo'],
            'totalGeneral' => $totalesPorMetodo['total_general'],
            'periodo' => $periodo,
            'periodoLabel' => $periodoLabel,
            'fecha' => $fecha,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'fechaFormateada' => $this->formatDisplayDate($periodo, $fecha, $fecha_inicio, $fecha_fin),
        ];
    }

    private function calculateDateRange($periodo, $fecha, $params): array
    {
        $carbon = Carbon::parse($fecha);
        switch ($periodo) {
            case 'semanal':
                return [
                    'inicio' => $carbon->startOfWeek()->format('Y-m-d'),
                    'fin' => $carbon->endOfWeek()->format('Y-m-d'),
                    'label' => 'Semanal'
                ];
            case 'mensual':
                return [
                    'inicio' => $carbon->startOfMonth()->format('Y-m-d'),
                    'fin' => $carbon->endOfMonth()->format('Y-m-d'),
                    'label' => 'Mensual'
                ];
            case 'anual':
                return [
                    'inicio' => $carbon->startOfYear()->format('Y-m-d'),
                    'fin' => $carbon->endOfYear()->format('Y-m-d'),
                    'label' => 'Anual'
                ];
            case 'personalizado':
                return [
                    'inicio' => $params['fecha_inicio'] ?? $fecha,
                    'fin' => $params['fecha_fin'] ?? $fecha,
                    'label' => 'Personalizado'
                ];
            default:
                return [
                    'inicio' => $fecha,
                    'fin' => $fecha,
                    'label' => 'Diario'
                ];
        }
    }

    private function calculateTotalesPorMetodo(Collection $ventas, Collection $cobranzas): array
    {
        $totales = [
            'efectivo' => 0,
            'transferencia' => 0,
            'cheque' => 0,
            'tarjeta' => 0,
            'otros' => 0,
        ];
        $totalGeneral = 0;

        foreach ($ventas as $v) {
            $metodo = $v->metodo_pago ?? 'otros';
            $monto = (float) $v->total;
            if (isset($totales[$metodo]))
                $totales[$metodo] += $monto;
            else
                $totales['otros'] += $monto;
            $totalGeneral += $monto;
        }

        foreach ($cobranzas as $c) {
            $metodo = $c->metodo_pago ?? 'otros';
            $monto = (float) $c->monto_pagado;
            if (isset($totales[$metodo]))
                $totales[$metodo] += $monto;
            else
                $totales['otros'] += $monto;
            $totalGeneral += $monto;
        }

        return ['por_metodo' => $totales, 'total_general' => $totalGeneral];
    }

    private function formatPagos(Collection $ventas, Collection $cobranzas): Collection
    {
        $vForm = $ventas->map(fn($v) => [
            'id' => $v->id,
            'tipo' => 'venta',
            'numero' => $v->numero_venta,
            'cliente' => $v->cliente->nombre_razon_social ?? 'Sin cliente',
            'concepto' => 'Venta',
            'total' => $v->total,
            'metodo_pago' => $v->metodo_pago,
            'fecha_pago' => $v->fecha_pago ? $v->fecha_pago->toIso8601String() : null,
            'notas_pago' => $v->notas_pago,
            'pagado_por' => $v->pagadoPor?->name ?? 'Sistema',
        ]);

        $cForm = $cobranzas->map(fn($c) => [
            'id' => $c->id,
            'tipo' => 'cobranza',
            'numero' => $c->renta->numero_contrato ?? 'N/A',
            'cliente' => $c->renta->cliente->nombre_razon_social ?? 'Sin cliente',
            'concepto' => $c->concepto ?? 'Cobranza',
            'total' => $c->monto_pagado,
            'metodo_pago' => $c->metodo_pago,
            'fecha_pago' => $c->fecha_pago ? $c->fecha_pago->toIso8601String() : null,
            'notas_pago' => $c->notas_pago,
            'pagado_por' => $c->responsableCobro?->name ?? 'Sistema',
        ]);

        return collect([...$vForm, ...$cForm])->sortByDesc('fecha_pago')->values();
    }

    private function formatDisplayDate($periodo, $fecha, $inicio, $fin): string
    {
        if ($periodo === 'personalizado') {
            return "Del " . Carbon::parse($inicio)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') .
                " al " . Carbon::parse($fin)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        }
        $format = match ($periodo) {
            'diario' => 'dddd, D [de] MMMM [de] YYYY',
            'semanal' => '[Semana del] D [de] MMMM [de] YYYY',
            'mensual' => 'MMMM [de] YYYY',
            default => 'YYYY',
        };
        return Carbon::parse($fecha)->locale('es')->isoFormat($format);
    }
}
