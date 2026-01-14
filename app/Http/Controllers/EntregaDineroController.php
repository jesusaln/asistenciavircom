<?php

namespace App\Http\Controllers;

use App\Models\EntregaDinero;
use App\Models\Cobranza;
use App\Models\Venta;
use Illuminate\Http\Request;
use App\Services\EntregaDineroService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EntregaDineroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Administradores y usuarios de ventas pueden acceder a esta funcionalidad
        if (!auth()->user()->hasAnyRole(['admin', 'ventas', 'super-admin'])) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $userId = auth()->id();
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);

        // Entregas manuales - admins ven todas, usuarios normales solo las suyas
        $query = EntregaDinero::with(['usuario', 'recibidoPor']);

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        // Búsqueda
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('usuario', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('notas', 'like', "%{$search}%")
                    ->orWhere('notas_recibido', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        // Filtros
        if (request('estado')) {
            $query->where('estado', request('estado'));
        }

        if (request('user_id')) {
            $query->where('user_id', request('user_id'));
        }

        // Ordenamiento
        $sortBy = request('sort_by', 'fecha_entrega');
        $sortDirection = request('sort_direction', 'desc');
        $allowedSorts = ['fecha_entrega', 'total', 'created_at', 'usuario'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'fecha_entrega';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'usuario') {
            $query->join('users', 'entregas_dinero.user_id', '=', 'users.id')
                ->orderBy('users.name', $sortDirection)
                ->select('entregas_dinero.*');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Si no se especificó ordenamiento personalizado, agregar ordenamiento secundario
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        $entregas = $query->paginate(request('per_page', 15));

        // Load related venta data for entregas that have tipo_origen = 'venta'
        $entregas->getCollection()->transform(function ($entrega) {
            if ($entrega->tipo_origen === 'venta' && $entrega->id_origen) {
                $venta = Venta::find($entrega->id_origen);
                $entrega->venta_numero = $venta ? $venta->numero_venta : null;
                $entrega->venta_cliente = $venta && $venta->cliente ? $venta->cliente->nombre_razon_social : null;
            }
            return $entrega;
        });

        // Obtener cobranzas pagadas con saldos pendientes
        $cobranzasQuery = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->where('estado', 'pagado')
            ->whereRaw("monto_pagado > COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'cobranza' AND id_origen = cobranzas.id AND estado = 'recibido'), 0)");

        // Si no es admin, filtrar solo por el usuario actual
        if (!$isAdmin) {
            $cobranzasQuery->where('responsable_cobro', $userId);
        }

        $cobranzasPagadas = $cobranzasQuery->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($cobranza) {
                $montoYaEntregado = EntregaDinero::where('tipo_origen', 'cobranza')
                    ->where('id_origen', $cobranza->id)
                    ->where('estado', 'recibido')
                    ->sum('total');
                $saldoPendiente = $cobranza->monto_pagado - $montoYaEntregado;

                return [
                    'id' => 'cobranza_' . $cobranza->id,
                    'tipo' => 'cobranza',
                    'tipo_origen' => 'cobranza',
                    'id_origen' => $cobranza->id,
                    'fecha_entrega' => $cobranza->fecha_pago?->format('Y-m-d'),
                    'total' => $cobranza->monto_pagado,
                    'saldo_pendiente' => $saldoPendiente,
                    'ya_entregado' => $montoYaEntregado,
                    'concepto' => $cobranza->concepto,
                    'cliente' => $cobranza->renta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'estado' => 'por_entregar',
                    'usuario' => $cobranza->responsableCobro,
                    'registro_original' => $cobranza,
                    'metodo_pago' => $cobranza->metodo_pago ?? 'efectivo',
                ];
            });

        // Obtener ventas pagadas con saldos pendientes
        // CRÍTICO: Excluir ventas que YA tienen entregas (pendientes o recibidas) para evitar duplicados
        // ✅ FIX DOUBLE ACCOUNTING: También excluir montos que ya están conciliados en movimientos bancarios (via CxC)
        $ventasQuery = Venta::with(['cliente', 'pagadoPor', 'cuentaPorCobrar.movimientosBancarios'])
            ->where('pagado', true)
            ->whereRaw("total > (
                COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'venta' AND id_origen = ventas.id AND estado IN ('pendiente', 'recibido')), 0) +
                COALESCE((SELECT SUM(monto) FROM movimientos_bancarios WHERE conciliable_type = 'App\\\Models\\\CuentasPorCobrar' AND conciliable_id = (SELECT id FROM cuentas_por_cobrar WHERE venta_id = ventas.id LIMIT 1)), 0)
            )");

        // Si no es admin, filtrar solo por el usuario actual
        if (!$isAdmin) {
            $ventasQuery->where('pagado_por', $userId);
        }

        $ventasPagadas = $ventasQuery->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(function ($venta) {
                $montoYaEntregado = EntregaDinero::where('tipo_origen', 'venta')
                    ->where('id_origen', $venta->id)
                    ->whereIn('estado', ['pendiente', 'recibido']) // ✅ Corrected: Count pending as 'already engaged'
                    ->sum('total');

                $montoConciliado = 0;
                if ($venta->cuentaPorCobrar) {
                    // Sum absolute values of linked movements
                    $montoConciliado = $venta->cuentaPorCobrar->movimientosBancarios->sum(fn($m) => abs($m->monto));
                }

                $saldoPendiente = max(0, $venta->total - $montoYaEntregado - $montoConciliado);

                return [
                    'id' => 'venta_' . $venta->id,
                    'tipo' => 'venta',
                    'tipo_origen' => 'venta',
                    'id_origen' => $venta->id,
                    'fecha_entrega' => $venta->fecha_pago?->format('Y-m-d'),
                    'total' => $venta->total,
                    'saldo_pendiente' => $saldoPendiente,
                    'ya_entregado' => $montoYaEntregado,
                    'ya_conciliado' => $montoConciliado,
                    'concepto' => 'Venta #' . $venta->numero_venta . ($montoConciliado > 0 ? ' (Conciliado parcial)' : ''),
                    'cliente' => $venta->cliente->nombre_razon_social ?? 'Sin cliente',
                    'estado' => 'por_entregar',
                    'usuario' => $venta->pagadoPor,
                    'registro_original' => $venta,
                    'metodo_pago' => $venta->metodo_pago,
                ];
            });

        // Combinar todos los registros
        $registrosAutomaticos = collect([...$cobranzasPagadas, ...$ventasPagadas]);

        // Estadísticas - FILTRADAS por usuario si no es admin
        $statsQuery = EntregaDinero::query();
        if (!$isAdmin) {
            $statsQuery->where('user_id', $userId);
        }

        $stats = [
            'total_pendientes' => (float) (clone $statsQuery)->where('estado', 'pendiente')->sum('total'),
            'total_recibidas' => (float) (clone $statsQuery)->where('estado', 'recibido')->sum('total'),
            'entregas_pendientes' => (clone $statsQuery)->where('estado', 'pendiente')->count(),
            'registros_automaticos' => $registrosAutomaticos->count(),
            'total_automatico' => (float) $registrosAutomaticos->sum('total'),
            // Nuevos totales para el header
            'total' => (float) (clone $statsQuery)->sum('total'),
            'total_efectivo' => (float) (clone $statsQuery)->sum('monto_efectivo'),
            'total_otros' => (float) ((clone $statsQuery)->sum('monto_transferencia')
                + (clone $statsQuery)->sum('monto_cheques')
                + (clone $statsQuery)->sum('monto_tarjetas')),
        ];

        return Inertia::render('EntregasDinero/Index', [
            'entregas' => $entregas,
            'registrosAutomaticos' => $registrosAutomaticos,
            'stats' => $stats,
            'filters' => request()->only(['estado', 'user_id', 'search', 'sort_by', 'sort_direction']),
            'usuarios' => \App\Models\User::select('id', 'name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('EntregasDinero/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha_entrega' => 'required|date',
            'monto_efectivo' => 'required|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'monto_cheques' => 'required|numeric|min:0',
            'monto_tarjetas' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        $total = $request->monto_efectivo + ($request->monto_transferencia ?? 0) + $request->monto_cheques + $request->monto_tarjetas;

        if ($total <= 0) {
            return back()->withErrors(['total' => 'El total debe ser mayor a cero']);
        }

        EntregaDinero::create([
            'user_id' => auth()->id(),
            'fecha_entrega' => $request->fecha_entrega,
            'monto_efectivo' => $request->monto_efectivo,
            'monto_transferencia' => $request->monto_transferencia ?? 0,
            'monto_cheques' => $request->monto_cheques,
            'monto_tarjetas' => $request->monto_tarjetas,
            'total' => $total,
            'notas' => $request->notas,
        ]);

        return redirect()->route('entregas-dinero.index')->with('success', 'Entrega de dinero registrada correctamente');
    }

    /**
     * Registrar entrega rÃ¡pida desde Corte Diario (marca como recibida).
     */
    public function storeDesdeCorte(Request $request)
    {
        $this->middleware(['auth', 'verified']);

        $data = $request->validate([
            'fecha' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'notas' => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();

        EntregaDinero::create([
            'user_id' => $userId,
            'fecha_entrega' => $data['fecha'],
            'monto_efectivo' => $data['monto'],
            'monto_cheques' => 0,
            'monto_tarjetas' => 0,
            'total' => $data['monto'],
            'notas' => $data['notas'] ?? null,
            'estado' => 'recibido',
            'recibido_por' => $userId,
            'fecha_recibido' => now(),
        ]);

        return back()->with('success', 'Entrega registrada en el corte');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntregaDinero $entregaDinero)
    {
        $entregaDinero->load(['usuario', 'recibidoPor']);
        return Inertia::render('EntregasDinero/Show', ['entrega' => $entregaDinero]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntregaDinero $entregaDinero)
    {
        // ✅ FIX: Validar propiedad antes de mostrar formulario
        if ($entregaDinero->user_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403);
        }

        // ✅ FIX: No permitir editar entregas recibidas (consistente con update)
        if ($entregaDinero->estado === 'recibido' && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return redirect()->route('entregas-dinero.index')
                ->with('error', 'No se pueden editar entregas ya recibidas. Los datos están conciliados.');
        }

        $entregaDinero->load(['usuario', 'recibidoPor']);
        return Inertia::render('EntregasDinero/Edit', ['entrega' => $entregaDinero]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntregaDinero $entregaDinero)
    {
        // Si es para marcar como recibido
        if ($request->has('marcar_recibido')) {
            $request->validate([
                'notas_recibido' => 'nullable|string|max:500',
            ]);

            $entregaDinero->update([
                'estado' => 'recibido',
                'recibido_por' => auth()->id(),
                'fecha_recibido' => now(),
                'notas_recibido' => $request->notas_recibido,
            ]);

            return redirect()->route('entregas-dinero.index')->with('success', 'Entrega marcada como recibida');
        }

        // Si es para actualizar la entrega (solo el usuario que la creó)
        if ($entregaDinero->user_id !== auth()->id()) {
            abort(403);
        }

        // CRÍTICO: No permitir editar entregas ya recibidas (datos conciliados)
        // Solo admin puede editar entregas recibidas si es absolutamente necesario
        if ($entregaDinero->estado === 'recibido' && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return back()->withErrors(['error' => 'No se pueden editar entregas ya recibidas. Los datos están conciliados.']);
        }

        $request->validate([
            'fecha_entrega' => 'required|date',
            'monto_efectivo' => 'required|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'monto_cheques' => 'required|numeric|min:0',
            'monto_tarjetas' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        $total = $request->monto_efectivo + ($request->monto_transferencia ?? 0) + $request->monto_cheques + $request->monto_tarjetas;

        if ($total <= 0) {
            return back()->withErrors(['total' => 'El total debe ser mayor a cero']);
        }

        DB::transaction(function () use ($entregaDinero, $request, $total) {
            $totalAnterior = $entregaDinero->total;
            $diferencia = $total - $totalAnterior;

            $entregaDinero->update([
                'fecha_entrega' => $request->fecha_entrega,
                'monto_efectivo' => $request->monto_efectivo,
                'monto_transferencia' => $request->monto_transferencia ?? 0,
                'monto_cheques' => $request->monto_cheques,
                'monto_tarjetas' => $request->monto_tarjetas,
                'total' => $total,
                'notas' => $request->notas,
            ]);

            // ✅ FIX: Si ya estaba recibida y tiene movimiento bancario, actualizar el movimiento
            if ($entregaDinero->estado === 'recibido' && $diferencia != 0) {
                $movimiento = \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\EntregaDinero::class)
                    ->where('conciliable_id', $entregaDinero->id)
                    ->first();

                if ($movimiento) {
                    $movimiento->monto = $total; // Deposito es positivo
                    $movimiento->save();

                    // Actualizar saldo de cuenta bancaria
                    if ($movimiento->cuentaBancaria) {
                        $movimiento->cuentaBancaria->saldo_actual += $diferencia;
                        $movimiento->cuentaBancaria->save();
                    }
                } elseif ($entregaDinero->cuenta_bancaria_id) {
                    // Caso Legacy: No tiene movimiento pero tiene cuenta vinculada
                    $cuenta = \App\Models\CuentaBancaria::find($entregaDinero->cuenta_bancaria_id);
                    if ($cuenta) {
                        $cuenta->saldo_actual += $diferencia;
                        $cuenta->save();
                    }
                }
            }
        });

        return redirect()->route('entregas-dinero.index')->with('success', 'Entrega actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntregaDinero $entregaDinero)
    {
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);

        // Solo el usuario que creó la entrega o admin puede eliminar
        if ($entregaDinero->user_id !== auth()->id() && !$isAdmin) {
            abort(403);
        }

        // Solo se pueden eliminar entregas pendientes (excepto para admins)
        if ($entregaDinero->estado !== 'pendiente' && !$isAdmin) {
            return back()->withErrors(['error' => 'Solo se pueden eliminar entregas pendientes']);
        }

        $entregaDinero->delete();

        return redirect()->route('entregas-dinero.index')->with('success', 'Entrega eliminada correctamente');
    }

    /**
     * Marcar una entrega manual como recibida (desde el boton de la tabla).
     */
    public function marcarRecibido(Request $request, $id)
    {
        $entrega = EntregaDinero::findOrFail($id);

        if ($entrega->user_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403);
        }

        $request->validate([
            'notas_recibido' => 'nullable|string|max:500',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id', // ✅ Obligatorio
        ]);

        $cuentaBancariaId = $request->cuenta_bancaria_id;

        DB::transaction(function () use ($entrega, $request, $cuentaBancariaId) {
            $entrega->update([
                'estado' => 'recibido',
                'recibido_por' => auth()->id(),
                'fecha_recibido' => now(),
                'notas_recibido' => $request->notas_recibido,
                'cuenta_bancaria_id' => $cuentaBancariaId,
            ]);

            // ✅ FIX: Crear Movimiento Bancario para trazabilidad
            if ($cuentaBancariaId) {
                $cuenta = \App\Models\CuentaBancaria::find($cuentaBancariaId);
                if ($cuenta) {
                    // El método registrarMovimiento maneja transacción y bloqueo internamente,
                    // pero ya estamos dentro de una transacción, lo cual es seguro.
                    $movimiento = $cuenta->registrarMovimiento(
                        'deposito',
                        $entrega->total,
                        "Entrega de dinero #{$entrega->id} - " . ($request->notas_recibido ?? 'Sin notas'),
                        'cobro'
                    );

                    // Vincular el movimiento con esta entrega para trazabilidad bidireccional
                    $movimiento->update([
                        'conciliable_type' => \App\Models\EntregaDinero::class,
                        'conciliable_id' => $entrega->id,
                        'conciliado_por' => auth()->id(),
                        'conciliado_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('entregas-dinero.index')->with('success', 'Entrega marcada como recibida y registrada en banco');
    }

    /**
     * Revertir una entrega de recibido a pendiente (solo admin).
     * Útil para corregir errores cuando se marcó como recibido por equivocación.
     */
    public function revertirAPendiente(Request $request, $id)
    {
        // Solo admin puede revertir
        if (!auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Solo administradores pueden revertir entregas');
        }

        $entrega = EntregaDinero::findOrFail($id);

        if ($entrega->estado !== 'recibido') {
            return back()->withErrors(['error' => 'Solo se pueden revertir entregas que estén en estado recibido']);
        }

        DB::transaction(function () use ($entrega) {
            // ✅ FIX: Revertir movimiento bancario si existe
            $movimiento = \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\EntregaDinero::class)
                ->where('conciliable_id', $entrega->id)
                ->first();

            if ($movimiento) {
                // Si existe movimiento vinculado, revertir saldo y eliminar movimiento
                if ($movimiento->cuentaBancaria) {
                    $movimiento->cuentaBancaria->revertirSaldoPorMovimiento($movimiento);
                }
                $movimiento->delete();
            } elseif ($entrega->cuenta_bancaria_id) {
                // FALLBACK LEGACY: Si no hay movimiento pero hay cuenta vinculada (registros antiguos),
                // revertir saldo manualmente.
                $cuenta = \App\Models\CuentaBancaria::find($entrega->cuenta_bancaria_id);
                if ($cuenta) {
                    $cuenta->saldo_actual -= $entrega->total;
                    $cuenta->save();
                }
            }

            $entrega->update([
                'estado' => 'pendiente',
                'recibido_por' => null,
                'fecha_recibido' => null,
                'cuenta_bancaria_id' => null, // Desvincular banco al revertir
                'notas_recibido' => $entrega->notas_recibido . ' [REVERTIDO por ' . auth()->user()->name . ' el ' . now()->format('d/m/Y H:i') . ']',
            ]);
        });

        return redirect()->route('entregas-dinero.index')->with('success', 'Entrega revertida a pendiente correctamente');
    }

    /**
     * API endpoint para obtener entregas pendientes por usuario (para dashboard)
     */
    public function pendientesPorUsuario()
    {
        $entregas = EntregaDinero::with('usuario')
            ->where('estado', 'pendiente')
            ->orderBy('total', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(function ($entregasUsuario, $userId) {
                $usuario = $entregasUsuario->first()->usuario;
                return [
                    'usuario' => $usuario->name,
                    'user_id' => $userId,
                    'total_pendiente' => $entregasUsuario->sum('total'),
                    'cantidad_entregas' => $entregasUsuario->count(),
                    'entregas' => $entregasUsuario->map(function ($entrega) {
                        return [
                            'id' => $entrega->id,
                            'fecha_entrega' => $entrega->fecha_entrega->format('Y-m-d'),
                            'total' => $entrega->total,
                            'notas' => $entrega->notas,
                        ];
                    }),
                ];
            })
            ->values();

        return response()->json($entregas);
    }

    /**
     * Reporte detallado de pagos recibidos con informaciÃ³n de quiÃ©n recibiÃ³ y mÃ©todo de pago
     */
    public function reportePagosRecibidos(Request $request)
    {
        $query = EntregaDinero::with(['usuario', 'recibidoPor'])
            ->where('estado', 'recibido');

        // Filtros
        if ($request->filled('fecha_inicio')) {
            $query->where('fecha_entrega', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('fecha_entrega', '<=', $request->fecha_fin);
        }

        if ($request->filled('usuario_id')) {
            $query->where('user_id', $request->usuario_id);
        }

        if ($request->filled('recibido_por')) {
            $query->where('recibido_por', $request->recibido_por);
        }

        $entregas = $query->orderBy('fecha_recibido', 'desc')->get();

        // Agrupar por mÃ©todo de pago y calcular totales
        $reportePorMetodo = $entregas->groupBy(function ($entrega) {
            if ($entrega->monto_efectivo > 0 && $entrega->monto_cheques == 0 && $entrega->monto_tarjetas == 0 && $entrega->monto_transferencia == 0) {
                return 'efectivo';
            } elseif ($entrega->monto_cheques > 0 && $entrega->monto_efectivo == 0 && $entrega->monto_tarjetas == 0 && $entrega->monto_transferencia == 0) {
                return 'cheque';
            } elseif ($entrega->monto_tarjetas > 0 && $entrega->monto_efectivo == 0 && $entrega->monto_cheques == 0 && $entrega->monto_transferencia == 0) {
                return 'tarjeta';
            } elseif ($entrega->monto_transferencia > 0 && $entrega->monto_efectivo == 0 && $entrega->monto_cheques == 0 && $entrega->monto_tarjetas == 0) {
                return 'transferencia';
            } else {
                return 'mixto';
            }
        });

        $resumenMetodos = [];
        foreach ($reportePorMetodo as $metodo => $entregasMetodo) {
            $resumenMetodos[] = [
                'metodo' => $metodo,
                'label' => $this->getLabelMetodoPago($metodo),
                'total' => $entregasMetodo->sum('total'),
                'cantidad' => $entregasMetodo->count(),
                'entregas' => $entregasMetodo->map(function ($entrega) {
                    return [
                        'id' => $entrega->id,
                        'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d') ?? 'N/A',
                        'fecha_recibido' => $entrega->fecha_recibido?->format('Y-m-d H:i:s') ?? 'N/A',
                        'usuario' => $entrega->usuario?->name ?? 'Usuario desconocido',
                        'recibido_por' => $entrega->recibidoPor?->name ?? 'No especificado',
                        'monto_efectivo' => $entrega->monto_efectivo,
                        'monto_transferencia' => $entrega->monto_transferencia,
                        'monto_cheques' => $entrega->monto_cheques,
                        'monto_tarjetas' => $entrega->monto_tarjetas,
                        'total' => $entrega->total,
                        'notas' => $entrega->notas,
                        'notas_recibido' => $entrega->notas_recibido,
                        'tipo_origen' => $entrega->tipo_origen,
                        'id_origen' => $entrega->id_origen,
                    ];
                })
            ];
        }

        // EstadÃ­sticas generales
        $stats = [
            'total_recibido' => $entregas->sum('total'),
            'total_efectivo' => $entregas->sum('monto_efectivo'),
            'total_transferencia' => $entregas->sum('monto_transferencia'),
            'total_cheques' => $entregas->sum('monto_cheques'),
            'total_tarjetas' => $entregas->sum('monto_tarjetas'),
            'cantidad_entregas' => $entregas->count(),
            'usuarios_unicos' => $entregas->pluck('user_id')->unique()->count(),
            'responsables_unicos' => $entregas->pluck('recibido_por')->unique()->count(),
        ];

        // EstadÃ­sticas por mÃ©todo de pago en entrega
        $metodoEntregaStats = [
            'efectivo' => $entregas->where('monto_efectivo', '>', 0)->sum('monto_efectivo'),
            'transferencia' => $entregas->where('monto_transferencia', '>', 0)->sum('monto_transferencia'),
            'cheque' => $entregas->where('monto_cheques', '>', 0)->sum('monto_cheques'),
            'tarjeta' => $entregas->where('monto_tarjetas', '>', 0)->sum('monto_tarjetas'),
            'mixto' => $entregas->where('monto_efectivo', '>', 0)
                ->where(function ($q) {
                    $q->where('monto_cheques', '>', 0)
                        ->orWhere('monto_tarjetas', '>', 0)
                        ->orWhere('monto_transferencia', '>', 0);
                })->sum('total'),
        ];

        // Obtener usuarios y responsables para los filtros
        $usuarios = \App\Models\User::select('id', 'name')
            ->whereIn('id', $entregas->pluck('user_id')->unique())
            ->get();

        $responsables = \App\Models\User::select('id', 'name')
            ->whereIn('id', $entregas->pluck('recibido_por')->unique())
            ->get();

        return Inertia::render('EntregasDinero/ReportePagos', [
            'entregas' => $entregas,
            'resumenMetodos' => $resumenMetodos,
            'stats' => $stats,
            'metodoEntregaStats' => $metodoEntregaStats,
            'usuarios' => $usuarios,
            'responsables' => $responsables,
            'filters' => $request->only(['fecha_inicio', 'fecha_fin', 'usuario_id', 'recibido_por']),
        ]);
    }

    /**
     * Obtener label para mÃ©todo de pago
     */
    private function getLabelMetodoPago($metodo)
    {
        $labels = [
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'tarjeta' => 'Tarjeta',
            'mixto' => 'Mixto'
        ];

        return $labels[$metodo] ?? 'Desconocido';
    }

    /**
     * Marcar un registro automÃ¡tico (cobranza o venta) como recibido (puede ser parcial)
     */
    public function marcarAutomaticoRecibido(Request $request, $tipo_origen, $id_origen)
    {
        $request->validate([
            'monto_recibido' => 'required|numeric|min:0.01',
            'metodo_pago_entrega' => 'required|in:efectivo,transferencia,cheque,tarjeta,otros',
            'notas_recibido' => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);

        if ($tipo_origen === 'cobranza') {
            $q = Cobranza::query()
                ->where('id', $id_origen)
                ->where('estado', 'pagado');

            if (!$isAdmin) {
                $q->where('responsable_cobro', $userId);
            }

            $registro = $q->firstOrFail();
            $montoTotal = $registro->monto_pagado;
            $concepto = $registro->concepto;
            $fecha = $registro->fecha_pago;
            $usuarioEntrega = $registro->responsable_cobro; // Usuario que cobrÃ³
        } elseif ($tipo_origen === 'venta') {
            $q = Venta::query()
                ->where('id', $id_origen)
                ->where('pagado', true);

            if (!$isAdmin) {
                $q->where('pagado_por', $userId);
            }

            $registro = $q->firstOrFail();
            $montoTotal = $registro->total;
            $concepto = 'Venta #' . $registro->numero_venta;
            $fecha = $registro->fecha_pago;
            $usuarioEntrega = $registro->pagado_por; // Usuario que cobrÃ³
        } else {
            return response()->json(['error' => 'Tipo de registro no vÃ¡lido'], 422);
        }

        if ($request->monto_recibido > $montoTotal) {
            return response()->json(['error' => 'El monto recibido no puede ser mayor al total'], 422);
        }

        DB::transaction(function () use ($request, $tipo_origen, $id_origen, $montoTotal, $concepto, $fecha, $usuarioEntrega, $userId) {
            // CRÍTICO: Incluir entregas pendientes Y recibidas en el cálculo
            // Anteriormente solo se contaban las recibidas, permitiendo duplicados
            $entregasExistentes = EntregaDinero::where('tipo_origen', $tipo_origen)
                ->where('id_origen', $id_origen)
                ->whereIn('estado', ['pendiente', 'recibido'])
                ->lockForUpdate() // Bloqueo pesimista para evitar condiciones de carrera
                ->get();

            $montoYaEntregado = $entregasExistentes->where('estado', 'recibido')->sum('total');
            $montoPendiente = $montoTotal - $montoYaEntregado;

            // Verificar si ya existe una entrega pendiente para este origen
            $hayEntregaPendiente = $entregasExistentes->where('estado', 'pendiente')->isNotEmpty();

            if ($hayEntregaPendiente) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'monto_recibido' => 'Ya existe una entrega pendiente para este registro. Debe ser procesada primero.'
                ]);
            }

            if ($request->monto_recibido > $montoPendiente + 0.01) { // Pequeña tolerancia por flotantes
                throw \Illuminate\Validation\ValidationException::withMessages(['monto_recibido' => 'El monto recibido excede el saldo pendiente']);
            }

            EntregaDineroService::crearDesdeOrigen(
                $tipo_origen,
                $id_origen,
                (float) $request->monto_recibido,
                $request->metodo_pago_entrega,
                $fecha?->format('Y-m-d') ?? now()->toDateString(),
                (int) $usuarioEntrega,
                'recibido',
                (int) $userId,
                'Entrega automática - ' . $concepto . ' - Método entrega: ' . $request->metodo_pago_entrega
            );
        });

        return redirect()->route('entregas-dinero.index')->with('success', 'Monto registrado correctamente');
    }

    /**
     * Marcar entrega como entregada al responsable de la organizaciÃ³n
     */
    public function marcarEntregadoResponsable(Request $request, $id)
    {
        $request->validate([
            'responsable_nombre' => 'required|string|max:255',
            'notas_entrega' => 'nullable|string|max:500',
        ]);

        $entrega = EntregaDinero::findOrFail($id);

        // Solo se pueden marcar como entregadas al responsable las entregas que ya estÃ¡n recibidas
        if ($entrega->estado !== 'recibido') {
            return response()->json([
                'success' => false,
                'error' => 'Solo se pueden entregar al responsable las entregas que ya han sido recibidas'
            ], 400);
        }

        $entrega->marcarEntregadoResponsable(
            $request->responsable_nombre,
            $request->notas_entrega
        );

        return response()->json([
            'success' => true,
            'message' => 'Entrega marcada como entregada al responsable correctamente'
        ]);
    }
}
