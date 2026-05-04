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
        // Administradores, ventas y tesoreros de recepción de efectivo
        if (! auth()->user()->hasAnyRole(['admin', 'ventas', 'super-admin'])
            && ! auth()->user()->can('confirmar entrega efectivo')) {
            abort(403, 'No tienes permisos para acceder a esta sección');
        }

        $userId = auth()->id();
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);
        $esTesoreroRecepcion = auth()->user()->can('confirmar entrega efectivo');

        // Entregas manuales: admin ve todas; tesorero ve pendientes de todos; el resto solo las propias
        $query = EntregaDinero::with(['usuario', 'recibidoPor', 'children.origen'])
                    ->whereNull('parent_id');

        if ($isAdmin) {
            // sin filtro user_id
        } elseif ($esTesoreroRecepcion) {
            $query->where('estado', 'pendiente');
        } else {
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
            } elseif ($entrega->tipo_origen === 'lote') {
                $entrega->es_lote = true;
                $entrega->conteo_items = $entrega->children->count();
            }
            return $entrega;
        });

        // Obtener cobranzas pagadas con saldos pendientes
        $cobranzasQuery = Cobranza::with(['renta.cliente', 'responsableCobro'])
            ->where('estado', 'pagado')
            ->whereRaw("monto_pagado > COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'cobranza' AND id_origen = cobranzas.id AND estado = 'recibido' AND deleted_at IS NULL), 0)");

        if (! $isAdmin && ! $esTesoreroRecepcion) {
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
                COALESCE((SELECT SUM(total) FROM entregas_dinero WHERE tipo_origen = 'venta' AND id_origen = ventas.id AND estado IN ('pendiente', 'recibido') AND deleted_at IS NULL), 0) +
                COALESCE((SELECT SUM(monto) FROM movimientos_bancarios WHERE conciliable_type = 'App\\\Models\\\CuentasPorCobrar' AND conciliable_id = (SELECT id FROM cuentas_por_cobrar WHERE venta_id = ventas.id LIMIT 1) AND deleted_at IS NULL), 0)
            )");

        if (! $isAdmin && ! $esTesoreroRecepcion) {
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

        // Estadísticas (mismo criterio de alcance que la tabla principal)
        $statsQuery = EntregaDinero::query();
        if ($isAdmin) {
            // todas
        } elseif ($esTesoreroRecepcion) {
            $statsQuery->where('estado', 'pendiente');
        } else {
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
        $data = $request->validate([
            'fecha_entrega' => 'required|date',
            'monto_efectivo' => 'required|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'monto_cheques' => 'required|numeric|min:0',
            'monto_tarjetas' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            EntregaDineroService::crearManual($data, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['total' => $e->getMessage()]);
        }

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

        EntregaDineroService::crearDesdeCorte($data, $userId);

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

        $data = $request->validate([
            'fecha_entrega' => 'required|date',
            'monto_efectivo' => 'required|numeric|min:0',
            'monto_transferencia' => 'nullable|numeric|min:0',
            'monto_cheques' => 'required|numeric|min:0',
            'monto_tarjetas' => 'required|numeric|min:0',
            'notas' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($entregaDinero, $data) {
            try {
                EntregaDineroService::actualizarManual($entregaDinero, $data);
            } catch (\InvalidArgumentException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages(['total' => $e->getMessage()]);
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

        // CRÍTICO: No permitir borrar entregas recibidas/conciliadas.
        // Se debe usar revertirAPendiente primero para deshacer movimientos bancarios de forma segura.
        if ($entregaDinero->estado !== 'pendiente') {
            return back()->withErrors(['error' => 'No se pueden eliminar entregas recibidas. Si es necesario, reviértala a pendiente primero.']);
        }

        \Log::info('Eliminando Entrega de Dinero', [
            'id' => $entregaDinero->id,
            'total' => $entregaDinero->total,
            'usuario_id' => auth()->id(),
            'estado' => $entregaDinero->estado,
        ]);

        // CRÍTICO FIX #32: Eliminar movimientos bancarios asociados (aunque esté pendiente, pudo haber sido vinculado)
        $movs = \App\Models\MovimientoBancario::where('conciliable_type', \App\Models\EntregaDinero::class)
            ->where('conciliable_id', $entregaDinero->id)
            ->get();

        foreach ($movs as $mov) {
            // Si el movimiento está conciliado con esta entrega, lo desvinculamos o eliminamos si fue creado por ella.
            // Asumimos soft delete en movimientos.
            $mov->delete();
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

        $esPropietario = (int) $entrega->user_id === (int) auth()->id();
        $esAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);
        $esTesorero = auth()->user()->can('confirmar entrega efectivo');

        if (! $esPropietario && ! $esAdmin && ! $esTesorero) {
            abort(403);
        }

        $soloRecepcionFisica = $request->boolean('solo_recepcion_fisica');
        if ($esTesorero || $esAdmin) {
            $request->validate([
                'notas_recibido' => 'nullable|string|max:500',
                'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'solo_recepcion_fisica' => 'nullable|boolean',
            ]);
        } else {
            $request->validate([
                'notas_recibido' => 'nullable|string|max:500',
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
            $soloRecepcionFisica = false;
        }

        $cuentaBancariaId = $request->filled('cuenta_bancaria_id') ? (int) $request->cuenta_bancaria_id : null;
        if (! $soloRecepcionFisica && ! $cuentaBancariaId && ($esPropietario && ! $esAdmin && ! $esTesorero)) {
            return back()->withErrors(['cuenta_bancaria_id' => 'Selecciona la cuenta bancaria de depósito.']);
        }

        $registrarBanco = ! $soloRecepcionFisica && $cuentaBancariaId;

        DB::transaction(function () use ($entrega, $request, $cuentaBancariaId, $registrarBanco) {
            EntregaDineroService::marcarComoRecibido(
                $entrega,
                auth()->id(),
                $cuentaBancariaId,
                $request->notas_recibido,
                $registrarBanco
            );
        });

        \Log::info('Entrega marcada como recibida', [
            'entrega_id' => $entrega->id,
            'usuario_id' => auth()->id(),
            'cuenta_bancaria_id' => $cuentaBancariaId,
            'total' => $entrega->total,
        ]);

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

        \Log::info('Entrega revertida a pendiente', [
            'entrega_id' => $entrega->id,
            'usuario_id' => auth()->id(),
            'total' => $entrega->total,
        ]);

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
        $query = EntregaDinero::with(['usuario', 'recibidoPor', 'children.origen'])
            ->whereNull('parent_id')
            ->belongsToEmpresa()
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
     * Crear un "Lote" de Entregas (Carrito de entregas)
     * Permite agrupar varias ventas o cobranzas en un solo registro de entrega.
     */
    public function entregarLote(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.tipo_origen' => 'required|string',
            'items.*.id_origen' => 'required|integer',
            'items.*.total' => 'required|numeric|min:0.01',
            'items.*.metodo_pago' => 'nullable|string',
            'notas' => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();
        $totalLote = 0;

        DB::transaction(function () use ($request, $userId, &$totalLote) {
            $items = $request->items;
            
            // Crear el Lote Padre temporalmente con 0
            $parentEntrega = EntregaDinero::create([
                'user_id' => $userId,
                'fecha_entrega' => now()->toDateString(),
                'monto_efectivo' => 0,
                'monto_transferencia' => 0,
                'monto_cheques' => 0,
                'monto_tarjetas' => 0,
                'monto_otros' => 0,
                'total' => 0,
                'estado' => 'pendiente',
                'tipo_origen' => 'lote',
                'id_origen' => null,
                'notas' => 'LOTE DE ENTREGAS' . ($request->notas ? "\n" . $request->notas : ''),
            ]);

            $montoEfectivo = 0;
            $montoTransferencia = 0;
            $montoCheques = 0;
            $montoTarjetas = 0;
            $montoOtros = 0;

            foreach ($items as $item) {
                $tipo_origen = $item['tipo_origen'];
                $id_origen = $item['id_origen'];
                $monto_recibido = (float)$item['total'];
                $metodo_pago_entrega = strtolower($item['metodo_pago'] ?? 'efectivo');

                if ($tipo_origen === 'cobranza') {
                    $registro = Cobranza::where('id', $id_origen)->where('estado', 'pagado')->firstOrFail();
                    $montoTotal = $registro->monto_pagado;
                } elseif ($tipo_origen === 'venta') {
                    $registro = Venta::where('id', $id_origen)->where('pagado', true)->firstOrFail();
                    $montoTotal = $registro->total;
                } else {
                    throw new \Exception('Tipo de registro no válido');
                }

                $entregasExistentes = EntregaDinero::where('tipo_origen', $tipo_origen)
                    ->where('id_origen', $id_origen)
                    ->whereIn('estado', ['pendiente', 'recibido'])
                    ->lockForUpdate()
                    ->get();

                $montoYaEntregado = $entregasExistentes->where('estado', 'recibido')->sum('total');
                $montoPendiente = $montoTotal - $montoYaEntregado;
                $hayEntregaPendiente = $entregasExistentes->where('estado', 'pendiente')->isNotEmpty();

                if ($hayEntregaPendiente) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => "Ya existe una entrega pendiente para {$tipo_origen} #{$id_origen}."
                    ]);
                }
                
                if ($monto_recibido > $montoPendiente + 0.01) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => "El monto excede el saldo pendiente en {$tipo_origen} #{$id_origen}"]);
                }

                switch ($metodo_pago_entrega) {
                    case 'efectivo': $montoEfectivo += $monto_recibido; break;
                    case 'transferencia': $montoTransferencia += $monto_recibido; break;
                    case 'cheque': $montoCheques += $monto_recibido; break;
                    case 'tarjeta': case 'tarjeta_credito': case 'tarjeta_debito': $montoTarjetas += $monto_recibido; break;
                    default: $montoOtros += $monto_recibido; break;
                }

                $child = EntregaDineroService::crearDesdeOrigen(
                    $tipo_origen,
                    $id_origen,
                    $monto_recibido,
                    $metodo_pago_entrega,
                    now()->toDateString(),
                    $userId,
                    'pendiente',
                    null,
                    'Entregado en Lote #' . $parentEntrega->id
                );
                
                $child->update(['parent_id' => $parentEntrega->id]);
                $totalLote += $monto_recibido;
            }

            $parentEntrega->update([
                'monto_efectivo' => $montoEfectivo,
                'monto_transferencia' => $montoTransferencia,
                'monto_cheques' => $montoCheques,
                'monto_tarjetas' => $montoTarjetas,
                'monto_otros' => $montoOtros,
                'total' => $totalLote,
            ]);
        });

        return redirect()->route('entregas-dinero.index')->with('success', 'Lote de depósitos creado por $' . number_format($totalLote, 2));
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
