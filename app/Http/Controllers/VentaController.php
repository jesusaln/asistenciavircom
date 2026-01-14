<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Venta;
use App\Models\VentaAuditLog;
use App\Services\StockValidationService;
use App\Services\Ventas\VentaCreationService;
use App\Services\Ventas\VentaUpdateService;
use App\Services\Ventas\VentaCancellationService;
use App\Services\Ventas\VentaQueryService;
use App\Services\Ventas\VentaDeletionService;
use App\Services\Ventas\VentaPaymentService;
use App\Services\Ventas\VentaValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


use App\Services\Cfdi\CfdiService;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function __construct(
        private readonly VentaCreationService $ventaCreationService,
        private readonly VentaUpdateService $ventaUpdateService,
        private readonly VentaCancellationService $ventaCancellationService,
        private readonly VentaQueryService $ventaQueryService,
        private readonly VentaDeletionService $ventaDeletionService,
        private readonly VentaPaymentService $ventaPaymentService,
        private readonly VentaValidationService $ventaValidationService
    ) {
        $this->authorizeResource(Venta::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->ventaQueryService->getVentasList($request);
            return Inertia::render('Ventas/Index', $data);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar las ventas');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $data = $this->ventaQueryService->getCreateData($request);
            return Inertia::render('Ventas/Create', $data);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@create: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario de creación');
        }
    }

    /**
     * Synchronize PostgreSQL sequence for ventas table
     */
    private function sincronizarSecuenciaVentas(): void
    {
        try {
            DB::statement("
                SELECT setval(
                    pg_get_serial_sequence('ventas', 'id'),
                    COALESCE(MAX(id), 1),
                    true
                ) FROM ventas
            ");
        } catch (\Exception $e) {
            Log::warning('Error sincronizando secuencia de ventas', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * âœ… REFACTORED: Now uses VentaCreationService for business logic
     */
    public function store(StoreVentaRequest $request)
    {
        try {
            // Sanitize series data
            $validatedData = $request->validated();
            if (isset($validatedData['productos'])) {
                $validatedData['productos'] = $this->ventaValidationService->sanitizeProductData($validatedData['productos']);

                // Validate uniqueness
                $seriesErrors = $this->ventaValidationService->validateSeriesUniqueness($validatedData['productos']);
                if (!empty($seriesErrors)) {
                    throw new \Exception(implode(', ', $seriesErrors));
                }
            }

            // âœ… REFACTORED: Use VentaCreationService instead of inline logic
            $venta = $this->ventaCreationService->createVenta($validatedData);

            // âœ… Log successful creation
            VentaAuditLog::logAction(
                $venta->id,
                'created',
                null,
                $venta->estado->value,
                [
                    'total' => $venta->total,
                    'productos_count' => $venta->items()->where('ventable_type', \App\Models\Producto::class)->count(),
                    'servicios_count' => $venta->items()->where('ventable_type', \App\Models\Servicio::class)->count(),
                ],
                'Venta created successfully via VentaCreationService'
            );

            return redirect()->route('ventas.index')
                ->with('success', 'Venta creada exitosamente.');

        } catch (\Exception $e) {
            // âœ… Enhanced error handling
            Log::error('Error creating venta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            VentaAuditLog::logAction(
                null,
                'creation_failed',
                null,
                null,
                ['error' => $e->getMessage()],
                'Venta creation failed'
            );

            // Si es una petición Inertia/XHR, retornar error usando withErrors para activar onError
            // Inertia espera una redirección con errores en la sesión, no un JSON directo
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Venta $venta)
    {
        try {
            $data = $this->ventaQueryService->getVentaDetails($venta);
            return Inertia::render('Ventas/Show', $data);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@show: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los detalles de la venta');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venta $venta)
    {
        try {
            $data = $this->ventaQueryService->getVentaEditData($venta);
            return Inertia::render('Ventas/Edit', $data);
        } catch (\Exception $e) {
            Log::error('Error en VentaController@edit: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario de edición');
        }
    }

    /**
     * Update the specified resource in storage.
     * âœ… REFACTORED: Now uses VentaUpdateService for business logic
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        try {
            // Sanitize series data
            $validatedData = $request->validated();
            if (isset($validatedData['productos'])) {
                $validatedData['productos'] = $this->ventaValidationService->sanitizeProductData($validatedData['productos']);
            }

            // âœ… REFACTORED: Use VentaUpdateService instead of inline logic
            $ventaUpdated = $this->ventaUpdateService->updateVenta($venta, $validatedData);

            // âœ… Log successful update
            VentaAuditLog::logAction(
                $ventaUpdated->id,
                'updated',
                json_encode($venta->getOriginal()),
                json_encode($ventaUpdated->toArray()),
                [
                    'total_before' => $venta->total,
                    'total_after' => $ventaUpdated->total,
                ],
                'Venta updated successfully via VentaUpdateService'
            );

            return redirect()->route('ventas.index')->with('success', 'Venta actualizada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error updating venta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'venta_id' => $venta->id,
                'user_id' => Auth::id(),
            ]);

            VentaAuditLog::logAction(
                $venta->id,
                'update_failed',
                null,
                null,
                ['error' => $e->getMessage()],
                'Venta update failed'
            );

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar la venta: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * ✅ FIX Error #3: Validate series availability in real-time
     * Called before form submission to ensure series are still available
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validarSeries(Request $request)
    {
        $productos = $request->input('productos', []);

        try {
            $productos = $this->ventaValidationService->sanitizeProductData($productos);
            $errors = $this->ventaValidationService->validateSeriesUniqueness($productos);

            if (!empty($errors)) {
                return response()->json([
                    'valid' => false,
                    'errors' => $errors
                ]);
            }

            // Re-validate against stock service for availability
            $almacenId = $request->input('almacen_id');
            return response()->json([
                'valid' => empty($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error validando series', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'valid' => false,
                'errors' => ['Error al validar series: ' . $e->getMessage()]
            ], 500);
        }
    }








    /**
     * Mark sale as paid.
     * ✅ REFACTORED: delegating to VentaPaymentService
     */
    public function marcarPagado(Request $request, Venta $venta)
    {
        try {
            Log::info('Marcar pagado request', [
                'venta_id' => $venta->id,
                'numero_venta' => $venta->numero_venta,
                'user_id' => Auth::id(),
            ]);

            $this->ventaPaymentService->markAsPaid($venta, $request->all());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta marcada como pagada correctamente.',
                ]);
            }

            return back()->with('success', 'Venta marcada como pagada correctamente.');

        } catch (\Exception $e) {
            Log::error('Error en VentaController@marcarPagado: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Facturar una venta (Generar CFDI 4.0)
     */
    public function facturar(Request $request, Venta $venta, CfdiService $cfdiService)
    {
        $validated = $request->validate([
            'tipo_factura' => 'nullable|in:ingreso,anticipo',
            'cfdi_relacion_tipo' => 'nullable|in:01,02,03,04,05,06,07',
            'cfdi_relacion_uuids' => 'nullable|array',
            'cfdi_relacion_uuids.*' => 'string|uuid',
            'anticipo_monto' => 'nullable|numeric|min:0.01',
            'anticipo_metodo_pago' => 'nullable|in:efectivo,transferencia,cheque,tarjeta,otros',
        ]);

        $tipoFactura = $validated['tipo_factura'] ?? 'ingreso';

        if ($tipoFactura === 'anticipo') {
            if (empty($validated['anticipo_monto']) || empty($validated['anticipo_metodo_pago'])) {
                return back()->withErrors([
                    'anticipo_monto' => 'Monto y método de pago son obligatorios para facturar anticipo.',
                ]);
            }

            $result = $cfdiService->facturarAnticipo(
                $venta,
                (float) $validated['anticipo_monto'],
                $validated['anticipo_metodo_pago']
            );
        } else {
            $options = [
                'tipo_factura' => 'ingreso',
                'cfdi_relacion_tipo' => $validated['cfdi_relacion_tipo'] ?? null,
                'cfdi_relacion_uuids' => $validated['cfdi_relacion_uuids'] ?? [],
            ];
            $result = $cfdiService->facturarVenta($venta, $options);
        }

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Cancelar la factura de una venta
     */
    public function cancelarFactura(Request $request, Venta $venta, \App\Services\Cfdi\CfdiCancelService $cancelService)
    {
        Log::info('=== CANCELAR FACTURA ===', [
            'venta_id' => $venta->id,
            'numero_venta' => $venta->numero_venta,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'motivo' => 'required|string|in:01,02,03,04',
            'folio_sustitucion' => 'nullable|string|uuid|required_if:motivo,01',
        ]);

        // Buscar CFDI activo (no cancelado) de la venta
        $cfdi = $venta->cfdi_actual;

        Log::info('CFDI actual desde relación:', ['cfdi_id' => $cfdi?->id, 'cfdi_uuid' => $cfdi?->uuid]);

        if (!$cfdi) {
            // Intentar buscar el último válido en la colección
            $cfdi = $venta->cfdis()
                ->whereNotNull('uuid')
                ->where('estatus', '!=', 'cancelado')
                ->latest()
                ->first();

            Log::info('CFDI buscado manualmente:', ['cfdi_id' => $cfdi?->id, 'cfdi_uuid' => $cfdi?->uuid, 'estatus' => $cfdi?->estatus]);
        }

        if (!$cfdi) {
            Log::warning('No se encontró CFDI para cancelar');
            return back()->with('error', 'No se encontró una factura válida para cancelar.');
        }

        Log::info('Llamando a CfdiCancelService->cancelar()');

        // Llamar al servicio de cancelación
        $result = $cancelService->cancelar($cfdi, $validated['motivo'], $validated['folio_sustitucion']);

        Log::info('Resultado de cancelación:', $result);

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        // Actualizar estado de la venta a cancelada
        $venta->estado = \App\Enums\EstadoVenta::Cancelada;
        $venta->save();

        // Registrar en bitácora de auditoría
        VentaAuditLog::logAction(
            $venta->id,
            'cfdi_cancelled',
            null,
            null,
            ['uuid' => $cfdi->uuid, 'motivo' => $validated['motivo']],
            'Factura cancelada manualmente'
        );

        return back()->with('success', $result['message']);
    }

    /**
     * Helper method for error responses
     */
    private function errorResponse($request, $message, $code = 400)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'error' => $message], $code);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * Limpiar cachÃ© de catÃ¡logos cuando se actualizan
     */
    private function clearCatalogCache()
    {
        Cache::forget('ventas_catalogs');
        Log::info('CatÃ¡logo de ventas cache cleared');
    }

    /**
     * Helper method for success responses
     */
    private function successResponse($request, $message, $route = 'ventas.index')
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     * âœ… HIGH PRIORITY FIX #4: Enhanced validation with complete integrity checks
     */
    public function destroy(Request $request, $id)
    {
        try {
            $venta = Venta::findOrFail($id);
            $this->ventaDeletionService->deleteVenta($venta);
            return $this->successResponse($request, 'Venta eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando venta: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al eliminar la venta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper to load all necessary options for edit view
     */
    private function loadEditOptions(array $selectedProductIds = [], array $selectedServiceIds = []): array
    {
        // âœ… P3 FIX #11: Optimizar como create() - con lÃ­mites y solo campos necesarios
        $clientes = \App\Models\Cliente::select('id', 'nombre_razon_social', 'rfc', 'email', 'price_list_id', 'tipo_persona', 'credito_activo', 'limite_credito')
            ->orderBy('nombre_razon_social')
            ->limit(500)
            ->with('priceList:id,nombre,clave')
            ->get();

        $productos = \App\Models\Producto::select('id', 'nombre', 'codigo', 'precio_venta', 'stock', 'categoria_id', 'marca_id', 'requiere_serie', 'tipo_producto')
            ->with(['categoria:id,nombre', 'marca:id,nombre', 'kitItems.item'])
            ->where(function ($q) use ($selectedProductIds) {
                $q->where('estado', 'activo')
                    ->orWhereIn('id', $selectedProductIds);
            })
            ->limit(1000)
            ->get()
            ->map(function ($producto) {
                $producto->precios_listas = $producto->precios->mapWithKeys(function ($precio) {
                    return [$precio->price_list_id => (float) $precio->precio];
                });
                unset($producto->precios); // Limpiar relaciÃ³n
                return $producto;
            });

        $servicios = \App\Models\Servicio::select('id', 'nombre', 'descripcion', 'precio', 'comision_vendedor')
            ->where(function ($q) use ($selectedServiceIds) {
                $q->where('estado', 'activo')
                    ->orWhereIn('id', $selectedServiceIds);
            })
            ->limit(500)
            ->get();

        $almacenes = \App\Models\Almacen::select('id', 'nombre', 'descripcion', 'ubicacion', 'estado')
            ->where('estado', 'activo')
            ->get();

        // âœ… P3 FIX #11: Usar cachÃ© para catÃ¡logos SAT
        $catalogs = Cache::remember('ventas_catalogs', 604800, function () {
            return [
                'regimenes_fiscales' => \App\Models\SatRegimenFiscal::select('clave', 'descripcion')->get(),
                'usos_cfdi' => \App\Models\SatUsoCfdi::select('clave', 'descripcion')->get(),
                'estados' => \App\Models\SatEstado::select('clave', 'nombre')->get(),
            ];
        });

        return [
            'clientes' => $clientes,
            'productos' => $productos,
            'servicios' => $servicios,
            'almacenes' => $almacenes,
            'priceLists' => \App\Models\PriceList::activas()->select('id', 'nombre')->get(),
            'catalogs' => $catalogs,
            'defaults' => [
                'ivaPorcentaje' => (float) \App\Services\EmpresaConfiguracionService::getIvaPorcentaje(),
                'isrPorcentaje' => \App\Services\EmpresaConfiguracionService::getIsrPorcentaje(),
                'enableIsr' => \App\Services\EmpresaConfiguracionService::isIsrEnabled(),
                'enableRetencionIva' => \App\Services\EmpresaConfiguracionService::isRetencionIvaEnabled(),
                'enableRetencionIsr' => \App\Services\EmpresaConfiguracionService::isRetencionIsrEnabled(),
                'retencionIvaPorcentaje' => \App\Services\EmpresaConfiguracionService::getRetencionIvaDefault(),
                'retencionIsrPorcentaje' => \App\Services\EmpresaConfiguracionService::getRetencionIsrDefault(),
            ],
        ];
    }
    /**
     * Obtener el siguiente número de venta disponible (Preview)
     */
    public function obtenerSiguienteNumero()
    {
        try {
            $siguienteNumero = app(\App\Services\Folio\FolioService::class)->previewNextFolio('venta');
        } catch (\Exception $e) {
            $siguienteNumero = 'V000';
        }
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }
}
