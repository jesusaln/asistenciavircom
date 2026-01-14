<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PagoPrestamoController extends Controller
{
    private const ITEMS_PER_PAGE = 15;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // FIX Error #3: Agregar eager loading para prevenir N+1 queries
            $query = PagoPrestamo::query()->with(['prestamo.cliente', 'historialPagos']);

            // Filtros
            if ($prestamo_id = $request->input('prestamo_id')) {
                $query->where('prestamo_id', $prestamo_id);
            }

            if ($estado = $request->input('estado')) {
                $query->where('estado', $estado);
            }

            if ($fecha_desde = $request->input('fecha_desde')) {
                $query->where('fecha_programada', '>=', $fecha_desde);
            }

            if ($fecha_hasta = $request->input('fecha_hasta')) {
                $query->where('fecha_programada', '<=', $fecha_hasta);
            }

            // Solo mostrar pagos de préstamos activos
            $query->whereHas('prestamo', function ($q) {
                $q->where('estado', 'activo');
            });

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'fecha_programada');
            $sortDirection = $request->get('sort_direction', 'asc');

            $query->orderBy($sortBy, $sortDirection);

            // Paginación con soporte para per_page
            $perPage = $request->get('per_page', self::ITEMS_PER_PAGE);
            $perPage = max(1, min(100, (int) $perPage)); // Limitar entre 1 y 100

            $pagos = $query->paginate($perPage)->appends($request->query());

            // FIX: Calcular dias_atraso dinámicamente para pagos pendientes/parciales
            // Esto asegura que el frontend muestre "X días" en lugar de "A tiempo" para pagos vencidos
            $hoy = now()->startOfDay();
            $pagos->getCollection()->transform(function ($pago) use ($hoy) {
                if (in_array($pago->estado, ['pendiente', 'parcial'])) {
                    $fechaProgramada = \Carbon\Carbon::parse($pago->fecha_programada)->startOfDay();
                    // Si la fecha programada ya pasó, calcular los días de atraso
                    if ($fechaProgramada->lt($hoy)) {
                        $pago->dias_atraso = (int) $fechaProgramada->diffInDays($hoy);
                        // También actualizar visualmente el estado a atrasado
                        $pago->estado = 'atrasado';
                    } else {
                        $pago->dias_atraso = 0;
                    }
                }
                return $pago;
            });

            // Estadísticas
            $totalVencido = PagoPrestamo::vencidos()
                ->sum(\DB::raw('monto_programado - monto_pagado'));

            $totalPendiente = PagoPrestamo::where('fecha_programada', '>=', now()->toDateString())
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->sum(\DB::raw('monto_programado - monto_pagado'));

            $estadisticas = [
                'total_vencido' => $totalVencido,
                'total_pendiente' => $totalPendiente,
                'pagos_vencidos' => PagoPrestamo::vencidos()->count(),
                'pagos_pendientes' => PagoPrestamo::where('fecha_programada', '>=', now()->toDateString())
                    ->whereIn('estado', ['pendiente', 'parcial'])
                    ->count(),
            ];

            // Obtener préstamos activos para el filtro
            $prestamos = Prestamo::where('estado', 'activo')
                ->with('cliente')
                ->orderBy('created_at', 'desc')
                ->get(['id', 'cliente_id', 'monto_prestado', 'pago_periodico'])
                ->map(function ($prestamo) {
                    return [
                        'id' => $prestamo->id,
                        'cliente_nombre' => $prestamo->cliente->nombre_razon_social ?? 'Cliente no encontrado',
                        'monto_prestado' => $prestamo->monto_prestado,
                        'pago_periodico' => $prestamo->pago_periodico,
                    ];
                });

            return Inertia::render('Pagos/Index', [
                'pagos' => $pagos,
                'estadisticas' => $estadisticas,
                'prestamos' => $prestamos,
                'filters' => $request->only(['prestamo_id', 'estado', 'fecha_desde', 'fecha_hasta']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
                'pagination' => [
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                    'per_page' => $pagos->perPage(),
                    'from' => $pagos->firstItem(),
                    'to' => $pagos->lastItem(),
                    'total' => $pagos->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en PagoPrestamoController@index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar la lista de pagos.');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $prestamoId = $request->get('prestamo_id');

            if (!$prestamoId) {
                return redirect()->route('pagos.index')->with('error', 'Debe seleccionar un préstamo.');
            }

            $prestamo = Prestamo::with('cliente')->findOrFail($prestamoId);

            // Crear pagos programados si no existen
            if (!$prestamo->pagos()->exists()) {
                $prestamo->crearPagosProgramados();
            }

            // Obtener pagos pendientes del préstamo con historial
            $pagosPendientes = $prestamo->pagos()
                ->with('historialPagos')
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderBy('numero_pago')
                ->get();

            if ($pagosPendientes->isEmpty()) {
                return redirect()->route('pagos.index')->with('warning', 'Este préstamo no tiene pagos pendientes.');
            }

            return Inertia::render('Pagos/Create', [
                'prestamo' => $prestamo,
                'pagos_pendientes' => $pagosPendientes,
                'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('pagos.index')->with('error', 'Préstamo no encontrado.');
        } catch (\Exception $e) {
            Log::error('Error en PagoPrestamoController@create: ' . $e->getMessage());
            return redirect()->route('pagos.index')->with('error', 'Error al cargar el formulario de pago.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Store a newly created resource in storage.
     * FIX Error #1: Implementar lockForUpdate para prevenir race conditions
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'prestamo_id' => 'required|exists:prestamos,id',
                'pago_id' => 'required|exists:pagos_prestamos,id',
                'monto_pagado' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'metodo_pago' => 'nullable|string|max:100',
                'referencia' => 'nullable|string|max:255',
                'notas' => 'nullable|string|max:1000',
                'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            ]);

            // FIX Error #1: Bloquear el pago para prevenir race conditions
            $pago = PagoPrestamo::where('id', $validated['pago_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Bloquear también el préstamo
            $prestamo = Prestamo::where('id', $validated['prestamo_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Verificar que el pago pertenece al préstamo
            if ($pago->prestamo_id != $validated['prestamo_id']) {
                throw ValidationException::withMessages([
                    'pago_id' => 'El pago seleccionado no pertenece al préstamo especificado.'
                ]);
            }

            // Agregar el pago (acumula si es parcial)
            // Nota: agregarPago() ya maneja su propia transacción, pero como estamos
            // dentro de una transacción padre, se anidará correctamente
            $pago->agregarPago(
                $validated['monto_pagado'],
                $validated['fecha_pago'],
                $validated['metodo_pago'],
                $validated['referencia'],
                $validated['cuenta_bancaria_id'] ?? null
            );

            // Actualizar información adicional
            $pago->update([
                'metodo_pago' => $validated['metodo_pago'],
                'referencia' => $validated['referencia'],
                'notas' => $validated['notas'],
                'confirmado' => true,
            ]);

            DB::commit();

            Log::info('Pago registrado', [
                'pago_id' => $pago->id,
                'prestamo_id' => $prestamo->id,
                'monto' => $validated['monto_pagado']
            ]);

            return redirect()->route('pagos.index')->with('success', 'Pago registrado correctamente');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['monto_pagado' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar pago: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PagoPrestamo $pago)
    {
        try {
            $pago->load(['prestamo.cliente', 'historialPagos']);

            return Inertia::render('Pagos/Show', [
                'pago' => $pago,
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('pagos.index')->with('error', 'Pago no encontrado.');
        } catch (\Exception $e) {
            Log::error('Error al mostrar pago: ' . $e->getMessage());
            return redirect()->route('pagos.index')->with('error', 'Error al cargar el pago.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PagoPrestamo $pago)
    {
        try {
            $pago->load(['prestamo.cliente', 'historialPagos']);

            return Inertia::render('Pagos/Edit', [
                'pago' => $pago,
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('pagos.index')->with('error', 'Pago no encontrado.');
        } catch (\Exception $e) {
            Log::error('Error al cargar formulario de edición: ' . $e->getMessage());
            return redirect()->route('pagos.index')->with('error', 'Error al cargar el formulario de edición.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     * FIX Error #1: Implementar lockForUpdate para prevenir race conditions
     */
    public function update(Request $request, PagoPrestamo $pago)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'monto_pagado' => 'required|numeric|min:0',
                'fecha_pago' => 'required|date',
                'metodo_pago' => 'nullable|string|max:100',
                'referencia' => 'nullable|string|max:255',
                'notas' => 'nullable|string|max:1000',
            ]);

            // FIX Error #1: Bloquear el pago para prevenir race conditions
            $pago = PagoPrestamo::where('id', $pago->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Agregar el pago (acumula si es parcial)
            $pago->agregarPago(
                $validated['monto_pagado'],
                $validated['fecha_pago'],
                $validated['metodo_pago'],
                $validated['referencia']
            );

            // Actualizar información adicional
            $pago->update([
                'metodo_pago' => $validated['metodo_pago'],
                'referencia' => $validated['referencia'],
                'notas' => $validated['notas'],
            ]);

            DB::commit();

            Log::info('Pago actualizado', [
                'pago_id' => $pago->id,
                'monto' => $validated['monto_pagado']
            ]);

            return redirect()->route('pagos.index')->with('success', 'Pago actualizado correctamente');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['monto_pagado' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pago: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el pago: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified resource from storage.
     * FIX Error #10: Validar estado en eliminación con lock
     */
    public function destroy(PagoPrestamo $pago)
    {
        DB::beginTransaction();

        try {
            // FIX Error #10: Bloquear el pago antes de validar y eliminar
            $pago = PagoPrestamo::where('id', $pago->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pago->estado === 'pagado') {
                DB::rollBack();
                return redirect()->back()->with(
                    'error',
                    'No se puede eliminar un pago ya registrado. Solo se pueden editar.'
                );
            }

            Log::info('Eliminando pago', [
                'pago_id' => $pago->id,
                'prestamo_id' => $pago->prestamo_id
            ]);

            $pago->delete();

            // FIX: Actualizar el estado del préstamo padre para mantener integridad
            // Esto recalcula monto_pagado y pagos_realizados
            $pago->prestamo->actualizarEstado();

            DB::commit();

            return redirect()->route('pagos.index')->with('success', 'Pago eliminado correctamente');
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->route('pagos.index')->with('error', 'Pago no encontrado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar pago: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error interno al eliminar el pago.');
        }
    }

    /**
     * Obtener pagos de un préstamo específico
     */
    public function pagosPorPrestamo(Prestamo $prestamo)
    {
        try {
            // Crear pagos programados si no existen
            if (!$prestamo->pagos()->exists()) {
                $prestamo->crearPagosProgramados();
            }

            $pagos = $prestamo->pagos()
                ->with('historialPagos')
                ->orderBy('numero_pago')
                ->get();

            return response()->json([
                'success' => true,
                'pagos' => $pagos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo pagos del préstamo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pagos del préstamo'
            ], 500);
        }
    }
}
