<?php

namespace App\Services\Panel;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use DB;
use App\Support\EmpresaResolver;

class PanelChartsService
{
    public function getVentasMensuales(): array
    {
        $empresaId = EmpresaResolver::resolveId();
        $cacheKey = 'panel_ventas_mensuales_' . ($empresaId ?: 'global');

        return Cache::remember($cacheKey, 300, function () use ($empresaId) {
            $now = Carbon::now();
            $startDate = $now->copy()->subMonths(6)->startOfMonth();
            $endDate = $now->copy()->endOfMonth();

            try {
                $ventasPorMes = DB::table('ventas')
                    ->select(DB::raw('TO_CHAR(created_at, \'YYYY-MM\') as mes'), DB::raw('SUM(total) as total'))
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get();

                // Crear array con todos los meses del período
                $allMonths = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $monthKey = $currentDate->format('Y-m');
                    $allMonths[$monthKey] = 0;
                    $currentDate->addMonth();
                }

                // Rellenar con datos reales
                foreach ($ventasPorMes as $venta) {
                    $allMonths[$venta->mes] = round($venta->total, 2);
                }

                return [
                    'labels' => array_map(function ($monthKey) {
                        return Carbon::parse($monthKey . '-01')->format('M Y');
                    }, array_keys($allMonths)),
                    'data' => array_values($allMonths),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading sales chart data: ' . $e->getMessage());
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }

    public function getProductosMasVendidos(): array
    {
        $empresaId = EmpresaResolver::resolveId();
        $cacheKey = 'panel_productos_mas_vendidos_' . ($empresaId ?: 'global');

        return Cache::remember($cacheKey, 300, function () use ($empresaId) {
            $now = Carbon::now();

            try {
                $productos = DB::table('venta_items')
                    ->join('productos', 'venta_items.ventable_id', '=', 'productos.id')
                    ->select('productos.nombre', DB::raw('SUM(venta_items.cantidad) as total_vendido'))
                    ->where('venta_items.ventable_type', 'App\\Models\\Producto')
                    ->when($empresaId, function ($query) use ($empresaId) {
                        $query->where('venta_items.empresa_id', $empresaId)
                            ->where('productos.empresa_id', $empresaId);
                    })
                    ->where('venta_items.created_at', '>=', $now->copy()->subDays(30))
                    ->groupBy('productos.id', 'productos.nombre')
                    ->orderByDesc('total_vendido')
                    ->limit(5)
                    ->get();

                return [
                    'labels' => $productos->pluck('nombre')->toArray(),
                    'data' => $productos->pluck('total_vendido')->toArray(),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading top products chart data: ' . $e->getMessage());
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }

    public function getOrdenesEstados(): array
    {
        $empresaId = EmpresaResolver::resolveId();
        $cacheKey = 'panel_ordenes_estados_' . ($empresaId ?: 'global');

        return Cache::remember($cacheKey, 300, function () use ($empresaId) {
            try {
                $estados = DB::table('orden_compras')
                    ->select('estado', DB::raw('COUNT(*) as total'))
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->groupBy('estado')
                    ->get();

                $estadosMap = [
                    'pendiente' => 'Pendiente',
                    'enviado_a_proveedor' => 'Enviado',
                    'recibido' => 'Recibido',
                    'cancelado' => 'Cancelado',
                ];

                return [
                    'labels' => $estados->map(function ($item) use ($estadosMap) {
                        return $estadosMap[$item->estado] ?? $item->estado;
                    })->toArray(),
                    'data' => $estados->pluck('total')->toArray(),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading orders chart data: ' . $e->getMessage());
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }

    public function getClientesCrecimiento(): array
    {
        $empresaId = EmpresaResolver::resolveId();
        $cacheKey = 'panel_clientes_crecimiento_' . ($empresaId ?: 'global');

        return Cache::remember($cacheKey, 300, function () use ($empresaId) {
            $now = Carbon::now();
            $startDate = $now->copy()->subMonths(6)->startOfMonth();
            $endDate = $now->copy()->endOfMonth();

            try {
                $clientesPorMes = DB::table('clientes')
                    ->select(DB::raw('TO_CHAR(created_at, \'YYYY-MM\') as mes'), DB::raw('COUNT(*) as total'))
                    ->when($empresaId, fn($query) => $query->where('empresa_id', $empresaId))
                    ->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
                    ->groupBy('mes')
                    ->orderBy('mes')
                    ->get();

                // Crear array con todos los meses del período
                $allMonths = [];
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $monthKey = $currentDate->format('Y-m');
                    $allMonths[$monthKey] = 0;
                    $currentDate->addMonth();
                }

                // Rellenar con datos reales
                foreach ($clientesPorMes as $cliente) {
                    $allMonths[$cliente->mes] = (int) $cliente->total;
                }

                return [
                    'labels' => array_map(function ($monthKey) {
                        return Carbon::parse($monthKey . '-01')->format('M Y');
                    }, array_keys($allMonths)),
                    'data' => array_values($allMonths),
                ];
            } catch (\Exception $e) {
                \Log::error('Error loading clients chart data: ' . $e->getMessage());
                return [
                    'labels' => [],
                    'data' => [],
                ];
            }
        });
    }
}
