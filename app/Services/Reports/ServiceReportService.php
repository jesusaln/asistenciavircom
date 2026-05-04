<?php

namespace App\Services\Reports;

use App\Models\Servicio;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Models\Venta;
use App\Enums\EstadoVenta;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ServiceReportService
{
    public function getServiceReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 500));

        $serviciosQuery = Servicio::query()
            ->withCount([
                'ventas' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                }
            ])
            ->select('servicios.*')
            ->addSelect([
                'total_vendido' => \App\Models\VentaItem::query()
                    ->join('ventas', 'venta_items.venta_id', '=', 'ventas.id')
                    ->whereColumn('venta_items.ventable_id', 'servicios.id')
                    ->where('venta_items.ventable_type', Servicio::class)
                    ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
                    ->selectRaw('SUM((venta_items.precio - COALESCE(venta_items.descuento, 0)) * venta_items.cantidad)'),
                'cantidad_vendida' => \App\Models\VentaItem::query()
                    ->join('ventas', 'venta_items.venta_id', '=', 'ventas.id')
                    ->whereColumn('venta_items.ventable_id', 'servicios.id')
                    ->where('venta_items.ventable_type', Servicio::class)
                    ->whereBetween('ventas.fecha', [$fechaInicio, $fechaFin])
                    ->selectRaw('SUM(venta_items.cantidad)')
            ]);

        $totalRows = (clone $serviciosQuery)->count();

        $serviciosData = $serviciosQuery->orderByDesc('total_vendido')
            ->limit($maxRows)
            ->get()
            ->map(function ($servicio) {
                $totalVendido = (float) $servicio->total_vendido;
                $ganancia = $totalVendido * ($servicio->margen_ganancia / 100);

                return [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'precio' => $servicio->precio,
                    'ganancia' => $ganancia,
                    'cantidad_vendida' => (float) $servicio->cantidad_vendida,
                    'total_vendido' => $totalVendido,
                    'numero_ventas' => $servicio->ventas_count,
                ];
            });

        $estadisticas = [
            'total_servicios' => Servicio::count(),
            'total_ingresos' => $serviciosData->sum('total_vendido'), // Simplificado: suma del top 500
            'total_ganancias' => $serviciosData->sum('ganancia'),
        ];

        return [
            'servicios' => $serviciosData,
            'estadisticas' => $estadisticas,
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => compact('fechaInicio', 'fechaFin'),
        ];
    }

    public function getCitaReportData(array $filters): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tecnicoId = $filters['tecnico_id'] ?? null;
        $estado = $filters['estado'] ?? 'todos';
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 1000));

        $query = Cita::with(['cliente', 'tecnico', 'servicio'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($tecnicoId)
            $query->where('tecnico_id', $tecnicoId);
        if ($estado !== 'todos')
            $query->where('estado', $estado);

        $totalRows = (clone $query)->count();

        $citas = $query->limit($maxRows)->get()->map(fn($c) => [
            'id' => $c->id,
            'fecha' => $c->fecha->format('Y-m-d'),
            'hora' => $c->hora,
            'cliente' => $c->cliente?->nombre_razon_social,
            'tecnico' => $c->tecnico?->name,
            'servicio' => $c->servicio?->nombre,
            'estado' => $c->estado,
            'precio' => $c->precio,
        ]);

        return [
            'citas' => $citas,
            'tecnicos' => User::where('role', 'tecnico')->get(['id', 'nombre', 'apellido']),
            'estadisticas' => [
                'total_citas' => $citas->count(),
                'completadas' => $citas->where('estado', 'completada')->count(),
                'ingresos' => $citas->where('estado', 'completada')->sum('precio'),
            ],
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => compact('fechaInicio', 'fechaFin', 'tecnicoId', 'estado'),
        ];
    }

    /**
     * Citas detalladas por técnico: quién atendió, fechas de agenda/inicio/fin, cliente, importes (sin tickets).
     *
     * @param  array<string, mixed>  $filters
     */
    public function getCitasPorTecnicoDetallado(array $filters, ?int $empresaId = null): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $tecnicoId = isset($filters['tecnico_id']) && $filters['tecnico_id'] !== '' && $filters['tecnico_id'] !== null
            ? (int) $filters['tecnico_id']
            : null;
        $estado = $filters['estado'] ?? 'todos';
        $soloConTecnico = array_key_exists('solo_con_tecnico', $filters)
            ? filter_var($filters['solo_con_tecnico'], FILTER_VALIDATE_BOOLEAN)
            : true;
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 2000));

        $inicio = $fechaInicio.' 00:00:00';
        $fin = $fechaFin.' 23:59:59';

        $query = Cita::query()
            ->with([
                'tecnico:id,name,email',
                'cliente:id,nombre_razon_social,telefono',
                'venta:id,cita_id,numero_venta,total,fecha',
            ])
            ->whereBetween('fecha_hora', [$inicio, $fin])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($tecnicoId, fn ($q) => $q->where('tecnico_id', $tecnicoId))
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
            ->when($soloConTecnico, fn ($q) => $q->whereNotNull('tecnico_id'));

        $totalRows = (clone $query)->count();

        $registros = $query->orderByDesc('fecha_hora')
            ->limit($maxRows)
            ->get()
            ->map(function (Cita $c) {
                $tecnicoNombre = $c->tecnico?->name;
                if (! $tecnicoNombre && $c->tecnico_id) {
                    $tecnicoNombre = 'Usuario #'.$c->tecnico_id;
                }
                if (! $tecnicoNombre) {
                    $tecnicoNombre = 'Sin técnico asignado';
                }

                $direccion = collect([
                    $c->direccion_calle,
                    $c->direccion_colonia,
                    $c->direccion_cp ? 'C.P. '.$c->direccion_cp : null,
                ])->filter()->implode(', ');

                $equipo = collect([
                    $c->tipo_equipo,
                    $c->marca_equipo,
                    $c->modelo_equipo,
                ])->filter()->implode(' · ');

                return [
                    'id' => $c->id,
                    'folio' => $c->folio,
                    'tecnico_id' => $c->tecnico_id,
                    'tecnico_nombre' => $tecnicoNombre,
                    'tecnico_email' => $c->tecnico?->email,
                    'cliente' => $c->cliente?->nombre_razon_social,
                    'cliente_id' => $c->cliente_id,
                    'cliente_telefono' => $c->cliente?->telefono,
                    'direccion' => $direccion !== '' ? $direccion : null,
                    'tipo_servicio' => $c->tipo_servicio,
                    'estado' => $c->estado,
                    'prioridad' => $c->prioridad,
                    'fecha_hora' => $c->fecha_hora?->toIso8601String(),
                    'inicio_servicio' => $c->inicio_servicio?->toIso8601String(),
                    'fin_servicio' => $c->fin_servicio?->toIso8601String(),
                    'fecha_hora_fin' => $c->fecha_hora_fin?->toIso8601String(),
                    'tiempo_servicio_minutos' => $c->tiempo_servicio !== null ? (int) $c->tiempo_servicio : null,
                    'total' => $c->total !== null ? (float) $c->total : null,
                    'descripcion' => $c->descripcion ? Str::limit((string) $c->descripcion, 200) : null,
                    'notas' => $c->notas ? Str::limit((string) $c->notas, 240) : null,
                    'equipo' => $equipo !== '' ? $equipo : null,
                    'problema_reportado' => $c->problema_reportado ? Str::limit((string) $c->problema_reportado, 400) : null,
                    'trabajo_realizado' => $c->trabajo_realizado ? Str::limit((string) $c->trabajo_realizado, 500) : null,
                    'creado_at' => $c->created_at?->toIso8601String(),
                    'actualizado_at' => $c->updated_at?->toIso8601String(),
                    'ver_url' => route('citas.show', $c->id),
                    'venta_id' => $c->venta?->id,
                    'venta_numero' => $c->venta?->numero_venta,
                    'venta_total' => $c->venta?->total !== null ? (float) $c->venta->total : null,
                ];
            })
            ->values();

        $resumenPorTecnico = $registros
            ->groupBy('tecnico_id')
            ->map(function ($grupo, $tid) {
                $nombre = $grupo->first()['tecnico_nombre'] ?? '—';
                $completadas = $grupo->where('estado', Cita::ESTADO_COMPLETADO)->count();
                $canceladas = $grupo->where('estado', Cita::ESTADO_CANCELADO)->count();
                $minutos = $grupo->sum(fn ($r) => (int) ($r['tiempo_servicio_minutos'] ?? 0));

                return [
                    'tecnico_id' => $tid === '' ? null : (is_numeric($tid) ? (int) $tid : null),
                    'tecnico_nombre' => $nombre,
                    'cantidad_citas' => $grupo->count(),
                    'completadas' => $completadas,
                    'canceladas' => $canceladas,
                    'minutos_servicio' => $minutos,
                    'total_importe' => round($grupo->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
                ];
            })
            ->values()
            ->sortByDesc('cantidad_citas')
            ->values();

        $tecnicosFiltro = User::query()
            ->where('activo', true)
            ->where(function ($q) {
                $q->where('es_tecnico', true)
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'tecnico'));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return [
            'registros' => $registros,
            'resumen_por_tecnico' => $resumenPorTecnico,
            'tecnicos' => $tecnicosFiltro,
            'estadisticas' => [
                'total_en_rango' => $totalRows,
                'mostrados' => $registros->count(),
                'completadas' => $registros->where('estado', Cita::ESTADO_COMPLETADO)->count(),
                'importe_total_mostrado' => round($registros->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
            ],
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'tecnico_id' => $tecnicoId,
                'estado' => $estado,
                'solo_con_tecnico' => $soloConTecnico,
            ],
        ];
    }

    /**
     * Ventas en un periodo: vendedor, líneas vendidas, totales para corte y cita vinculada (si existe).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getVentasPeriodoVendedorCita(array $filters, ?int $empresaId = null): array
    {
        $fechaInicio = $filters['fecha_inicio'] ?? Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $fechaFin = $filters['fecha_fin'] ?? Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        $vendedorUserId = isset($filters['vendedor_id']) && $filters['vendedor_id'] !== '' && $filters['vendedor_id'] !== null
            ? (int) $filters['vendedor_id']
            : null;
        $soloPagadas = array_key_exists('solo_pagadas', $filters)
            ? filter_var($filters['solo_pagadas'], FILTER_VALIDATE_BOOLEAN)
            : false;
        $maxRows = (int) ($filters['max_rows'] ?? config('report.max_rows', 2000));

        $inicio = $fechaInicio.' 00:00:00';
        $fin = $fechaFin.' 23:59:59';

        $query = Venta::query()
            ->with([
                'cliente:id,nombre_razon_social',
                'vendedor',
                'createdBy:id,name',
                'cita:id,folio,fecha_hora,tipo_servicio',
                'items' => static fn ($q) => $q->orderBy('id')->limit(30),
                'items.ventable',
            ])
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereNotIn('estado', [
                EstadoVenta::Cancelada,
                EstadoVenta::Anulado,
                EstadoVenta::Borrador,
            ])
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($soloPagadas, fn ($q) => $q->where('pagado', true))
            ->when($vendedorUserId, fn ($q) => $q->where('vendedor_type', User::class)->where('vendedor_id', $vendedorUserId));

        $totalRows = (clone $query)->count();

        $ventas = $query->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($maxRows)
            ->get();

        $vendedorIds = Venta::query()
            ->whereBetween('fecha', [$inicio, $fin])
            ->whereNotIn('estado', [
                EstadoVenta::Cancelada,
                EstadoVenta::Anulado,
                EstadoVenta::Borrador,
            ])
            ->where('vendedor_type', User::class)
            ->whereNotNull('vendedor_id')
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->when($soloPagadas, fn ($q) => $q->where('pagado', true))
            ->distinct()
            ->pluck('vendedor_id');

        $vendedoresOpciones = $vendedorIds->isNotEmpty()
            ? User::query()
                ->whereIn('id', $vendedorIds)
                ->where('activo', true)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        $registros = $ventas->map(function (Venta $v) {
            $parts = [];
            foreach ($v->items as $it) {
                $nombre = null;
                if ($it->ventable) {
                    $nombre = $it->ventable->nombre ?? $it->ventable->nombre_razon_social ?? null;
                }
                $nombre = $nombre ? Str::limit((string) $nombre, 42) : 'Ítem';
                $parts[] = rtrim(rtrim(number_format((float) $it->cantidad, 2, '.', ''), '0'), '.').'× '.$nombre;
            }
            $itemsResumen = $parts !== [] ? implode(', ', $parts) : '—';

            $vendedorNombre = '—';
            if ($v->relationLoaded('vendedor') && $v->vendedor) {
                $ven = $v->vendedor;
                if ($ven instanceof User) {
                    $vendedorNombre = (string) $ven->name;
                } elseif (is_object($ven) && isset($ven->name)) {
                    $vendedorNombre = (string) $ven->name;
                } elseif (is_object($ven) && isset($ven->nombre)) {
                    $vendedorNombre = (string) $ven->nombre;
                }
            }
            if ($vendedorNombre === '—' && $v->createdBy) {
                $vendedorNombre = (string) $v->createdBy->name;
            }

            $estado = $v->estado instanceof \BackedEnum ? $v->estado->value : (string) $v->estado;

            return [
                'id' => $v->id,
                'numero_venta' => $v->numero_venta,
                'fecha' => $v->fecha?->toIso8601String(),
                'total' => (float) $v->total,
                'pagado' => (bool) $v->pagado,
                'metodo_pago' => $v->metodo_pago,
                'estado' => $estado,
                'cliente' => $v->cliente?->nombre_razon_social,
                'vendedor_nombre' => $vendedorNombre,
                'vendedor_id' => $v->vendedor_type === User::class ? $v->vendedor_id : null,
                'items_resumen' => $itemsResumen,
                'items_detalle' => $v->items->map(fn ($it) => [
                    'cantidad' => (float) $it->cantidad,
                    'precio' => (float) $it->precio,
                    'descuento' => (float) ($it->descuento ?? 0),
                    'subtotal' => (float) ($it->subtotal ?? 0),
                    'nombre' => $it->ventable
                        ? Str::limit((string) ($it->ventable->nombre ?? $it->ventable->nombre_razon_social ?? 'Ítem'), 80)
                        : 'Ítem',
                    'tipo' => $it->ventable_type ? class_basename((string) $it->ventable_type) : null,
                ])->values()->all(),
                'cita_id' => $v->cita_id,
                'cita_folio' => $v->cita?->folio,
                'cita_fecha' => $v->cita?->fecha_hora?->toIso8601String(),
                'cita_tipo' => $v->cita?->tipo_servicio,
            ];
        })->values();

        $montoTotal = round($registros->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2);
        $montoPagado = round($registros->where('pagado', true)->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2);
        $montoPendiente = round($registros->where('pagado', false)->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2);

        $porMetodo = $registros
            ->where('pagado', true)
            ->groupBy(fn ($r) => (string) ($r['metodo_pago'] ?: 'sin_metodo'))
            ->map(fn ($grupo, $clave) => [
                'metodo' => $clave === 'sin_metodo' ? '—' : $clave,
                'total' => round($grupo->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
                'cantidad' => $grupo->count(),
            ])
            ->values()
            ->sortByDesc('total')
            ->values();

        $conCita = $registros->whereNotNull('cita_id')->count();

        $resumenPorVendedor = $registros
            ->groupBy('vendedor_nombre')
            ->map(function ($grupo, $nombre) {
                return [
                    'vendedor_nombre' => $nombre ?: '—',
                    'cantidad' => $grupo->count(),
                    'total' => round($grupo->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
                    'total_pagado' => round($grupo->where('pagado', true)->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
                    'total_pendiente' => round($grupo->where('pagado', false)->sum(fn ($r) => (float) ($r['total'] ?? 0)), 2),
                ];
            })
            ->values()
            ->sortByDesc('total')
            ->values();

        return [
            'registros' => $registros,
            'resumen_por_vendedor' => $resumenPorVendedor,
            'vendedores' => $vendedoresOpciones,
            'totales' => [
                'monto_total' => $montoTotal,
                'monto_pagado' => $montoPagado,
                'monto_pendiente' => $montoPendiente,
                'cantidad' => $registros->count(),
                'cantidad_pagadas' => $registros->where('pagado', true)->count(),
                'cantidad_pendientes' => $registros->where('pagado', false)->count(),
                'con_cita' => $conCita,
                'sin_cita' => $registros->count() - $conCita,
            ],
            'por_metodo_pago' => $porMetodo,
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'vendedor_id' => $vendedorUserId,
                'solo_pagadas' => $soloPagadas,
            ],
        ];
    }
}
