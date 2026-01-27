<?php

namespace App\Http\Controllers;

use App\Models\CuentasPorCobrar;
use App\Models\Venta;
use App\Models\Renta;
use App\Models\CuentaBancaria;
use App\Models\Cliente;
use App\Enums\EstadoVenta;
use App\Services\PaymentService;
use App\Services\Cfdi\CfdiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CuentasPorCobrarController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CfdiService $cfdiService // ✅ Inyección
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CuentasPorCobrar::with(['cobrable.cliente', 'cliente']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        } else {
            $query->where('estado', '!=', 'cancelada');
        }

        if ($request->filled('cliente_id')) {
            $query->whereHasMorph('cobrable', [Venta::class, Renta::class], function ($q) use ($request) {
                $q->where('cliente_id', $request->cliente_id);
            });
        }

        if ($request->filled('type')) {
            $type = $request->type;
            if ($type === 'renta') {
                $query->where('cobrable_type', Renta::class);
            } elseif ($type === 'venta') {
                $query->where('cobrable_type', Venta::class);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Busqueda polimórfica: Usar '*' para buscar en TODOS los tipos sin importar formato
                $q->whereHasMorph('cobrable', '*', function ($sq, $type) use ($search) {
                    // Buscar en Cliente relacionado (Venta, Renta, Poliza tienen ->cliente())
                    $sq->whereHas('cliente', function ($cq) use ($search) {
                        $cq->where('clientes.nombre_razon_social', 'ilike', "%{$search}%")
                            ->orWhere('clientes.rfc', 'ilike', "%{$search}%")
                            ->orWhere('clientes.telefono', 'ilike', "%{$search}%");
                    });

                    // Buscar por folio específico según el tipo (detectar por substring)
                    $typeLower = strtolower($type);
                    if (str_contains($typeLower, 'venta')) {
                        $sq->orWhere('numero_venta', 'ilike', "%{$search}%");
                    } elseif (str_contains($typeLower, 'renta')) {
                        $sq->orWhere('numero_contrato', 'ilike', "%{$search}%");
                    } elseif (str_contains($typeLower, 'poliza')) {
                        $sq->orWhere('folio', 'ilike', "%{$search}%");
                    }
                })
                    // También buscar en cliente directo si la CxC tiene cliente_id directo
                    ->orWhereHas('cliente', function ($cq) use ($search) {
                        $cq->where('clientes.nombre_razon_social', 'ilike', "%{$search}%");
                    });
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Priorizar pendientes y parciales al principio si no hay ordenamiento específico de estado
        if (!$request->has('sort_by') || $request->get('sort_by') !== 'estado') {
            $query->orderByRaw("CASE WHEN estado IN ('pendiente', 'parcial', 'vencido') THEN 1 ELSE 2 END ASC");
            // Dentro del grupo de pendientes, ordenar por fecha de vencimiento ascendente (los próximos a vencer o vencidos primero)
            // Para los pagados (ELSE), asignamos una fecha futura lejana para que no afecte este ordenamiento y se rijan por el siguiente (created_at)
            $query->orderByRaw("CASE WHEN estado IN ('pendiente', 'parcial', 'vencido') THEN fecha_vencimiento ELSE '9999-12-31' END ASC");
        }

        $query->orderBy($sortBy, $sortDirection);

        $cuentas = $query->paginate($request->integer('per_page', 15));

        /** @var \Illuminate\Pagination\LengthAwarePaginator $cuentas */
        $cuentas->getCollection()->transform(function ($cuenta) {
            // Si la relación polimórfica no cargó correctamente, cargarla manualmente
            if (!$cuenta->cobrable && $cuenta->cobrable_id) {
                if (str_contains($cuenta->cobrable_type, 'Venta')) {
                    $venta = Venta::with('cliente')->find($cuenta->cobrable_id);
                    if ($venta) {
                        $cuenta->setRelation('cobrable', $venta);
                    }
                }
            }

            // Asegurar que los datos necesarios estén disponibles
            if ($cuenta->cobrable) {
                $cuenta->cobrable_data = [
                    'numero_venta' => $cuenta->cobrable->numero_venta ?? null,
                    'numero_contrato' => $cuenta->cobrable->numero_contrato ?? null,
                    'folio' => $cuenta->cobrable->folio ?? null,
                    'cliente' => $cuenta->cobrable->cliente ?? $cuenta->cliente ?? null,
                ];
            }

            return $cuenta;
        });

        $stats = [
            'total_pendiente' => CuentasPorCobrar::whereIn('estado', ['pendiente', 'parcial'])->sum('monto_pendiente'),
            'total_vencido' => CuentasPorCobrar::where('estado', 'vencido')->sum('monto_pendiente'),
            'cuentas_pendientes' => CuentasPorCobrar::whereIn('estado', ['pendiente', 'parcial'])->count(),
            'cuentas_vencidas' => CuentasPorCobrar::where('estado', 'vencido')->count(),
        ];

        return Inertia::render('CuentasPorCobrar/Index', [
            'cuentas' => $cuentas,
            'stats' => $stats,
            'filters' => $request->only(['estado', 'cliente_id', 'type']),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    // ... create and store methods (left mostly as is for Venta creation manual, but Venta::with usage updated if needed) ...

    public function show(string $id)
    {
        // Load cobrable and its cliente, and payment movements
        $cuenta = CuentasPorCobrar::with(['cobrable.cliente', 'movimientosBancarios.cuentaBancaria'])->findOrFail($id);

        // Sincronización manual si la relación polimórfica falló (similar a index)
        if (!$cuenta->cobrable && $cuenta->cobrable_id) {
            if (str_contains($cuenta->cobrable_type, 'Venta')) {
                $venta = Venta::with('cliente')->find($cuenta->cobrable_id);
                if ($venta) {
                    $cuenta->setRelation('cobrable', $venta);
                }
            } elseif (str_contains($cuenta->cobrable_type, 'Renta')) {
                $renta = Renta::with('cliente')->find($cuenta->cobrable_id);
                if ($renta) {
                    $cuenta->setRelation('cobrable', $renta);
                }
            }
        }

        // Asegurar que cobrable_data esté disponible para el frontend
        if ($cuenta->cobrable) {
            $cuenta->cobrable_data = [
                'numero_venta' => $cuenta->cobrable->numero_venta ?? null,
                'numero_contrato' => $cuenta->cobrable->numero_contrato ?? null,
                'cliente' => $cuenta->cobrable->cliente ?? null,
            ];

            // Cargar historial de pagos (Entregas de Dinero)
            // Buscamos entregas asociadas al cobrable (Venta/Renta) o a la CxC misma
            $tipoOrigen = $cuenta->cobrable instanceof \App\Models\Renta ? 'renta' : 'venta';

            $pagos = \App\Models\EntregaDinero::where(function ($q) use ($cuenta, $tipoOrigen) {
                // Pagos asociados al cobrable (lo más común según PaymentService)
                $q->where('id_origen', $cuenta->cobrable_id)
                    ->where('tipo_origen', $tipoOrigen);
            })->orWhere(function ($q) use ($cuenta) {
                // Pagos asociados directamente a la CxC (casos legacy o directos)
                $q->where('id_origen', $cuenta->id)
                    ->where('tipo_origen', 'cuentas_por_cobrar');
            })
                ->with(['cuentaBancaria', 'usuario']) // Cargar detalles de banco y usuario
                ->orderBy('fecha_entrega', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $cuenta->historial_pagos = $pagos;

            // Cargar items/equipos según el tipo real
            $cobrable = $cuenta->cobrable;
            if ($cobrable instanceof Venta) {
                $cobrable->load([
                    'items.ventable',
                    'cfdis' => function ($q) {
                        $q->orderBy('created_at', 'desc');
                    }
                ]);

                // Atributos CFDI para el modal (replicando lógica de VentaController)
                $cuenta->esta_facturada = $cobrable->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->isNotEmpty();
                $cuenta->cfdi_cancelado = $cobrable->cfdis->where('estatus', 'cancelado')->isNotEmpty();
                $cuenta->cfdi_cancelado_uuid = $cobrable->cfdis->where('estatus', 'cancelado')->first()?->uuid;
                $cuenta->factura_uuid = $cobrable->cfdis->whereIn('estatus', ['timbrado', 'vigente'])->first()?->uuid;

                $cuenta->ppd_cfdi_exists = $cobrable->cfdis()
                    ->timbrados()
                    ->where('metodo_pago', 'PPD')
                    ->exists();

                // Auditoría unificada
                $cuenta->metadata = [
                    'creado_por' => $cuenta->created_by_user_name ?? $cobrable->created_by_user_name ?? 'N/A',
                    'actualizado_por' => $cuenta->updated_by_user_name ?? $cobrable->updated_by_user_name ?? 'N/A',
                    'creado_en' => $cuenta->created_at,
                    'actualizado_en' => $cuenta->updated_at,
                ];
            } elseif ($cobrable instanceof Renta) {
                $cobrable->load('equipos');
                $cuenta->metadata = [
                    'creado_por' => $cuenta->created_by_user_name ?? 'N/A',
                    'actualizado_por' => $cuenta->updated_by_user_name ?? 'N/A',
                    'creado_en' => $cuenta->created_at,
                    'actualizado_en' => $cuenta->updated_at,
                ];
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'cuenta' => $cuenta,
                'cuentasBancarias' => CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
            ]);
        }

        return Inertia::render('CuentasPorCobrar/Show', [
            'cuenta' => $cuenta,
            'cuentasBancarias' => CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
        ]);
    }

    public function create()
    {
        return Inertia::render('CuentasPorCobrar/Create', [
            'clientes' => Cliente::orderBy('nombre_razon_social')->get(['id', 'nombre_razon_social']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cobrable_id' => 'required',
            'cobrable_type' => 'required',
            'monto_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'notas' => 'nullable|string',
        ]);

        $cuenta = CuentasPorCobrar::create([
            ...$validated,
            'monto_pagado' => 0,
            'monto_pendiente' => $validated['monto_total'],
            'estado' => 'pendiente',
        ]);

        return redirect()->route('cuentas-por-cobrar.index')->with('success', 'Cuenta por cobrar creada correctamente.');
    }

    public function edit(string $id)
    {
        $cuenta = CuentasPorCobrar::with('cobrable.cliente')->findOrFail($id);
        return Inertia::render('CuentasPorCobrar/Edit', [
            'cuenta' => $cuenta,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $cuenta = CuentasPorCobrar::findOrFail($id);

        $validated = $request->validate([
            'fecha_vencimiento' => 'nullable|date',
            'notas' => 'nullable|string',
            'monto_total' => 'required|numeric|min:0',
        ]);

        $cuenta->update($validated);
        $cuenta->actualizarEstado(); // Recalcular saldo y estado si cambió el total

        return redirect()->route('cuentas-por-cobrar.index')->with('success', 'Cuenta por cobrar actualizada.');
    }

    public function destroy(string $id)
    {
        $cuenta = CuentasPorCobrar::findOrFail($id);
        $cuenta->delete();

        return redirect()->route('cuentas-por-cobrar.index')->with('success', 'Cuenta por cobrar eliminada.');
    }

    // ... edit and update methods ...

    public function registrarPago(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            // Lock and load cobrable
            $cuenta = CuentasPorCobrar::with('cobrable')
                ->lockForUpdate()
                ->findOrFail($id);

            // Recalcular pendiente con datos bloqueados
            $pendienteActual = $cuenta->calcularPendiente();

            $validated = $request->validate([
                'monto' => 'required|numeric|min:0.01|max:' . number_format($pendienteActual, 2, '.', ''),
                'fecha_pago' => 'nullable|date',
                'notas' => 'nullable|string|max:1000',
                'metodo_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,tarjeta_credito,tarjeta_debito,otros',
                // ✅ CRITICAL FIX: Require bank account for bank methods to ensure MovimientoBancario is created
                'cuenta_bancaria_id' => 'required_if:metodo_pago,transferencia,cheque,tarjeta,tarjeta_credito,tarjeta_debito|nullable|exists:cuentas_bancarias,id',
            ]);

            // ✅ REGISTRAR PAGO UNIFICADO (CuentasPorCobrar + EntregaDinero + Banco si aplica)
            $this->paymentService->registrarPago(
                $cuenta,
                $validated['monto'],
                $validated['metodo_pago'],
                $validated['notas'] ?? null,
                Auth::id(),
                $validated['cuenta_bancaria_id'] ?? null
            );

            $cuenta->refresh();

            // Actualizar metadata en el origen (Venta o Renta) si es necesario para el estado global
            if ($cuenta->cobrable && $cuenta->cobrable instanceof Venta) {
                $cuenta->cobrable->update([
                    'metodo_pago' => $validated['metodo_pago'],
                    'notas_pago' => $validated['notas'] ?? $cuenta->cobrable->notas_pago,
                    'fecha_pago' => $cuenta->estado === 'pagado' ? now() : $cuenta->cobrable->fecha_pago,
                    'pagado_por' => Auth::id(),
                    'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? $cuenta->cobrable->cuenta_bancaria_id,
                ]);
            } elseif ($cuenta->cobrable && $cuenta->cobrable instanceof Renta) {
                if ($cuenta->estado === 'pagado') {
                    $cuenta->cobrable->update([
                        'ultimo_pago' => now(),
                    ]);
                }
            }

            DB::commit();

            // ✅ TIMBRADO AUTOMÁTICO DE COMPLEMENTO DE PAGO (REP)
            // Si es una Venta + Cliente Factura + Factura PPD existe
            $mensajeExtra = '';

            if ($cuenta->cobrable && $cuenta->cobrable instanceof Venta) {
                $venta = $cuenta->cobrable;
                $cliente = $venta->cliente;

                // Verificar condiciones para REP
                if ($cliente->requiere_factura && $venta->cfdis()->timbrados()->where('metodo_pago', 'PPD')->exists()) {
                    try {
                        // Llamar al timbrado asíncrono o síncrono (por ahora síncrono para feedback)
                        $res = $this->cfdiService->timbrarPago(
                            $cuenta,
                            $validated['monto'],
                            $validated['metodo_pago'],
                            now() // Fecha pago
                        );

                        if ($res['success']) {
                            $mensajeExtra = ' y Complemento de Pago enviado.';
                        } else {
                            $mensajeExtra = '. (Error al timbrar pago: ' . $res['message'] . ')';
                        }
                    } catch (\Exception $e) {
                        Log::error("Error auto-timbrando pago CXC {$id}: " . $e->getMessage());
                        $mensajeExtra = '. (Error intentando timbrar complemento).';
                    }
                }
            }

            return redirect()->back()->with('success', 'Pago registrado correctamente' . $mensajeExtra);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error registrando pago en CXC', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    /**
     * Anular un pago (Entrega de Dinero) realizado a una cuenta.
     */
    public function anularPago(Request $request, $id)
    {
        try {
            DB::transaction(function () use ($id, $request) {
                // 1. Obtener la entrega (Pago)
                $entrega = \App\Models\EntregaDinero::findOrFail($id);
                $monto = $entrega->total;

                // 2. Revertir saldo en Cuenta Bancaria (si aplica y fue recibido)
                // Usamos 'retiro' para restar el saldo de la cuenta
                if ($entrega->cuenta_bancaria_id && $entrega->estado === 'recibido') {
                    $cuentaBancaria = \App\Models\CuentaBancaria::find($entrega->cuenta_bancaria_id);
                    if ($cuentaBancaria) {
                        $cuentaBancaria->registrarMovimiento(
                            'retiro',
                            (float) $monto,
                            "Reversión de Pago #{$entrega->id}",
                            'reversion'
                        );
                    }
                }

                // 3. Revertir Cuenta Por Cobrar
                $cx = null;
                $cxcId = $request->input('cxc_id');

                if ($cxcId) {
                    $cx = CuentasPorCobrar::find($cxcId);
                }

                if (!$cx) {
                    if ($entrega->tipo_origen === 'cuentas_por_cobrar') {
                        $cx = CuentasPorCobrar::find($entrega->id_origen);
                    } else {
                        // Si es venta/renta, buscar la CxC ligada
                        // Intentamos buscar una que esté pagada o parcial del mismo cobrable
                        $cx = CuentasPorCobrar::where('cobrable_id', $entrega->id_origen)
                            ->where('cobrable_type', $entrega->tipo_origen)
                            ->whereIn('estado', ['pagado', 'parcial'])
                            ->first();

                        // Fallback si no hay ninguna pagada/parcial (por si acaso)
                        if (!$cx) {
                            $cx = CuentasPorCobrar::where('cobrable_id', $entrega->id_origen)
                                ->where('cobrable_type', $entrega->tipo_origen)
                                ->first();
                        }
                    }
                }

                if ($cx) {
                    $cx->monto_pagado = max(0, $cx->monto_pagado - $monto);
                    $cx->notas .= "\n[" . now()->format('Y-m-d H:i') . "] Pago anulado: {$monto} (Ref #{$entrega->id})";
                    $cx->actualizarEstado();
                } else {
                    \Log::warning("No se encontró CxC para anular pago #{$id}. Origen: {$entrega->tipo_origen} #{$entrega->id_origen}");
                }

                // 4. Eliminar entrega
                $entrega->delete();
            });

            return back()->with('success', 'Pago revertido y saldos ajustados correctamente.');

        } catch (\Exception $e) {
            \Log::error('Error al anular pago: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al anular el pago: ' . $e->getMessage()]);
        }
    }

    public function timbrarReciboPago(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $cuenta = CuentasPorCobrar::with('cobrable.cliente')
                ->lockForUpdate()
                ->findOrFail($id);

            if (!$cuenta->cobrable || !$cuenta->cobrable instanceof Venta) {
                throw new \Exception('Solo se puede timbrar REP para ventas.');
            }

            $venta = $cuenta->cobrable;
            $cliente = $venta->cliente;

            if (!$cliente || !$cliente->requiere_factura) {
                throw new \Exception('El cliente no tiene factura habilitada para generar REP.');
            }

            if (!$venta->cfdis()->timbrados()->where('metodo_pago', 'PPD')->exists()) {
                throw new \Exception('No existe una factura PPD timbrada para generar el REP.');
            }

            $pendienteActual = $cuenta->calcularPendiente();

            $validated = $request->validate([
                'monto' => 'required|numeric|min:0.01|max:' . $pendienteActual,
                'fecha_pago' => 'required|date',
                'metodo_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,tarjeta_credito,tarjeta_debito,otros',
                // ✅ CRITICAL FIX: Require bank account for bank methods
                'cuenta_bancaria_id' => 'required_if:metodo_pago,transferencia,cheque,tarjeta,tarjeta_credito,tarjeta_debito|nullable|exists:cuentas_bancarias,id',
                'notas' => 'nullable|string|max:1000',
            ]);

            $fechaPago = Carbon::parse($validated['fecha_pago']);

            $this->paymentService->registrarPago(
                $cuenta,
                $validated['monto'],
                $validated['metodo_pago'],
                $validated['notas'] ?? null,
                Auth::id(),
                $validated['cuenta_bancaria_id'] ?? null,
                $fechaPago
            );

            $cuenta->refresh();

            $venta->update([
                'metodo_pago' => $validated['metodo_pago'],
                'notas_pago' => $validated['notas'] ?? $venta->notas_pago,
                'fecha_pago' => $cuenta->estado === 'pagado' ? $fechaPago : $venta->fecha_pago,
                'pagado_por' => Auth::id(),
                'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? $venta->cuenta_bancaria_id,
            ]);

            DB::commit();

            $mensajeExtra = '';

            try {
                $res = $this->cfdiService->timbrarPago(
                    $cuenta,
                    $validated['monto'],
                    $validated['metodo_pago'],
                    $fechaPago
                );

                if ($res['success']) {
                    $mensajeExtra = ' y Complemento de Pago enviado.';
                } else {
                    $mensajeExtra = '. (Error al timbrar pago: ' . $res['message'] . ')';
                }
            } catch (\Exception $e) {
                Log::error("Error timbrando REP CXC {$id}: " . $e->getMessage());
                $mensajeExtra = '. (Error intentando timbrar complemento).';
            }

            return redirect()->back()->with('success', 'Pago registrado correctamente' . $mensajeExtra);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error timbrando REP en CXC', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Error al generar el REP: ' . $e->getMessage()]);
        }
    }

    /**
     * Importar XML de pago (complemento de pago) y buscar facturas relacionadas.
     */
    public function importPaymentXml(Request $request)
    {
        try {
            $request->validate(['xml' => 'required|file|mimes:xml']);

            $xmlContent = file_get_contents($request->file('xml')->getRealPath());
            $paymentData = $this->parsePaymentXml($xmlContent);

            if (!$paymentData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $paymentData['message'] ?? 'Error al parsear el XML de pago',
                ], 422);
            }

            // Buscar facturas relacionadas por UUID
            $matches = [];
            foreach ($paymentData['documentos_relacionados'] as $docRel) {
                // Buscar en ventas por cfdi_uuid
                $venta = Venta::where('cfdi_uuid', $docRel['uuid'])->first();
                $cuenta = null;

                if ($venta) {
                    // Buscar la cuenta por cobrar asociada
                    $cuenta = CuentasPorCobrar::where('cobrable_type', Venta::class)
                        ->where('cobrable_id', $venta->id)
                        ->with('cobrable.cliente')
                        ->first();
                }

                $matches[] = [
                    'uuid' => $docRel['uuid'],
                    'imp_saldo_ant' => $docRel['imp_saldo_ant'],
                    'imp_pagado' => $docRel['imp_pagado'],
                    'imp_saldo_insoluto' => $docRel['imp_saldo_insoluto'],
                    'found' => $cuenta !== null,
                    'cuenta_id' => $cuenta?->id,
                    'cuenta_estado' => $cuenta?->estado,
                    'monto_pendiente' => $cuenta?->monto_pendiente,
                    'cliente_nombre' => $cuenta?->cobrable?->cliente?->nombre_razon_social ?? 'N/A',
                    'numero_venta' => $venta?->numero_venta ?? 'N/A',
                ];
            }

            return response()->json([
                'success' => true,
                'payment_info' => [
                    'uuid' => $paymentData['uuid'],
                    'fecha_pago' => $paymentData['fecha_pago'],
                    'forma_pago' => $paymentData['forma_pago'],
                    'monto_total' => $paymentData['monto_total'],
                    'moneda' => $paymentData['moneda'],
                    'emisor' => $paymentData['emisor'],
                    'receptor' => $paymentData['receptor'],
                ],
                'matches' => $matches,
                'total_documentos' => count($matches),
                'documentos_encontrados' => count(array_filter($matches, fn($m) => $m['found'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Error importando XML de pago', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplicar pagos desde XML de pago previamente parseado.
     */
    public function applyPaymentsFromXml(Request $request)
    {
        try {
            $validated = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.cuenta_id' => 'required|exists:cuentas_por_cobrar,id',
                'payments.*.monto' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,tarjeta_credito,tarjeta_debito,otros',
                'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'fecha_pago' => 'nullable|date',
                'notas' => 'nullable|string|max:1000',
            ]);

            $results = ['success' => [], 'errors' => []];
            $fechaPago = isset($validated['fecha_pago']) ? Carbon::parse($validated['fecha_pago']) : now();

            DB::transaction(function () use ($validated, $fechaPago, &$results) {
                foreach ($validated['payments'] as $payment) {
                    try {
                        $cuenta = CuentasPorCobrar::with('cobrable')
                            ->lockForUpdate()
                            ->findOrFail($payment['cuenta_id']);

                        $pendiente = $cuenta->calcularPendiente();
                        $montoAplicar = min($payment['monto'], $pendiente);

                        if ($montoAplicar <= 0) {
                            $results['errors'][] = [
                                'cuenta_id' => $payment['cuenta_id'],
                                'error' => 'No hay saldo pendiente',
                            ];
                            continue;
                        }

                        $this->paymentService->registrarPago(
                            $cuenta,
                            $montoAplicar,
                            $validated['metodo_pago'],
                            $validated['notas'] ?? 'Importado desde XML de Pago',
                            Auth::id(),
                            $validated['cuenta_bancaria_id'] ?? null,
                            $fechaPago
                        );

                        // Actualizar metadatos en Venta si aplica
                        if ($cuenta->cobrable && $cuenta->cobrable instanceof Venta) {
                            $cuenta->cobrable->update([
                                'metodo_pago' => $validated['metodo_pago'],
                                'fecha_pago' => $cuenta->fresh()->estado === 'pagado' ? $fechaPago : $cuenta->cobrable->fecha_pago,
                                'pagado_por' => Auth::id(),
                                'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? $cuenta->cobrable->cuenta_bancaria_id,
                            ]);
                        }

                        $results['success'][] = [
                            'cuenta_id' => $payment['cuenta_id'],
                            'monto_aplicado' => $montoAplicar,
                        ];

                    } catch (\Exception $e) {
                        $results['errors'][] = [
                            'cuenta_id' => $payment['cuenta_id'],
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            });

            $successCount = count($results['success']);
            $errorCount = count($results['errors']);

            if ($errorCount === 0) {
                return redirect()->back()->with('success', "{$successCount} pago(s) aplicado(s) correctamente desde XML.");
            } else {
                $errorDetails = collect($results['errors'])->pluck('error')->implode(', ');
                return redirect()->back()
                    ->with('success', "{$successCount} pago(s) aplicado(s).")
                    ->withErrors(['warning' => "{$errorCount} error(es): {$errorDetails}"]);
            }

        } catch (\Exception $e) {
            Log::error('Error aplicando pagos desde XML', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Error al aplicar los pagos: ' . $e->getMessage()]);
        }
    }

    /**
     * Parsear XML de complemento de pago (CFDI tipo P).
     */
    private function parsePaymentXml(string $xmlContent): array
    {
        try {
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                return ['success' => false, 'message' => 'XML inválido'];
            }

            // Registrar namespaces
            $namespaces = $xml->getNamespaces(true);

            // Verificar que sea un CFDI tipo P (Pago)
            $tipoComprobante = (string) $xml['TipoDeComprobante'];
            if ($tipoComprobante !== 'P') {
                return ['success' => false, 'message' => 'El XML no es un complemento de pago (tipo P)'];
            }

            // Datos generales del CFDI
            $uuid = null;
            $tfd = $xml->children($namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4')
                ->Complemento
                ->children($namespaces['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital');
            if (isset($tfd->TimbreFiscalDigital)) {
                $uuid = (string) $tfd->TimbreFiscalDigital['UUID'];
            }

            // Datos del emisor (quien recibe el pago - normalmente tu empresa)
            $emisor = [
                'rfc' => (string) $xml->Emisor['Rfc'],
                'nombre' => (string) $xml->Emisor['Nombre'],
            ];

            // Datos del receptor (quien paga - cliente)
            $receptor = [
                'rfc' => (string) $xml->Receptor['Rfc'],
                'nombre' => (string) $xml->Receptor['Nombre'],
            ];

            // Buscar el complemento de pagos (namespace pago20 o pago10)
            $pagosNs = $namespaces['pago20'] ?? $namespaces['pago10'] ?? null;
            $complemento = $xml->children($namespaces['cfdi'] ?? 'http://www.sat.gob.mx/cfd/4')->Complemento;

            $documentosRelacionados = [];
            $fechaPago = null;
            $formaPago = null;
            $montoTotal = 0;
            $moneda = 'MXN';

            if ($pagosNs && $complemento) {
                $pagos = $complemento->children($pagosNs);

                if (isset($pagos->Pagos)) {
                    // Totales
                    if (isset($pagos->Pagos->Totales)) {
                        $montoTotal = (float) $pagos->Pagos->Totales['MontoTotalPagos'];
                    }

                    // Cada pago
                    foreach ($pagos->Pagos->Pago as $pago) {
                        $fechaPago = (string) $pago['FechaPago'];
                        $formaPago = (string) $pago['FormaDePagoP'];
                        $moneda = (string) ($pago['MonedaP'] ?? 'MXN');

                        // Documentos relacionados (facturas pagadas)
                        foreach ($pago->DoctoRelacionado as $docto) {
                            $documentosRelacionados[] = [
                                'uuid' => (string) $docto['IdDocumento'],
                                'serie' => (string) ($docto['Serie'] ?? ''),
                                'folio' => (string) ($docto['Folio'] ?? ''),
                                'moneda' => (string) ($docto['MonedaDR'] ?? 'MXN'),
                                'num_parcialidad' => (int) ($docto['NumParcialidad'] ?? 1),
                                'imp_saldo_ant' => (float) ($docto['ImpSaldoAnt'] ?? 0),
                                'imp_pagado' => (float) ($docto['ImpPagado'] ?? 0),
                                'imp_saldo_insoluto' => (float) ($docto['ImpSaldoInsoluto'] ?? 0),
                            ];
                        }
                    }
                }
            }

            if (empty($documentosRelacionados)) {
                return ['success' => false, 'message' => 'No se encontraron documentos relacionados en el XML de pago'];
            }

            return [
                'success' => true,
                'uuid' => $uuid,
                'fecha_pago' => $fechaPago,
                'forma_pago' => $formaPago,
                'monto_total' => $montoTotal,
                'moneda' => $moneda,
                'emisor' => $emisor,
                'receptor' => $receptor,
                'documentos_relacionados' => $documentosRelacionados,
            ];

        } catch (\Exception $e) {
            Log::error('Error parseando XML de pago', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error al parsear: ' . $e->getMessage()];
        }
    }

    // =====================================================
    // MÉTODOS API PARA IONIC / FRONTEND MOBILE
    // =====================================================

    /**
     * Obtener cobranzas del día de hoy (API Mobile)
     */
    public function hoy()
    {
        $hoy = Carbon::today();

        $cobranzas = CuentasPorCobrar::with(['cobrable.cliente'])
            ->whereIn('estado', ['pendiente', 'parcial', 'vencido'])
            ->whereDate('fecha_vencimiento', $hoy)
            ->orderBy('monto_pendiente', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'hoy' => $cobranzas,
            'total' => $cobranzas->count(),
            'monto_total' => $cobranzas->sum('monto_pendiente'),
        ]);
    }

    /**
     * Obtener cobranzas próximas a vencer (API Mobile)
     */
    public function proximas(Request $request)
    {
        $incluirVencidas = $request->boolean('incluir_vencidas', false);
        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(7);

        $query = CuentasPorCobrar::with(['cobrable.cliente'])
            ->whereIn('estado', ['pendiente', 'parcial', 'vencido']);

        if ($incluirVencidas) {
            $query->where('fecha_vencimiento', '<=', $limite);
        } else {
            $query->whereBetween('fecha_vencimiento', [$hoy, $limite]);
        }

        $cobranzas = $query->orderBy('fecha_vencimiento', 'asc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cobranzas,
            'total' => $cobranzas->count(),
            'monto_total' => $cobranzas->sum('monto_pendiente'),
        ]);
    }

    /**
     * Obtener listado de cuentas bancarias activas (API Mobile)
     */
    public function cuentasBancarias()
    {
        $cuentas = CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco', 'numero_cuenta', 'clabe']);

        return response()->json([
            'success' => true,
            'data' => $cuentas,
        ]);
    }
}
