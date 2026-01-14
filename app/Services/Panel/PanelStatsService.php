<?php

namespace App\Services\Panel;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Cita;
use App\Models\Mantenimiento;
use App\Models\OrdenCompra;

class PanelStatsService
{
    public function getBasicStats(): array
    {
        return Cache::remember('panel_stats', 300, function () {
            $now = Carbon::now(config('app.timezone', 'America/Hermosillo'));

            return [
                'clientes' => Cliente::count(),
                'productos' => Producto::count(),
                'proveedores' => Proveedor::count(),
                'citas' => Cita::count(),
                'mantenimientos' => Mantenimiento::count(),
                'clientes_nuevos' => Cliente::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
            ];
        });
    }

    public function getProductosBajoStock(int $limit = 20): array
    {
        return Cache::remember('panel_productos_bajo_stock', 300, function () use ($limit) {
            $productos = Producto::select('nombre', 'stock', 'stock_minimo')
                ->whereColumn('stock', '<=', 'stock_minimo')
                ->where(function ($query) {
                    $query->where('tipo_producto', '!=', 'kit')->orWhereNull('tipo_producto');
                })
                ->limit($limit)
                ->get();

            return [
                'productos' => $productos,
                'count' => Producto::whereColumn('stock', '<=', 'stock_minimo')
                    ->where(function ($query) {
                        $query->where('tipo_producto', '!=', 'kit')->orWhereNull('tipo_producto');
                    })
                    ->count(),
                'nombres' => $productos->pluck('nombre')->toArray(),
            ];
        });
    }

    public function getOrdenesCompraStats(): array
    {
        return Cache::remember('panel_ordenes_compra', 300, function () {
            $now = Carbon::now();

            try {
                $pendientes = OrdenCompra::with(['proveedor'])
                    ->where('estado', 'pendiente')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                $enviadas = OrdenCompra::with(['proveedor'])
                    ->where('estado', 'enviado_a_proveedor')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                return [
                    'pendientes' => $this->formatOrdenesPendientes($pendientes, $now),
                    'pendientes_count' => OrdenCompra::where('estado', 'pendiente')->count(),
                    'enviadas' => $this->formatOrdenesEnviadas($enviadas),
                    'enviadas_count' => $enviadas->count(),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading orders: ' . $e->getMessage());
                return [
                    'pendientes' => [],
                    'pendientes_count' => 0,
                    'enviadas' => [],
                    'enviadas_count' => 0,
                ];
            }
        });
    }

    private function formatOrdenesPendientes($ordenes, $now): array
    {
        return $ordenes->map(function ($orden) use ($now) {
            $total = $orden->total ?? 0;
            $diasRetraso = null;

            if ($orden->fecha_entrega_esperada) {
                $fechaEsperada = Carbon::parse($orden->fecha_entrega_esperada);
                if ($now->greaterThan($fechaEsperada)) {
                    $diasRetraso = (int) ceil($fechaEsperada->diffInDays($now));
                } else {
                    $diasRetraso = 0;
                }
            }

            return [
                'id' => $orden->id,
                'proveedor' => $orden->proveedor ? $orden->proveedor->nombre_razon_social : 'Proveedor no especificado',
                'total' => number_format($total, 2),
                'prioridad' => $orden->prioridad,
                'dias_retraso' => $diasRetraso,
                'fecha_creacion' => Carbon::parse($orden->created_at)->format('d/m/Y'),
                'fecha_esperada' => $orden->fecha_entrega_esperada ? Carbon::parse($orden->fecha_entrega_esperada)->format('d/m/Y') : 'No especificada',
            ];
        })->toArray();
    }

    private function formatOrdenesEnviadas($ordenes): array
    {
        return $ordenes->map(function ($orden) {
            $total = $orden->total ?? 0;

            return [
                'id' => $orden->id,
                'proveedor' => $orden->proveedor ? $orden->proveedor->nombre_razon_social : 'Proveedor no especificado',
                'total' => number_format($total, 2),
                'prioridad' => $orden->prioridad,
                'fecha_envio' => Carbon::parse($orden->updated_at)->format('d/m/Y'),
                'fecha_esperada' => $orden->fecha_entrega_esperada ? Carbon::parse($orden->fecha_entrega_esperada)->format('d/m/Y') : 'No especificada',
            ];
        })->toArray();
    }

    public function getCitasHoy(): array
    {
        return Cache::remember('panel_citas_hoy', 60, function () {
            $now = Carbon::now();

            $citas = Cita::with(['cliente', 'tecnico'])
                ->select('id', 'tipo_servicio', 'fecha_hora', 'cliente_id', 'tecnico_id', 'estado')
                ->whereDate('fecha_hora', $now->toDateString())
                ->whereIn('estado', ['en_proceso', 'pendiente'])
                ->orderByRaw("
                    CASE
                        WHEN estado = 'en_proceso' THEN 1
                        WHEN estado = 'pendiente' THEN 2
                        ELSE 999
                    END ASC
                ")
                ->orderBy('fecha_hora')
                ->get();

            return [
                'citas' => $citas->map(function ($cita) {
                    return [
                        'id' => $cita->id,
                        'cliente' => $cita->cliente ? $cita->cliente->nombre_razon_social : 'Desconocido',
                        'tecnico' => $cita->tecnico ? $cita->tecnico->nombre : 'Sin técnico asignado',
                        'titulo' => $cita->tipo_servicio,
                        'hora' => Carbon::parse($cita->fecha_hora)->format('H:i'),
                        'estado' => $cita->estado,
                        'estado_label' => $cita->estado === 'en_proceso' ? 'En Proceso' : 'Pendiente',
                    ];
                })->toArray(),
                'count' => $citas->count(),
            ];
        });
    }

    public function getMantenimientosCriticos(): array
    {
        return Cache::remember('panel_mantenimientos_criticos', 300, function () {
            $now = Carbon::now();

            try {
                $vencidos = Mantenimiento::with('carro')
                    ->where('proximo_mantenimiento', '<', $now)
                    ->where('estado', '!=', Mantenimiento::ESTADO_COMPLETADO)
                    ->orderBy('proximo_mantenimiento', 'asc')
                    ->limit(10)
                    ->get();

                $criticos = Mantenimiento::with('carro')
                    ->whereIn('prioridad', [Mantenimiento::PRIORIDAD_ALTA, Mantenimiento::PRIORIDAD_CRITICA])
                    ->where('proximo_mantenimiento', '<=', $now->copy()->addDays(7))
                    ->where('proximo_mantenimiento', '>=', $now)
                    ->orderBy('prioridad', 'desc')
                    ->orderBy('proximo_mantenimiento', 'asc')
                    ->limit(10)
                    ->get();

                $todos = $vencidos->concat($criticos);

                return [
                    'vencidos_count' => $vencidos->count(),
                    'criticos_count' => $criticos->count(),
                    'detalles' => $todos->map(function ($mantenimiento) {
                        return [
                            'id' => $mantenimiento->id,
                            'tipo' => $mantenimiento->tipo,
                            'carro' => $mantenimiento->carro ? [
                                'marca' => $mantenimiento->carro->marca,
                                'modelo' => $mantenimiento->carro->modelo,
                                'placa' => $mantenimiento->carro->placa
                            ] : null,
                            'proximo_mantenimiento' => $mantenimiento->proximo_mantenimiento,
                            'dias_restantes' => $mantenimiento->dias_restantes,
                            'prioridad' => $mantenimiento->prioridad,
                            'estado' => $mantenimiento->estado,
                        ];
                    })->toArray(),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading maintenances: ' . $e->getMessage());
                return [
                    'vencidos_count' => 0,
                    'criticos_count' => 0,
                    'detalles' => [],
                ];
            }
        });
    }
}
