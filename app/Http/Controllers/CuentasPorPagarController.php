<?php

namespace App\Http\Controllers;

use App\Models\CuentasPorPagar;
use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CuentasPorPagarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = CuentasPorPagar::with(['compra.proveedor']);

            // Filtros
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('proveedor_id')) {
                $query->whereHas('compra', function ($q) use ($request) {
                    $q->where('proveedor_id', $request->proveedor_id);
                });
            }

            // Ordenamiento: Pendientes primero, luego por fecha
            $sortBy = $request->get('sort_by', 'fecha_vencimiento');
            $sortDirection = $request->get('sort_direction', 'asc');

            // ✅ FIX: Ordenar por estado primero (pendientes/parcial/vencido arriba, pagados abajo)
            $query->orderByRaw("CASE 
                WHEN estado = 'vencido' THEN 1 
                WHEN estado = 'parcial' THEN 2 
                WHEN estado = 'pendiente' THEN 3 
                WHEN estado = 'pagado' THEN 4 
                WHEN estado = 'cancelada' THEN 5 
                ELSE 6 
            END");

            // Luego por fecha de vencimiento
            $query->orderBy($sortBy, $sortDirection);

            $cuentas = $query->paginate(15);

            // Estadísticas
            $stats = [
                'total_pendiente' => CuentasPorPagar::whereIn('estado', ['pendiente', 'parcial'])->sum('monto_pendiente'),
                'total_vencido' => CuentasPorPagar::where('estado', 'vencido')->sum('monto_pendiente'),
                'cuentas_pendientes' => CuentasPorPagar::whereIn('estado', ['pendiente', 'parcial'])->count(),
                'cuentas_vencidas' => CuentasPorPagar::where('estado', 'vencido')->count(),
            ];

            return Inertia::render('CuentasPorPagar/Index', [
                'cuentas' => $cuentas,
                'stats' => $stats,
                'filters' => $request->only(['estado', 'proveedor_id']),
                'sorting' => ['sort_by' => $sortBy, 'sort_direction' => $sortDirection],
                'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
                'proveedores' => \App\Models\Proveedor::where('activo', true)->orderBy('nombre_razon_social')->get(['id', 'nombre_razon_social']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in CuentasPorPagarController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $compraId = $request->get('compra_id');
        $compra = null;

        if ($compraId) {
            $compra = Compra::with('proveedor')->findOrFail($compraId);
        }

        return Inertia::render('CuentasPorPagar/Create', [
            'compra' => $compra,
            'compras' => Compra::with('proveedor')
                ->whereDoesntHave('cuentasPorPagar')
                ->where('estado', 'procesada')
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'compra_id' => 'required|exists:compras,id',
            'monto_total' => 'required|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date|after:today',
            'notas' => 'nullable|string|max:1000',
        ]);

        // Verificar que la compra no tenga ya una cuenta por pagar
        $compra = Compra::findOrFail($validated['compra_id']);
        if ($compra->cuentasPorPagar) {
            return redirect()->back()->with('error', 'Esta compra ya tiene una cuenta por pagar registrada.');
        }

        DB::transaction(function () use ($validated) {
            CuentasPorPagar::create([
                'compra_id' => $validated['compra_id'],
                'monto_total' => $validated['monto_total'],
                'monto_pagado' => 0,
                'monto_pendiente' => $validated['monto_total'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'estado' => 'pendiente',
                'notas' => $validated['notas'],
            ]);
        });

        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta por pagar creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cuenta = CuentasPorPagar::with(['compra.proveedor', 'compra.productos', 'pagadoPor', 'cfdi'])->findOrFail($id);

        return Inertia::render('CuentasPorPagar/Show', [
            'cuenta' => $cuenta,
            'cuentasBancarias' => \App\Models\CuentaBancaria::activas()->orderBy('banco')->orderBy('nombre')->get(['id', 'nombre', 'banco']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $cuenta = CuentasPorPagar::with('compra.proveedor')->findOrFail($id);

        // ✅ FIX: No permitir abrir formulario de edición para cuentas pagadas o canceladas
        if (in_array($cuenta->estado, ['cancelada', 'pagado'])) {
            return redirect()->route('cuentas-por-pagar.index')
                ->with('error', 'No se puede editar una cuenta ' . $cuenta->estado . '.');
        }

        return Inertia::render('CuentasPorPagar/Edit', [
            'cuenta' => $cuenta,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $cuenta = CuentasPorPagar::findOrFail($id);

        // ✅ FIX: No permitir editar cuentas canceladas o pagadas
        if (in_array($cuenta->estado, ['cancelada', 'pagado'])) {
            return redirect()->back()->with('error', 'No se puede editar una cuenta ' . $cuenta->estado . '.');
        }

        $validated = $request->validate([
            'monto_pagado' => 'nullable|numeric|min:0|max:' . $cuenta->monto_total,
            'fecha_vencimiento' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($cuenta, $validated) {
            if (isset($validated['monto_pagado'])) {
                $diferencia = $validated['monto_pagado'] - $cuenta->monto_pagado;
                if ($diferencia > 0) {
                    $cuenta->registrarPago($diferencia, 'Pago registrado desde edición');
                }
            }

            $cuenta->update([
                'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? $cuenta->fecha_vencimiento,
                'notas' => $validated['notas'] ?? $cuenta->notas,
            ]);
        });

        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta por pagar actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $cuenta = CuentasPorPagar::findOrFail($id);

        // ✅ FIX: Validar estado antes de eliminar
        if ($cuenta->estado === 'pagado') {
            return redirect()->back()->with('error', 'No se puede eliminar una cuenta pagada.');
        }

        // Solo permitir eliminar si no hay pagos registrados
        if ($cuenta->monto_pagado > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar una cuenta que ya tiene pagos registrados.');
        }

        $cuenta->delete();

        return redirect()->route('cuentas-por-pagar.index')->with('success', 'Cuenta por pagar eliminada correctamente.');
    }

    /**
     * Registrar un pago parcial
     */
    public function registrarPago(Request $request, string $id)
    {
        $cuenta = CuentasPorPagar::findOrFail($id);

        // ✅ FIX: No permitir pagos en cuentas pagadas o canceladas
        if (in_array($cuenta->estado, ['pagado', 'cancelada'])) {
            return redirect()->back()->with('error', 'No se puede registrar pago en una cuenta ' . $cuenta->estado . '.');
        }

        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01|max:' . $cuenta->monto_pendiente,
            'notas' => 'nullable|string|max:500',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        $cuenta->registrarPago($validated['monto'], $validated['notas'], $validated['cuenta_bancaria_id'] ?? null);

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Marcar cuenta como pagada con información detallada
     */
    public function marcarPagado(Request $request, string $id)
    {
        $request->validate([
            'metodo_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,otros',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'notas_pago' => 'nullable|string|max:500'
        ]);

        $cuenta = CuentasPorPagar::findOrFail($id);

        // Verificar que la cuenta no esté ya pagada
        if ($cuenta->pagado) {
            return response()->json([
                'success' => false,
                'error' => 'Esta cuenta ya está marcada como pagada'
            ], 400);
        }

        // Verificar que la cuenta no esté cancelada
        if ($cuenta->estado === 'cancelada') {
            return response()->json([
                'success' => false,
                'error' => 'No se puede marcar como pagada una cuenta cancelada'
            ], 400);
        }

        $cuenta->marcarPagado($request->metodo_pago, $request->cuenta_bancaria_id, $request->notas_pago);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta marcada como pagada exitosamente',
            'cuenta' => [
                'id' => $cuenta->id,
                'pagado' => true,
                'metodo_pago' => $cuenta->metodo_pago,
                'fecha_pago' => $cuenta->fecha_pago->format('Y-m-d'),
                'notas_pago' => $cuenta->notas_pago,
            ]
        ]);
    }

    /**
     * Importar XML de pago (complemento de pago) y buscar compras relacionadas.
     */
    public function importPaymentXml(Request $request, \App\Services\PaymentProcessingService $paymentService)
    {
        try {
            $request->validate(['xml' => 'required|file|mimes:xml']);

            $xmlContent = file_get_contents($request->file('xml')->getRealPath());
            return response()->json($paymentService->processPaymentXmlContent($xmlContent));

        } catch (\Exception $e) {
            Log::error('Error importando XML de pago CXP', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el XML: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Obtener lista de CFDIs de pago (tipo P) recibidos para selección.
     */
    public function getPaymentCfdis(Request $request)
    {
        try {
            $query = \App\Models\Cfdi::recibidos()
                ->tipoComprobante('P')
                ->whereNotNull('uuid')
                ->orderBy('fecha_emision', 'desc');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'ilike', "%{$search}%")
                        ->orWhere('nombre_emisor', 'ilike', "%{$search}%")
                        ->orWhere('rfc_emisor', 'ilike', "%{$search}%");
                });
            }

            $cfdis = $query->take(50)->get(['id', 'uuid', 'fecha_emision', 'total', 'nombre_emisor', 'rfc_emisor', 'xml_url']);

            // Enriquecer con datos del complemento de pagos
            $enrichedCfdis = $cfdis->map(function ($cfdi) {
                $montoTotal = 0;
                $numDocumentos = 0;

                // Intentar parsear XML para obtener MontoTotalPagos
                try {
                    $xmlPath = null;
                    if ($cfdi->xml_url) {
                        if (file_exists(storage_path('app/public/' . $cfdi->xml_url))) {
                            $xmlPath = storage_path('app/public/' . $cfdi->xml_url);
                        } elseif (file_exists(storage_path('app/' . $cfdi->xml_url))) {
                            $xmlPath = storage_path('app/' . $cfdi->xml_url);
                        }
                    }

                    if ($xmlPath) {
                        $xmlContent = file_get_contents($xmlPath);
                        $parser = app(\App\Services\CfdiXmlParserService::class);
                        $data = $parser->parseCfdiXml($xmlContent);

                        if (!empty($data['complementos']['pagos'])) {
                            foreach ($data['complementos']['pagos'] as $pago) {
                                $montoTotal += ($pago['monto'] ?? 0);
                                $numDocumentos += count($pago['doctos_relacionados'] ?? []);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error parseando XML de pago para lista', ['uuid' => $cfdi->uuid, 'error' => $e->getMessage()]);
                }

                return [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'fecha_emision' => $cfdi->fecha_emision,
                    'total' => $montoTotal > 0 ? $montoTotal : $cfdi->total,
                    'nombre_emisor' => $cfdi->nombre_emisor,
                    'rfc_emisor' => $cfdi->rfc_emisor,
                    'xml_url' => $cfdi->xml_url,
                    'num_documentos' => $numDocumentos,
                ];
            });

            return response()->json([
                'success' => true,
                'cfdis' => $enrichedCfdis,
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo CFDIs de pago', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener CFDIs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar CFDI de pago seleccionado desde el administrador de documentos.
     */
    public function processPaymentCfdi(Request $request, \App\Services\PaymentProcessingService $paymentService)
    {
        try {
            $request->validate(['cfdi_id' => 'required|exists:cfdis,id']);

            $cfdi = \App\Models\Cfdi::findOrFail($request->cfdi_id);

            if ($cfdi->tipo_comprobante !== 'P') {
                return response()->json([
                    'success' => false,
                    'message' => 'El CFDI seleccionado no es un complemento de pago (tipo P)',
                ], 422);
            }

            // Obtener contenido XML
            $xmlContent = null;
            if ($cfdi->xml_url && file_exists(storage_path('app/public/' . $cfdi->xml_url))) {
                $xmlContent = file_get_contents(storage_path('app/public/' . $cfdi->xml_url));
            } elseif ($cfdi->xml_url && file_exists(storage_path('app/' . $cfdi->xml_url))) {
                $xmlContent = file_get_contents(storage_path('app/' . $cfdi->xml_url));
            }

            if (!$xmlContent) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo XML del CFDI',
                ], 404);
            }

            return response()->json($paymentService->processPaymentXmlContent($xmlContent));

        } catch (\Exception $e) {
            Log::error('Error procesando CFDI de pago', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el CFDI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplicar pagos desde XML de pago previamente parseado.
     */
    public function applyPaymentsFromXml(Request $request, \App\Services\PaymentProcessingService $paymentService)
    {
        try {
            $validated = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.cuenta_id' => 'required|exists:cuentas_por_pagar,id',
                'payments.*.monto' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,transferencia,cheque,tarjeta,otros',
                'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'fecha_pago' => 'nullable|date',
                'notas' => 'nullable|string|max:1000',
            ]);

            $results = ['success' => [], 'errors' => []];

            foreach ($validated['payments'] as $payment) {
                try {
                    $paymentService->applySinglePayment(
                        $payment['cuenta_id'],
                        $payment['monto'],
                        $validated['metodo_pago'],
                        [
                            'cuenta_bancaria_id' => $validated['cuenta_bancaria_id'] ?? null,
                            'notas' => $validated['notas'] ?? 'Importado desde XML de Pago',
                            'fecha_pago' => $validated['fecha_pago'] ?? null,
                            'pagado_con_rep' => true,
                            'pue_pagado' => false
                        ]
                    );

                    $results['success'][] = [
                        'cuenta_id' => $payment['cuenta_id'],
                        'monto_aplicado' => $payment['monto'],
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'cuenta_id' => $payment['cuenta_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

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
            Log::error('Error aplicando pagos desde XML CXP', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors(['error' => 'Error al aplicar los pagos: ' . $e->getMessage()]);
        }
    }

    /**
     * Parsear contenido XML de pago.
     * (Método movido de PaymentProcessingService para mantener la lógica de parsing)
     */
    private function parsePaymentXml(string $xmlContent): array
    {
        try {
            $parser = app(\App\Services\CfdiXmlParserService::class);
            $data = $parser->parseCfdiXml($xmlContent);

            if ($data['tipo_comprobante'] !== 'P') {
                return ['success' => false, 'message' => 'El XML no es un complemento de pago (tipo P)'];
            }

            if (empty($data['complementos']['pagos'])) {
                return ['success' => false, 'message' => 'No se encontraron complementos de pago en el XML'];
            }

            // Consolidar documentos de todos los nodos de pago
            $documentosRelacionados = [];
            $montoTotal = 0;
            $fechaPago = null;
            $formaPago = null;
            $moneda = 'MXN';

            foreach ($data['complementos']['pagos'] as $pago) {
                $montoTotal += ($pago['monto'] ?? 0);
                $fechaPago = $pago['fecha_pago'] ?? $fechaPago;
                $formaPago = $pago['forma_pago'] ?? $formaPago;
                $moneda = $pago['moneda'] ?? $moneda;

                if (!empty($pago['doctos_relacionados'])) {
                    foreach ($pago['doctos_relacionados'] as $doc) {
                        $documentosRelacionados[] = [
                            'uuid' => $doc['id_documento'],
                            'serie' => $doc['serie'] ?? '',
                            'folio' => $doc['folio'] ?? '',
                            'moneda' => $doc['moneda_dr'] ?? 'MXN',
                            'num_parcialidad' => $doc['num_parcialidad'] ?? 1,
                            'imp_saldo_ant' => $doc['imp_saldo_ant'] ?? 0,
                            'imp_pagado' => $doc['imp_pagado'] ?? 0,
                            'imp_saldo_insoluto' => $doc['imp_saldo_insoluto'] ?? 0,
                        ];
                    }
                }
            }

            if (empty($documentosRelacionados)) {
                return ['success' => false, 'message' => 'No se encontraron documentos relacionados en el XML de pago'];
            }

            return [
                'success' => true,
                'uuid' => $data['uuid'],
                'fecha_pago' => $fechaPago,
                'forma_pago' => $formaPago,
                'metodo_pago_sistema' => $this->mapSatFormaPagoToSistema($formaPago),
                'monto_total' => $montoTotal,
                'moneda' => $moneda,
                'emisor' => $data['emisor'],
                'receptor' => $data['receptor'],
                'documentos_relacionados' => $documentosRelacionados,
            ];

        } catch (\Exception $e) {
            Log::error('Error parseando XML de pago CXP', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Error al parsear: ' . $e->getMessage()];
        }
    }

    /**
     * Map SAT payment method codes to system values
     */
    private function mapSatFormaPagoToSistema(?string $satCode): string
    {
        if (!$satCode)
            return 'otros';

        $mapping = [
            '01' => 'efectivo',
            '02' => 'cheque',
            '03' => 'transferencia',
            '04' => 'tarjeta', // Tarjeta de Crédito
            '28' => 'tarjeta', // Tarjeta de Débito
            '05' => 'tarjeta', // Monedero Electrónico
            '06' => 'tarjeta', // Dinero Electrónico
            '08' => 'otros',    // Vales de Despensa
            '12' => 'otros',    // Dación en pago
            '17' => 'otros',    // Compensación
            '30' => 'otros',    // Aplicación de anticipos
            '99' => 'otros',    // Por definir
        ];

        return $mapping[$satCode] ?? 'otros';
    }
}
