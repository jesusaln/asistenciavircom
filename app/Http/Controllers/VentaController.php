<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Models\Venta;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\User;
use App\Services\Ventas\VentaCreationService;
use App\Services\Ventas\VentaUpdateService;
use App\Services\Ventas\VentaQueryService;
use App\Services\Ventas\VentaCancellationService;
use App\Services\Ventas\VentaDeletionService;
use App\Services\Ventas\VentaPaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class VentaController extends Controller
{
    public function __construct(
        private readonly VentaCreationService $ventaCreationService,
        private readonly VentaUpdateService $ventaUpdateService,
        private readonly VentaQueryService $ventaQueryService,
        private readonly VentaCancellationService $ventaCancellationService,
        private readonly VentaDeletionService $ventaDeletionService,
        private readonly VentaPaymentService $ventaPaymentService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $this->ventaQueryService->getVentasList($request);
        return Inertia::render('Ventas/Index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            if ($request->filled('cita_id')) {
                $cita = Cita::with('venta:id,cita_id,numero_venta')->find($request->cita_id);
                if ($cita && $cita->venta && ! $request->boolean('nueva')) {
                    return redirect()
                        ->route('ventas.show', $cita->venta)
                        ->with('warning', 'Esta cita ya tiene la venta '.$cita->venta->numero_venta.' vinculada. Si debes registrar otra operación, hazlo sin vincular cita o contacta a administración.');
                }
            }

            $data = $this->ventaQueryService->getCreateData($request);

            return Inertia::render('Ventas/Create', $data);
        } catch (\Exception $e) {
            Log::error('Error loading create venta form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario de creación');
        }
    }

    /**
     * JSON: ventas recientes del cliente de la cita (modal en reporte «Citas por técnico»).
     */
    public function ventasClienteCandidatasParaCita(Request $request, Cita $cita)
    {
        if (! $request->user()?->can('view ventas')) {
            abort(403);
        }

        $cita->loadMissing(['cliente:id,nombre_razon_social', 'venta:id,cita_id,numero_venta']);

        $ventas = $this->ventaQueryService->getVentasClienteCandidatasForCita($cita);

        return response()->json([
            'cita' => [
                'id' => $cita->id,
                'cliente_id' => $cita->cliente_id,
                'cliente_nombre' => $cita->cliente?->nombre_razon_social,
                'venta_id' => $cita->venta?->id,
            ],
            'ventas' => $ventas,
        ]);
    }

    /**
     * Vincular una venta existente (mismo cliente) a una cita sin venta asociada.
     * Una cita solo puede tener una venta; una venta solo puede enlazarse a una cita si aún no tenía otra.
     */
    public function vincularVentaACita(Request $request, Cita $cita)
    {
        $request->validate([
            'venta_id' => 'required|integer|exists:ventas,id',
        ]);

        $venta = Venta::query()->findOrFail((int) $request->venta_id);

        if ((int) ($venta->empresa_id ?? 0) !== (int) ($cita->empresa_id ?? 0)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La venta no pertenece a la misma empresa que la cita.',
                ], 403);
            }
            abort(403, 'La venta no pertenece a la misma empresa que la cita.');
        }

        if ((int) $venta->cliente_id !== (int) $cita->cliente_id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'El cliente de la venta debe ser el mismo que el de la cita.',
                    'errors' => ['venta_id' => ['El cliente de la venta debe ser el mismo que el de la cita.']],
                ], 422);
            }

            return redirect()->back()->withErrors([
                'venta_id' => 'El cliente de la venta debe ser el mismo que el de la cita.',
            ]);
        }

        $otra = Venta::query()->where('cita_id', $cita->id)->where('id', '!=', $venta->id)->exists();
        if ($otra) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta cita ya tiene otra venta vinculada.',
                    'errors' => ['cita_id' => ['Esta cita ya tiene otra venta vinculada.']],
                ], 422);
            }

            return redirect()->back()->withErrors([
                'cita_id' => 'Esta cita ya tiene otra venta vinculada.',
            ]);
        }

        if ($venta->cita_id && (int) $venta->cita_id !== (int) $cita->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta venta ya está vinculada a otra cita.',
                    'errors' => ['venta_id' => ['Esta venta ya está vinculada a la cita #'.$venta->cita_id.'. Desvincúlala primero desde la venta si aplica.']],
                ], 422);
            }

            return redirect()->back()->withErrors([
                'venta_id' => 'Esta venta ya está vinculada a la cita #'.$venta->cita_id.'. Desvincúlala primero desde la venta si aplica.',
            ]);
        }

        $venta->cita_id = $cita->id;
        $venta->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Venta vinculada a la cita.',
                'venta_id' => $venta->id,
            ]);
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', 'Venta '.$venta->numero_venta.' vinculada a la cita #'.$cita->id.'.');
    }

    /**
     * Store a newly created resource in storage.
     * ✅ REFACTORED: Now uses VentaCreationService for business logic
     */
    public function store(StoreVentaRequest $request)
    {
        try {
            // ✅ Atribución de vendedor (crucial para Ionic app)
            // Se prioriza el vendedor_id enviado (admin/web) sobre el usuario autenticado (móvil)
            $vendedorId = $request->input('vendedor_id', auth()->id());
            
            // Buscar sin scopes para validar pertenencia real a la empresa
            $vendedor = User::withoutGlobalScopes()->find($vendedorId);

            // Validar que el vendedor pertenezca a la misma empresa
            $currentEmpresaId = \App\Support\EmpresaResolver::resolveId();
            if ($vendedorId && (!$vendedor || $vendedor->empresa_id !== $currentEmpresaId)) {
                return $this->errorResponse($request, 'El vendedor seleccionado no pertenece a su empresa.', 422);
            }

            // Validar almacén
            $almacenId = $request->input('almacen_id');
            $almacen = Almacen::withoutGlobalScopes()->find($almacenId);
            if ($almacenId && (!$almacen || $almacen->empresa_id !== $currentEmpresaId)) {
                return $this->errorResponse($request, 'El almacén seleccionado no pertenece a su empresa.', 422);
            }

            $venta = $this->ventaCreationService->createVenta($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Venta creada exitosamente',
                    'id' => $venta->id,
                    'numero_venta' => $venta->numero_venta
                ], 201);
            }

            return redirect()->route('ventas.index')->with('success', 'Venta creada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\App\Exceptions\StockInsuficienteException $e) {
            Log::warning('Stock insuficiente al crear venta: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [
                        'stock' => $e->getMessage(),
                        'stock_type' => 'stock_error',
                        'stock_details' => $e->getDetails()
                    ]
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error creando venta: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al crear la venta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        try {
            if (!$venta->exists) {
                $ventaId = request()->route('venta');
                $venta = Venta::findOrFail($ventaId);
            }
            
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
            if (!$venta->exists) {
                $ventaId = request()->route('venta');
                $venta = Venta::findOrFail($ventaId);
            }

            $data = $this->ventaQueryService->getVentaEditData($venta);
            return Inertia::render('Ventas/Edit', $data);
        } catch (\Exception $e) {
            Log::error('Error loading edit venta form: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el formulario de edición');
        }
    }

    /**
     * Update the specified resource in storage.
     * ✅ REFACTORED: Now uses VentaUpdateService for business logic
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        if (!$venta->exists) {
            $ventaId = $request->route('venta');
            $venta = Venta::findOrFail($ventaId);
        }

        try {
            $validatedData = $request->validated();

            // ✅ Si la venta ya tiene entrega de dinero, solo observaciones y/o vendedor/técnico asignado
            if ($venta->entregaDinero()->exists()) {
                $payload = [
                    'notas' => $validatedData['notas'] ?? $venta->notas,
                ];
                if (array_key_exists('vendedor_id', $validatedData) && $validatedData['vendedor_id'] !== null && $validatedData['vendedor_id'] !== '') {
                    $resolved = $this->ventaCreationService->resolveVendedorAttribution($validatedData, $request->user());
                    $payload['vendedor_id'] = $resolved['vendedor_id'];
                    $payload['vendedor_type'] = $resolved['vendedor_type'];
                }
                $venta->update($payload);
                return $this->successResponse($request, 'Venta actualizada (corte ya registrado: solo notas y vendedor asignado)');
            }

            $this->ventaUpdateService->updateVenta($venta, $validatedData);
            return $this->successResponse($request, 'Venta actualizada exitosamente');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Error de validación',
                    'errors' => $e->errors(),
                ], 422);
            }

            throw $e;
        } catch (\Exception $e) {
            Log::error('Error actualizando venta: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al actualizar la venta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancelar una venta y devolver inventario
     */
    public function cancelar(Request $request, Venta $venta)
    {
        if (!$venta->exists) {
            $ventaId = $request->route('venta');
            $venta = Venta::findOrFail($ventaId);
        }

        try {
            $motivo = $request->input('motivo');
            $force = $request->boolean('force_with_payments', false);

            $this->ventaCancellationService->cancelVenta($venta, $motivo, $force);
            return $this->successResponse($request, 'Venta cancelada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Error cancelando venta: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al cancelar la venta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Marcar una venta como pagada (flujo rápido)
     */
    public function marcarPagado(Request $request, Venta $venta)
    {
        if (!$venta->exists) {
            $ventaId = $request->route('venta');
            $venta = Venta::findOrFail($ventaId);
        }

        try {
            $this->ventaPaymentService->markAsPaid($venta, $request->all());
            return $this->successResponse($request, 'Venta marcada como pagada exitosamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error marcando venta como pagada: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al procesar el pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * ✅ HIGH PRIORITY FIX #4: Enhanced validation with complete integrity checks
     */
    public function destroy(Request $request, Venta $venta)
    {
        if (!$venta->exists) {
            $ventaId = $request->route('venta');
            $venta = Venta::findOrFail($ventaId);
        }

        try {
            // Si la venta no está cancelada, intentar cancelarla primero
            if ($venta->estado?->value !== \App\Enums\EstadoVenta::Cancelada->value) {
                $motivo = $request->input('motivo', 'Cancelación automática y forzada desde listado');
                $force = true;
                $this->ventaCancellationService->cancelVenta($venta, $motivo, $force);
                $venta->refresh();
            }

            $this->ventaDeletionService->deleteVenta($venta);
            return $this->successResponse($request, 'Venta eliminada exitosamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando venta: ' . $e->getMessage());
            return $this->errorResponse($request, 'Error al eliminar la venta: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener el siguiente número de venta disponible (Preview)
     */
    public function obtenerSiguienteNumero()
    {
        try {
            $siguienteNumero = app(\App\Services\Folio\FolioService::class)->previewNextFolio('venta');
        } catch (\Exception $e) {
            Log::error('Error generating folio preview: ' . $e->getMessage());
            $siguienteNumero = 'V' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        }
        return response()->json(['siguiente_numero' => $siguienteNumero]);
    }

    /**
     * Helper para respuestas de error
     */
    private function errorResponse($request, $message, $code = 400)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], $code);
        }
        return redirect()->back()->withInput()->with('error', $message);
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
}
