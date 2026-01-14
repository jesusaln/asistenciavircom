<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Carbon\Carbon;

class ReporteTecnicoController extends Controller
{
    /**
     * Muestra el reporte de ganancias por técnico
     */
    public function index(Request $request)
    {
        $tecnicos = User::tecnicosActivos()->get();

        // Filtros
        $tecnicoId = $request->input('tecnico_id');
        $periodo = $request->input('periodo', 'mes'); // dia, semana, mes, personalizado
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        // Determinar rango de fechas
        $rangoFechas = $this->determinarRangoFechas($periodo, $fechaInicio, $fechaFin);

        // Obtener datos del reporte
        $reporte = $this->generarReporte($tecnicoId, $rangoFechas['inicio'], $rangoFechas['fin']);

        return Inertia::render('Reportes/Tecnicos/Index', [
            'tecnicos' => $tecnicos,
            'reporte' => $reporte,
            'filtros' => [
                'tecnico_id' => $tecnicoId,
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'rango_fechas' => $rangoFechas,
            ],
        ]);
    }

    /**
     * API para obtener datos del reporte en JSON
     */
    public function datos(Request $request): JsonResponse
    {
        $tecnicoId = $request->input('tecnico_id');
        $periodo = $request->input('periodo', 'mes');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $rangoFechas = $this->determinarRangoFechas($periodo, $fechaInicio, $fechaFin);
        $reporte = $this->generarReporte($tecnicoId, $rangoFechas['inicio'], $rangoFechas['fin']);

        return response()->json($reporte);
    }

    /**
     * Determina el rango de fechas basado en el período
     */
    private function determinarRangoFechas(string $periodo, ?string $fechaInicio, ?string $fechaFin): array
    {
        $hoy = Carbon::now();

        switch ($periodo) {
            case 'dia':
                return [
                    'inicio' => $hoy->copy()->startOfDay(),
                    'fin' => $hoy->copy()->endOfDay(),
                ];

            case 'semana':
                return [
                    'inicio' => $hoy->copy()->startOfWeek(),
                    'fin' => $hoy->copy()->endOfWeek(),
                ];

            case 'mes':
                return [
                    'inicio' => $hoy->copy()->startOfMonth(),
                    'fin' => $hoy->copy()->endOfMonth(),
                ];

            case 'personalizado':
                return [
                    'inicio' => $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : $hoy->copy()->startOfMonth(),
                    'fin' => $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : $hoy->copy()->endOfMonth(),
                ];

            default:
                return [
                    'inicio' => $hoy->copy()->startOfMonth(),
                    'fin' => $hoy->copy()->endOfMonth(),
                ];
        }
    }

    /**
     * Genera el reporte de ganancias por técnico
     */
    private function generarReporte(?int $tecnicoId, Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $query = Venta::with(['vendedor', 'productos', 'servicios'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->whereNotNull('vendedor_type')
            ->where('vendedor_type', User::class);

        if ($tecnicoId) {
            $query->where('vendedor_id', $tecnicoId);
        }

        $ventas = $query->get();

        $reportePorTecnico = [];

        foreach ($ventas as $venta) {
            $tecnico = $venta->vendedor;
            if (!$tecnico || !$tecnico->es_tecnico)
                continue;

            $tecnicoId = $tecnico->id;
            $tecnicoNombre = $tecnico->name;

            if (!isset($reportePorTecnico[$tecnicoId])) {
                $reportePorTecnico[$tecnicoId] = [
                    'tecnico' => [
                        'id' => $tecnico->id,
                        'nombre' => $tecnicoNombre,
                        'email' => $tecnico->email,
                        'margen_productos' => $tecnico->margen_venta_productos,
                        'margen_servicios' => $tecnico->margen_venta_servicios,
                        'comision_instalacion' => $tecnico->comision_instalacion,
                    ],
                    'ventas' => [],
                    'totales' => [
                        'ventas' => 0,
                        'ventas_total' => 0,
                        'ganancia_base' => 0,
                        'margen_tecnico' => 0,
                        'comision_instalacion' => 0,
                        'comision_productos' => 0,
                        'comision_servicios' => 0,
                        'ganancia_total' => 0,
                    ],
                ];
            }

            $gananciaBase = 0;
            $margenTecnico = 0;
            $comisionInstalacion = 0;
            $comisionProductos = 0;
            $comisionServicios = 0;

            // Calcular ganancias de productos
            foreach ($venta->productos as $producto) {
                $pivot = $producto->pivot;
                $precioVenta = $pivot->precio - ($pivot->descuento ?? 0);
                $costo = $pivot->costo_unitario ?? $producto->precio_compra;
                $gananciaProducto = ($precioVenta - $costo) * $pivot->cantidad;

                $gananciaBase += $gananciaProducto;
                $comisionProductos += $gananciaProducto * ($producto->comision_vendedor / 100);
                $margenTecnico += $gananciaProducto * ($tecnico->margen_venta_productos / 100);
            }

            // Calcular ganancias de servicios
            foreach ($venta->servicios as $servicio) {
                $pivot = $servicio->pivot;
                $precioVenta = $pivot->precio - ($pivot->descuento ?? 0);
                $gananciaServicio = $precioVenta * ($servicio->margen_ganancia / 100) * $pivot->cantidad;

                $gananciaBase += $gananciaServicio;
                $comisionServicios += $servicio->comision_vendedor * $pivot->cantidad;
                $margenTecnico += $gananciaServicio * ($tecnico->margen_venta_servicios / 100);

                // Comisión por instalación
                if ($servicio->es_instalacion) {
                    $comisionInstalacion += $tecnico->comision_instalacion * $pivot->cantidad;
                }
            }

            $ventaData = [
                'id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'fecha' => $venta->fecha->format('Y-m-d'),
                'cliente' => $venta->cliente->nombre_razon_social ?? 'N/A',
                'productos_count' => $venta->productos->count(),
                'servicios_count' => $venta->servicios->count(),
                'total_venta' => $venta->total,
                'ganancia_base' => $gananciaBase,
                'margen_tecnico' => $margenTecnico,
                'comision_instalacion' => $comisionInstalacion,
                'comision_productos' => $comisionProductos,
                'comision_servicios' => $comisionServicios,
                'ganancia_total' => $gananciaBase + $margenTecnico + $comisionInstalacion + $comisionProductos + $comisionServicios,
            ];

            $reportePorTecnico[$tecnicoId]['ventas'][] = $ventaData;

            // Acumular totales
            $reportePorTecnico[$tecnicoId]['totales']['ventas'] += 1;
            $reportePorTecnico[$tecnicoId]['totales']['ventas_total'] += $venta->total;
            $reportePorTecnico[$tecnicoId]['totales']['ganancia_base'] += $gananciaBase;
            $reportePorTecnico[$tecnicoId]['totales']['margen_tecnico'] += $margenTecnico;
            $reportePorTecnico[$tecnicoId]['totales']['comision_instalacion'] += $comisionInstalacion;
            $reportePorTecnico[$tecnicoId]['totales']['comision_productos'] += $comisionProductos;
            $reportePorTecnico[$tecnicoId]['totales']['comision_servicios'] += $comisionServicios;
            $reportePorTecnico[$tecnicoId]['totales']['ganancia_total'] += $ventaData['ganancia_total'];
        }

        return array_values($reportePorTecnico);
    }
}
