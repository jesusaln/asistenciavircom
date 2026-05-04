<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\EmpresaResolver;
use Inertia\Inertia;

class GarantiaController extends Controller
{
    /**
     * ✅ FIX: Escape special LIKE characters to prevent unexpected search behavior
     */
    private function escapeLikePattern(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }

    /**
     * ✅ FIX: Reusable query for serie detail (eliminates duplicate code)
     */
    private function getSerieDetailQuery(string $numeroSerie)
    {
        $empresaId = EmpresaResolver::resolveId();

        return DB::table('producto_series as ps')
            ->leftJoin('venta_item_series as vis', 'vis.producto_serie_id', '=', 'ps.id')
            ->leftJoin('venta_items as vi', 'vi.id', '=', 'vis.venta_item_id')
            ->leftJoin('ventas as v', 'v.id', '=', 'vi.venta_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->leftJoin('productos as p', 'p.id', '=', 'ps.producto_id')
            ->select(
                'ps.id as producto_serie_id',
                'ps.numero_serie',
                'ps.estado as estado_serie',
                'ps.cita_id',
                'p.id as producto_id',
                'p.nombre as producto_nombre',
                'v.id as venta_id',
                'v.numero_venta',
                'v.created_at as venta_fecha',
                'c.id as cliente_id',
                'c.nombre_razon_social as cliente_nombre',
                'c.email as cliente_email',
                'c.telefono as cliente_telefono'
            )
            ->where('ps.numero_serie', $numeroSerie)
            ->when($empresaId, fn($query) => $query->where('ps.empresa_id', $empresaId))
            ->when($empresaId, fn($query) => $query->where('v.empresa_id', $empresaId))
            ->first();
    }

    /**
     * ✅ FIX: Apply search filter with proper LIKE escaping
     */
    private function addSearchFilter($query, string $search): void
    {
        $escaped = $this->escapeLikePattern($search);
        $query->where(function ($q) use ($escaped) {
            $q->where('ps.numero_serie', 'ilike', "%{$escaped}%")
                ->orWhere('p.nombre', 'ilike', "%{$escaped}%")
                ->orWhere('p.codigo', 'ilike', "%{$escaped}%")
                ->orWhere('c.nombre_razon_social', 'ilike', "%{$escaped}%")
                ->orWhere('c.email', 'ilike', "%{$escaped}%");
        });
    }

    private function getSeriesVendidasQuery()
    {
        $empresaId = EmpresaResolver::resolveId();

        return DB::table('producto_series as ps')
            ->join('venta_item_series as vis', 'vis.producto_serie_id', '=', 'ps.id')
            ->join('venta_items as vi', 'vi.id', '=', 'vis.venta_item_id')
            ->join('ventas as v', 'v.id', '=', 'vi.venta_id')
            ->join('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->join('productos as p', 'p.id', '=', 'ps.producto_id')
            ->leftJoin('almacenes as a', 'ps.almacen_id', '=', 'a.id')
            ->select(
                'ps.id as producto_serie_id',
                'ps.numero_serie',
                'ps.estado as estado_serie',
                'ps.created_at as serie_creada',
                'ps.cita_id',
                'p.id as producto_id',
                'p.nombre as producto_nombre',
                'p.codigo as producto_codigo',
                'p.dias_garantia', // ✅ FIX P3: Agregar días de garantía del producto
                'v.id as venta_id',
                'v.numero_venta',
                'v.created_at as venta_fecha',
                'c.id as cliente_id',
                'c.nombre_razon_social as cliente_nombre',
                'c.email as cliente_email',
                'c.telefono as cliente_telefono',
                'c.calle',
                'c.numero_exterior',
                'c.numero_interior',
                'c.colonia',
                'c.municipio',
                'c.estado',
                'c.codigo_postal',
                'a.nombre as almacen_nombre',
                'vi.precio',
                'vi.cantidad',
                // ✅ FIX: PostgreSQL-compatible date arithmetic (simplified)
                // In PostgreSQL: date - date = integer (number of days)
                // So: (fecha_vencimiento) - CURRENT_DATE = dias_restantes
                DB::raw('((v.created_at::date + COALESCE(p.dias_garantia, 365)) - CURRENT_DATE) as dias_restantes_garantia'),
                DB::raw('(v.created_at::date + COALESCE(p.dias_garantia, 365)) as fecha_vencimiento_garantia'),
                DB::raw('CASE WHEN (v.created_at::date + COALESCE(p.dias_garantia, 365)) >= CURRENT_DATE THEN 1 ELSE 0 END as garantia_vigente')
            )
            // Excluir ventas canceladas
            ->where('v.estado', '!=', 'cancelada')
            ->when($empresaId, fn($query) => $query->where('ps.empresa_id', $empresaId))
            ->when($empresaId, fn($query) => $query->where('v.empresa_id', $empresaId));
    }

    public function index(Request $request)
    {
        $serie = trim((string) $request->query('serie', ''));

        // Si hay búsqueda por serie específica
        if ($serie !== '') {
            // ✅ FIX: Use reusable helper method instead of duplicate query
            $resultado = $this->getSerieDetailQuery($serie);

            return Inertia::render('Garantias/BuscarSerie', [
                'serie' => $serie,
                'resultado' => $resultado,
                'seriesVendidas' => $this->getSeriesVendidasQuery()->orderBy('v.created_at', 'desc')->paginate(10),
            ]);
        }

        // Lista de todas las series vendidas (excluyendo ventas canceladas)
        $query = $this->getSeriesVendidasQuery();

        // Aplicar filtros
        if ($request->filled('search')) {
            $this->addSearchFilter($query, $request->search);
        }

        if ($request->filled('estado')) {
            $query->where('ps.estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('v.created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('v.created_at', '<=', $request->fecha_hasta);
        }

        $seriesVendidas = $query->orderBy('v.created_at', 'desc')->paginate(50);

        // Calcular estadísticas para los cards
        $statsQuery = $this->getSeriesVendidasQuery();
        $allSeries = $statsQuery->get();

        $stats = [
            'vigentes' => $allSeries->where('garantia_vigente', 1)->count(),
            'vencidas' => $allSeries->where('garantia_vigente', 0)->count(),
            'conCita' => $allSeries->whereNotNull('cita_id')->count(),
        ];

        return Inertia::render('Garantias/Index', [
            'seriesVendidas' => $seriesVendidas,
            'filters' => $request->only(['search', 'estado', 'fecha_desde', 'fecha_hasta', 'garantia']),
            'stats' => $stats,
        ]);
    }

    public function create(Request $request)
    {
        // Obtener series vendidas para mostrar en la lista
        $query = $this->getSeriesVendidasQuery();
        $resultado = null;
        $search = $request->input('search');

        // Aplicar búsqueda si existe
        if ($search) {
            // 1. Intentar buscar coincidencia exacta para el card de detalle
            // ✅ FIX: Use reusable helper method
            $resultado = $this->getSerieDetailQuery($search);

            // 2. Filtrar la lista general (✅ FIX: Use helper with LIKE escaping)
            $this->addSearchFilter($query, $search);
        }

        $seriesVendidas = $query->orderBy('v.created_at', 'desc')->paginate(20);

        // Página para buscar series de garantía
        return Inertia::render('Garantias/BuscarSerie', [
            'serie' => $search,
            'resultado' => $resultado,
            'seriesVendidas' => $seriesVendidas,
            'filters' => $request->only(['search']),
        ]);
    }

    public function crearCitaGarantia($serieId)
    {
        $empresaId = EmpresaResolver::resolveId();

        // ✅ FIX: Validate serieId is a positive integer
        $serieId = filter_var($serieId, FILTER_VALIDATE_INT);
        if ($serieId === false || $serieId <= 0) {
            return response()->json(['error' => 'ID de serie inválido'], 400);
        }

        // ✅ FIX P1: Envolver todo en transacción para prevenir race conditions
        try {
            return DB::transaction(function () use ($serieId, $empresaId) {
                // ✅ FIX P1: Usar lockForUpdate para prevenir race conditions
                // Esto bloquea la fila hasta que la transacción se complete
                $serieInfo = DB::table('producto_series as ps')
                    ->join('venta_item_series as vis', 'vis.producto_serie_id', '=', 'ps.id')
                    ->join('venta_items as vi', 'vi.id', '=', 'vis.venta_item_id')
                    ->join('ventas as v', 'v.id', '=', 'vi.venta_id')
                    ->join('clientes as c', 'c.id', '=', 'v.cliente_id')
                    ->join('productos as p', 'p.id', '=', 'ps.producto_id')
                    ->where('ps.id', $serieId)
                    ->when($empresaId, fn($query) => $query->where('ps.empresa_id', $empresaId))
                    ->when($empresaId, fn($query) => $query->where('v.empresa_id', $empresaId))
                    ->lockForUpdate() // ✅ Previene que otro proceso lea esta fila hasta commit
                    ->select(
                        'ps.id as producto_serie_id',
                        'ps.numero_serie',
                        'ps.estado as estado_serie',
                        'ps.cita_id',
                        'p.id as producto_id',
                        'p.nombre as producto_nombre',
                        'p.dias_garantia',
                        'c.id as cliente_id',
                        'c.nombre_razon_social as cliente_nombre',
                        'c.email as cliente_email',
                        'c.telefono as cliente_telefono',
                        'c.calle',
                        'c.numero_exterior',
                        'c.numero_interior',
                        'c.colonia',
                        'c.municipio',
                        'c.estado',
                        'c.codigo_postal',
                        'v.id as venta_id',
                        'v.created_at as fecha_venta'
                    )
                    ->first();

                if (!$serieInfo) {
                    return response()->json(['error' => 'Serie no encontrada'], 404);
                }

                // Validar estado de serie
                if ($serieInfo->estado_serie !== 'vendido') {
                    return response()->json([
                        'error' => 'Estado de serie inválido',
                        'mensaje' => "La serie está en estado '{$serieInfo->estado_serie}'. Solo se pueden crear citas de garantía para series vendidas.",
                        'estado_actual' => $serieInfo->estado_serie,
                        'serie' => $serieInfo->numero_serie
                    ], 400);
                }

                // Validar vigencia de garantía
                $diasGarantia = $serieInfo->dias_garantia ?? 365; // Default 1 año
                $fechaVenta = \Carbon\Carbon::parse($serieInfo->fecha_venta);
                $fechaVencimiento = $fechaVenta->copy()->addDays($diasGarantia);
                $diasRestantes = now()->diffInDays($fechaVencimiento, false);

                if ($diasRestantes < 0) {
                    $diasVencida = abs($diasRestantes);
                    return response()->json([
                        'error' => 'Garantía vencida',
                        'mensaje' => "La garantía venció hace {$diasVencida} días. No se puede crear cita de garantía.",
                        'fecha_venta' => $fechaVenta->format('d/m/Y'),
                        'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                        'dias_vencida' => $diasVencida,
                        'serie' => $serieInfo->numero_serie,
                        'producto' => $serieInfo->producto_nombre
                    ], 400);
                }

                // Verificar si ya existe una cita para esta serie (doble verificación dentro del lock)
                if ($serieInfo->cita_id) {
                    return response()->json([
                        'error' => 'Esta serie ya tiene una cita asociada',
                        'cita_id' => $serieInfo->cita_id,
                        'mensaje' => 'Esta garantía ya tiene una cita creada (Cita #' . $serieInfo->cita_id . '). No se pueden crear múltiples citas para la misma garantía.'
                    ], 400);
                }

                // Construir dirección completa
                $direccion = trim(implode(' ', array_filter([
                    $serieInfo->calle,
                    $serieInfo->numero_exterior,
                    $serieInfo->numero_interior ? 'Int. ' . $serieInfo->numero_interior : null,
                    $serieInfo->colonia,
                    $serieInfo->municipio,
                    $serieInfo->estado,
                    $serieInfo->codigo_postal
                ])));

                // Crear descripción para la cita
                $descripcion = "Garantía - Serie: {$serieInfo->numero_serie} - Producto: {$serieInfo->producto_nombre}";

                // Preparar parámetros para la URL de creación de cita
                $params = [
                    'cliente_id' => $serieInfo->cliente_id,
                    'numero_serie' => $serieInfo->numero_serie,
                    'descripcion' => $descripcion,
                    'direccion' => $direccion,
                    'tipo_servicio' => 'garantia',
                    'producto_serie_id' => $serieId,
                ];

                $queryString = http_build_query($params);
                $url = route('citas.create') . '?' . $queryString;

                return response()->json([
                    'success' => true,
                    'url' => $url,
                    'cliente' => $serieInfo->cliente_nombre,
                    'serie' => $serieInfo->numero_serie,
                    'direccion' => $direccion,
                    'garantia_info' => [
                        'dias_restantes' => max(0, $diasRestantes),
                        'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                        'vigente' => $diasRestantes >= 0
                    ]
                ]);
            }); // Fin de DB::transaction

        } catch (\Exception $e) {
            \Log::error('Error al crear cita de garantía: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }
}
