<?php

namespace App\Services\Reports;

use App\Models\Venta;
use App\Models\Cobranza;
use App\Models\Compra;
use App\Models\Cita;
use App\Models\Renta;
use App\Models\User;
use App\Models\CajaChica;
use App\Models\EntregaDinero;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public function getCorteDiarioData(array $params): array
    {
        $periodo = $params['periodo'] ?? 'diario';
        $fecha = $params['fecha'] ?? now()->format('Y-m-d');
        $maxRows = (int) ($params['max_rows'] ?? config('report.max_rows', 500));

        $range = $this->calculateDateRange($periodo, $fecha, $params);
        $fecha_inicio = $range['inicio'];
        $fecha_fin = $range['fin'];
        $periodoLabel = $range['label'];

        $ventasBase = Venta::with(['cliente', 'items.ventable', 'pagadoPor'])
            ->where('pagado', true)
            ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59']);

        $ventasTotalRows = (clone $ventasBase)->count();
        $ventasPagadas = $ventasBase->orderBy('fecha_pago', 'desc')
            ->limit($maxRows)
            ->get();

        $cobranzasBase = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->whereIn('estado', ['pagado', 'parcial'])
            ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59']);

        $cobranzasTotalRows = (clone $cobranzasBase)->count();
        $cobranzasPagadas = $cobranzasBase->orderBy('fecha_pago', 'desc')
            ->limit($maxRows)
            ->get();

        $totalesPorMetodo = $this->calculateTotalesPorMetodo($ventasPagadas, $cobranzasPagadas);

        $pagosFormateados = $this->formatPagos($ventasPagadas, $cobranzasPagadas);

        return [
            'pagos' => $pagosFormateados,
            'totalesPorMetodo' => $totalesPorMetodo['por_metodo'],
            'totalGeneral' => $totalesPorMetodo['total_general'],
            'total_rows' => $ventasTotalRows + $cobranzasTotalRows,
            'truncated' => $ventasTotalRows > $maxRows || $cobranzasTotalRows > $maxRows,
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

    public function getEfectivoPorUsuarioData(array $params): array
    {
        $fecha_inicio = $params['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fecha_fin = $params['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');

        $users = User::all(); // Fetch all users, or you can filter by roles if needed

        $report = $users->map(function ($user) use ($fecha_inicio, $fecha_fin) {
            // 1. Efectivo de Ventas
            $ventasEfectivo = Venta::where('pagado', true)
                ->where('pagado_por', $user->id)
                ->where('metodo_pago', 'efectivo')
                ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'])
                ->sum('total');

            // 2. Efectivo de Cobranzas
            $cobranzasEfectivo = Cobranza::whereIn('estado', ['pagado', 'parcial'])
                ->where('responsable_cobro', $user->id)
                ->where('metodo_pago', 'efectivo')
                ->whereBetween('fecha_pago', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'])
                ->sum('monto_pagado');

            // 3. Gastos / Ingresos de Caja Chica (Efectivo por defecto)
            $cajaChicaBase = CajaChica::where('user_id', $user->id)
                ->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
            
            $cajaChicaIngresos = (clone $cajaChicaBase)->where('tipo', 'ingreso')->sum('monto');
            $cajaChicaEgresos = (clone $cajaChicaBase)->where('tipo', 'egreso')->sum('monto');

            // 4. Entregas de Dinero realizadas (Salidas hacia admin/banco u otro usuario)
            $entregasEfectivoEnviadas = EntregaDinero::where('user_id', $user->id)
                ->where('estado', 'recibido') // Solo si fue recibido por el otro lado
                ->whereBetween('fecha_entrega', [$fecha_inicio, $fecha_fin])
                ->sum('monto_efectivo');

            // 5. Entregas de Dinero RECIBIDAS de otros usuarios (Entradas a su bolsillo)
            // Esto es crucial para transferencias entre técnicos
            $entregasEfectivoRecibidas = EntregaDinero::where('recibido_por', $user->id)
                ->where('estado', 'recibido')
                ->whereBetween('fecha_recibido', [$fecha_inicio . ' 00:00:00', $fecha_fin . ' 23:59:59'])
                ->sum('monto_efectivo');

            $totalRecolectado = (float)$ventasEfectivo + (float)$cobranzasEfectivo + (float)$cajaChicaIngresos + (float)$entregasEfectivoRecibidas;
            $totalEntregado = (float)$entregasEfectivoEnviadas + (float)$cajaChicaEgresos;
            $saldo = $totalRecolectado - $totalEntregado;

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'ventas_efectivo' => (float)$ventasEfectivo,
                'cobranzas_efectivo' => (float)$cobranzasEfectivo,
                'caja_chica_ingresos' => (float)$cajaChicaIngresos,
                'caja_chica_egresos' => (float)$cajaChicaEgresos,
                'entregas_efectivo' => (float)$entregasEfectivoEnviadas,
                'entregas_recibidas' => (float)$entregasEfectivoRecibidas,
                'total_recolectado' => $totalRecolectado,
                'total_entregado' => $totalEntregado,
                'saldo' => $saldo,
            ];
        })->filter(function($item) {
            // Solo mostrar usuarios que han tenido movimientos
            return $item['total_recolectado'] > 0 || $item['total_entregado'] > 0 || abs($item['saldo']) > 0.01;
        })->sortByDesc('saldo')->values();

        return [
            'data' => $report,
            'filtros' => [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
            ],
            'totales' => [
                'total_saldo' => (float)$report->sum('saldo'),
                'total_recolectado' => (float)$report->sum('total_recolectado'),
                'total_entregado' => (float)$report->sum('total_entregado'),
            ]
        ];
    }
}
