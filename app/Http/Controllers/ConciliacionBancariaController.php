<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBancario;
use App\Services\BankStatementParserService;
use App\Services\ConciliacionBancariaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConciliacionBancariaController extends Controller
{
    public function __construct(
        protected BankStatementParserService $parserService,
        protected ConciliacionBancariaService $conciliacionService
    ) {}

    /**
     * Vista principal de conciliación bancaria
     */
    public function index(Request $request)
    {
        $estado = $request->get('estado', 'pendiente');
        $tipo = $request->get('tipo');
        $banco = $request->get('banco');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = MovimientoBancario::with(['conciliable', 'usuario', 'conciliadoPor'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        // Filtros
        if ($estado && $estado !== 'todos') {
            $query->where('estado', $estado);
        }
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        if ($banco) {
            $query->where('banco', $banco);
        }
        if ($fechaDesde) {
            $query->whereDate('fecha', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('fecha', '<=', $fechaHasta);
        }

        $movimientos = $query->paginate(20)->withQueryString();

        // Resumen
        $resumen = $this->conciliacionService->getResumenPendientes();

        // Bancos disponibles
        $bancos = MovimientoBancario::select('banco')
            ->distinct()
            ->pluck('banco')
            ->toArray();

        $empresaId = auth()->user()->empresa_id ?? 8;

        $cuentasCobrar = \App\Models\CuentasPorCobrar::where('empresa_id', $empresaId)
            ->with(['cliente', 'cobrable', 'cfdi'])
            ->pendientes()
            ->get()
            ->map(function ($cxc) {
                return [
                    'id' => $cxc->id,
                    'cfdi_id' => $cxc->cfdi_id,
                    'monto_total' => (float) $cxc->monto_total,
                    'monto_pendiente' => round((float) $cxc->monto_pendiente, 2),
                    'fecha_vencimiento' => $cxc->fecha_vencimiento ? ($cxc->fecha_vencimiento instanceof \Carbon\Carbon ? $cxc->fecha_vencimiento->format('Y-m-d') : $cxc->fecha_vencimiento) : null,
                    'estado' => $cxc->estado,
                    'referencia' => $cxc->cfdi ? ($cxc->cfdi->folio ?? '') : ($cxc->cobrable ? ($cxc->cobrable->folio ?? '') : ''),
                    'cliente' => [
                        'nombre_razon_social' => $cxc->cliente ? $cxc->cliente->nombre_razon_social : 'Público en General',
                        'rfc' => $cxc->cliente ? $cxc->cliente->rfc : '',
                    ],
                ];
            });

        $cuentasPagar = \App\Models\CuentasPorPagar::where('empresa_id', $empresaId)
            ->with(['proveedor', 'compra', 'cfdi'])
            ->pendientes()
            ->get()
            ->map(function ($cxp) {
                return [
                    'id' => $cxp->id,
                    'cfdi_id' => $cxp->cfdi_id,
                    'monto_total' => (float) $cxp->monto_total,
                    'monto_pendiente' => round((float) $cxp->monto_pendiente, 2),
                    'fecha_vencimiento' => $cxp->fecha_vencimiento ? ($cxp->fecha_vencimiento instanceof \Carbon\Carbon ? $cxp->fecha_vencimiento->format('Y-m-d') : $cxp->fecha_vencimiento) : null,
                    'estado' => $cxp->estado,
                    'referencia' => $cxp->cfdi ? ($cxp->cfdi->folio ?? '') : ($cxp->compra ? $cxp->compra->numero_compra : ''),
                    'proveedor' => [
                        'nombre_razon_social' => $cxp->proveedor ? $cxp->proveedor->nombre_razon_social : ($cxp->compra && $cxp->compra->proveedor ? $cxp->compra->proveedor->nombre_razon_social : 'Proveedor'),
                        'rfc' => $cxp->proveedor ? $cxp->proveedor->rfc : ($cxp->compra && $cxp->compra->proveedor ? $cxp->compra->proveedor->rfc : ''),
                    ],
                ];
            });

        return Inertia::render('ConciliacionBancaria/Index', [
            'movimientos' => $movimientos,
            'resumen' => $resumen,
            'filtros' => [
                'estado' => $estado,
                'tipo' => $tipo,
                'banco' => $banco,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'bancos' => $bancos,
            'bancos_soportados' => $this->parserService->getBancosSoportados(),
            'cuentasCobrar' => $cuentasCobrar->sortBy('fecha_vencimiento')->values()->all(),
            'cuentasPagar' => $cuentasPagar->sortBy('fecha_vencimiento')->values()->all(),
        ]);
    }

    /**
     * Importar archivo CSV o Excel de estado de cuenta
     */
    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => 'required',
            'archivo.*' => 'file|max:10240',
            'banco' => 'nullable|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        try {
            $archivos = $request->file('archivo');
            if (!is_array($archivos)) $archivos = [$archivos];

            $totalImportados = 0;
            $totalDuplicados = 0;

            foreach ($archivos as $archivo) {
                $nombreArchivo = $archivo->getClientOriginalName();
                $extension = strtolower($archivo->getClientOriginalExtension());
                $bancoSeleccionado = $request->get('banco');
                $cuentaBancariaId = $request->get('cuenta_bancaria_id');

                try {
                    if (in_array($extension, ['xls', 'xlsx'])) {
                        $resultado = $this->parserService->parsearExcel($archivo->path(), $bancoSeleccionado);
                    } elseif ($extension === 'pdf') {
                        $resultado = $this->parserService->parsearPdf($archivo->path(), $bancoSeleccionado);
                    } else {
                        $contenido = file_get_contents($archivo->path());
                        $resultado = $this->parserService->parsear($contenido, $bancoSeleccionado);
                    }
                } catch (\Exception $e) {
                    Log::info("El parseador tradicional falló para {$nombreArchivo}: " . $e->getMessage() . ". Iniciando extracción con IA...");
                    
                    // Extraer texto plano del archivo para enviárselo a Gemini
                    $texto = '';
                    if (in_array($extension, ['xls', 'xlsx'])) {
                        try {
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo->path());
                            $worksheet = $spreadsheet->getActiveSheet();
                            $rows = $worksheet->toArray();
                            foreach ($rows as $row) {
                                $texto .= implode(', ', array_filter(array_map('strval', $row))) . "\n";
                            }
                        } catch (\Exception $ex) {
                            $texto = '';
                        }
                    } elseif ($extension === 'pdf') {
                        $output = [];
                        $resultCode = 0;
                        exec("pdftotext -layout " . escapeshellarg($archivo->path()) . " -", $output, $resultCode);
                        $texto = implode("\n", $output);
                    } else {
                        $texto = file_get_contents($archivo->path());
                    }

                    if (!empty(trim($texto))) {
                        $resultado = $this->parserService->parsearConIa($texto, $bancoSeleccionado);
                    } else {
                        throw $e; // Re-lanzar error original si no hay texto extraíble
                    }
                }

                if (!empty($resultado['movimientos'])) {
                    $importados = 0;
                    $duplicados = 0;

                    \Illuminate\Support\Facades\DB::transaction(function () use ($resultado, $nombreArchivo, $cuentaBancariaId, &$importados, &$duplicados) {
                        foreach ($resultado['movimientos'] as $mov) {
                            $existe = \App\Models\MovimientoBancario::where('fecha', $mov['fecha'])
                                ->where('monto', $mov['monto'])
                                ->where('concepto', $mov['concepto'])
                                ->where('banco', $mov['banco'])
                                ->exists();

                            if ($existe) {
                                $duplicados++;
                                continue;
                            }

                            \App\Models\MovimientoBancario::create([
                                'fecha' => $mov['fecha'],
                                'concepto' => $mov['concepto'],
                                'referencia' => $mov['referencia'] ?? '',
                                'monto' => $mov['monto'],
                                'saldo' => $mov['saldo'] ?? null,
                                'tipo' => $mov['tipo'],
                                'banco' => $mov['banco'],
                                'cuenta_bancaria_id' => $cuentaBancariaId,
                                'estado' => 'pendiente',
                                'archivo_origen' => $nombreArchivo,
                                'usuario_id' => \Illuminate\Support\Facades\Auth::id(),
                            ]);

                            $importados++;
                        }
                    });

                    $totalImportados += $importados;
                    $totalDuplicados += $duplicados;
                }
            }

            if ($totalImportados === 0 && $totalDuplicados === 0) {
                return back()->with('error', 'No se encontraron movimientos válidos.');
            }

            $msg = "Importación completada. {$totalImportados} nuevos.";
            if ($totalDuplicados > 0) $msg .= " ({$totalDuplicados} duplicados omitidos).";

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error importación', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Obtener sugerencias de conciliación para un movimiento
     */
    public function sugerencias(MovimientoBancario $movimiento)
    {
        $sugerencias = $this->conciliacionService->buscarSugerencias($movimiento);

        return response()->json([
            'movimiento' => [
                'id' => $movimiento->id,
                'fecha' => $movimiento->fecha->format('d/m/Y'),
                'concepto' => $movimiento->concepto,
                'monto' => $movimiento->monto,
                'tipo' => $movimiento->tipo,
            ],
            'sugerencias' => $sugerencias,
        ]);
    }

    /**
     * Conciliar un movimiento con una cuenta
     */
    public function conciliar(Request $request)
    {
        $request->validate([
            'movimiento_id' => 'required|exists:movimientos_bancarios,id',
            'tipo_cuenta' => 'required|in:CXC,CXP',
            'cuenta_id' => 'required|integer',
        ]);

        try {
            $movimiento = MovimientoBancario::findOrFail($request->movimiento_id);

            if ($movimiento->estado !== 'pendiente') {
                return back()->with('error', 'Este movimiento ya fue conciliado o ignorado');
            }

            $this->conciliacionService->conciliar(
                $movimiento,
                $request->tipo_cuenta,
                $request->cuenta_id
            );

            Log::info('Movimiento conciliado', [
                'movimiento_id' => $movimiento->id,
                'tipo_cuenta' => $request->tipo_cuenta,
                'cuenta_id' => $request->cuenta_id,
                'usuario_id' => Auth::id(),
            ]);

            return back()->with('success', 'Movimiento conciliado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al conciliar', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al conciliar: ' . $e->getMessage());
        }
    }

    /**
     * Conciliación automática masiva
     */
    public function conciliacionAutomatica(Request $request)
    {
        $request->validate([
            'score_minimo' => 'nullable|integer|min:50|max:100',
        ]);

        try {
            $scoreMinimo = $request->get('score_minimo', 75);
            $movimientos = MovimientoBancario::pendientes()->get();

            $resultado = $this->conciliacionService->conciliacionAutomatica($movimientos, $scoreMinimo);

            $mensaje = "Conciliación automática: {$resultado['conciliados']} conciliados, {$resultado['sin_match']} sin match";
            
            if (!empty($resultado['errores'])) {
                $mensaje .= " ({$resultado['errores'][0]})";
            }

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Ignorar un movimiento
     */
    public function ignorar(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado !== 'pendiente') {
            return back()->with('error', 'Este movimiento ya fue procesado');
        }

        $movimiento->ignorar();

        return back()->with('success', 'Movimiento marcado como ignorado');
    }

    /**
     * Revertir conciliación
     */
    public function revertir(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado !== 'conciliado') {
            return back()->with('error', 'Este movimiento no está conciliado');
        }

        $movimiento->revertirConciliacion();

        Log::info('Conciliación revertida', [
            'movimiento_id' => $movimiento->id,
            'usuario_id' => Auth::id(),
        ]);

        return back()->with('success', 'Conciliación revertida');
    }

    /**
     * Eliminar movimiento
     */
    public function destroy(MovimientoBancario $movimiento)
    {
        if ($movimiento->estado === 'conciliado') {
            return back()->with('error', 'No se puede eliminar un movimiento conciliado. Primero revierta la conciliación.');
        }

        $movimiento->delete();

        return back()->with('success', 'Movimiento eliminado');
    }

    /**
     * Analizar movimientos pendientes con IA (Gemini) y sugerir conciliación side-by-side
     */
    public function analizarConciliacionAi(Request $request, \App\Services\AI\GeminiService $gemini)
    {
        try {
            if (!$gemini->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El servicio de IA (Gemini) no está disponible o no tiene una API key configurada.'
                ], 400);
            }

            $empresaId = auth()->user()->empresa_id ?? 8;

            // 1. Obtener movimientos bancarios pendientes (ej. retiros y depósitos)
            $movimientos = MovimientoBancario::where('estado', 'pendiente')
                ->orderBy('fecha', 'desc')
                ->take(40)
                ->get();

            if ($movimientos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'matches' => [],
                    'message' => 'No hay movimientos bancarios pendientes de conciliación.'
                ]);
            }

            // 2. Obtener facturas/cuentas por cobrar pendientes (CxC)
            $cxc = \App\Models\CuentasPorCobrar::where('empresa_id', $empresaId)
                ->where('estado', '!=', 'pagado')
                ->with(['cliente', 'cfdi'])
                ->orderBy('fecha_vencimiento', 'asc')
                ->take(60)
                ->get();

            // 3. Obtener facturas/cuentas por pagar pendientes (CxP)
            $cxp = \App\Models\CuentasPorPagar::where('empresa_id', $empresaId)
                ->where('estado', '!=', 'pagado')
                ->with(['proveedor', 'compra', 'cfdi'])
                ->orderBy('fecha_vencimiento', 'asc')
                ->take(60)
                ->get();

            // 4. Preparar listado de movimientos
            $movsTxt = $movimientos->map(function ($m) {
                return "ID Movimiento: {$m->id} | Fecha: {$m->fecha->format('Y-m-d')} | Tipo: {$m->tipo} | Monto: \${$m->monto} | Concepto: \"{$m->concepto}\" | Referencia: \"{$m->referencia}\"";
            })->implode("\n");

            // 5. Preparar listado de CxC (Ingresos esperados)
            $cxcTxt = $cxc->map(function ($c) {
                $cliente = $c->cliente ? $c->cliente->nombre_razon_social : 'Público en General';
                $folio = $c->cfdi ? ($c->cfdi->folio ?? '') : ($c->cobrable ? ($c->cobrable->folio ?? '') : '');
                $fechaVenc = $c->fecha_vencimiento ? ($c->fecha_vencimiento instanceof \Carbon\Carbon ? $c->fecha_vencimiento->format('Y-m-d') : $c->fecha_vencimiento) : 'N/A';
                return "ID CxC: {$c->id} | Monto Pendiente: \${$c->monto_pendiente} | Cliente: \"{$cliente}\" | Folio: \"{$folio}\" | Vencimiento: {$fechaVenc}";
            })->implode("\n");

            // 6. Preparar listado de CxP (Egresos esperados)
            $cxpTxt = $cxp->map(function ($c) {
                $prov = $c->proveedor ? $c->proveedor->nombre_razon_social : ($c->compra && $c->compra->proveedor ? $c->compra->proveedor->nombre_razon_social : 'Proveedor');
                $folio = $c->cfdi ? ($c->cfdi->folio ?? '') : ($c->compra ? $c->compra->numero_compra : '');
                $fechaVenc = $c->fecha_vencimiento ? ($c->fecha_vencimiento instanceof \Carbon\Carbon ? $c->fecha_vencimiento->format('Y-m-d') : $c->fecha_vencimiento) : 'N/A';
                return "ID CxP: {$c->id} | Monto Pendiente: \${$c->monto_pendiente} | Proveedor: \"{$prov}\" | Folio: \"{$folio}\" | Vencimiento: {$fechaVenc}";
            })->implode("\n");

            $systemPrompt = "Eres un Auditor Fiscal de alto nivel y experto en conciliación bancaria inteligente en México.
Tu tarea es emparejar de forma inteligente los movimientos bancarios pendientes con las facturas (CxC o CxP) que la empresa tiene pendientes de cobrar o pagar.

REGLAS DE CONCILIACIÓN SEMÁNTICA:
1. Los depósitos bancarios ('deposito') representan entradas de dinero, por lo que SOLO pueden conciliarse con Cuentas por Cobrar (CXC).
2. Los retiros bancarios ('retiro') representan salidas de dinero, por lo que SOLO pueden conciliarse con Cuentas por Pagar (CXP).
3. Busca coincidencias de montos exactos o muy cercanos (ej. un movimiento de $1,166.24 coincide con una CxC de $1,166.24 o $1,166.25).
4. Analiza semánticamente la descripción o concepto del movimiento bancario frente al nombre del cliente/proveedor y folios de facturas para encontrar la mejor coincidencia.
5. Valora la cercanía de las fechas (el movimiento debe ser cercano o posterior a la fecha de emisión de la factura).
6. Asigna un score de coincidencia de 0 a 100, donde 100 es coincidencia absoluta e indudable. Devuelve únicamente sugerencias con score mayor a 55.";

            $userPrompt = "MOVIMIENTOS BANCARIOS PENDIENTES:
{$movsTxt}

CUENTAS POR COBRAR PENDIENTES (Para Depósitos):
{$cxcTxt}

CUENTAS POR PAGAR PENDIENTES (Para Retiros):
{$cxpTxt}

Por favor, analiza todos los movimientos y genera los emparejamientos sugeridos llamando a 'suggest_reconciliation_matches'.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ];

            $tools = [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'suggest_reconciliation_matches',
                        'description' => 'Sugiere emparejamientos de conciliación entre movimientos bancarios y facturas pendientes.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'matches' => [
                                    'type' => 'ARRAY',
                                    'items' => [
                                        'type' => 'OBJECT',
                                        'properties' => [
                                            'movimiento_id' => [
                                                'type' => 'INTEGER',
                                                'description' => 'ID del movimiento bancario pendiente.'
                                            ],
                                            'tipo_cuenta' => [
                                                'type' => 'STRING',
                                                'description' => 'Tipo de la cuenta a conciliar: CXC o CXP.'
                                            ],
                                            'cuenta_id' => [
                                                'type' => 'INTEGER',
                                                'description' => 'ID de la factura en la tabla correspondiente.'
                                            ],
                                            'score' => [
                                                'type' => 'INTEGER',
                                                'description' => 'Porcentaje de confianza del match (0 a 100).'
                                            ],
                                            'razonamiento' => [
                                                'type' => 'STRING',
                                                'description' => 'Explicación del porqué coincide.'
                                            ]
                                        ],
                                        'required' => ['movimiento_id', 'tipo_cuenta', 'cuenta_id', 'score', 'razonamiento']
                                    ]
                                ]
                            ],
                            'required' => ['matches']
                        ]
                    ]
                ]
            ];

            $response = $gemini->chat($messages, $tools);

            if ($response['success']) {
                $toolCall = $response['message']['tool_calls'][0] ?? null;
                if ($toolCall) {
                    $args = is_array($toolCall['function']['arguments'])
                        ? $toolCall['function']['arguments']
                        : json_decode($toolCall['function']['arguments'], true);

                    $matches = $args['matches'] ?? [];

                    // Completar datos de los matches para el frontend
                    $matchesCompletos = [];
                    foreach ($matches as $match) {
                        $mov = $movimientos->firstWhere('id', $match['movimiento_id']);
                        if (!$mov) continue;

                        $facturaInfo = null;
                        if ($match['tipo_cuenta'] === 'CXC') {
                            $fact = $cxc->firstWhere('id', $match['cuenta_id']);
                            if ($fact) {
                                $facturaInfo = [
                                    'id' => $fact->id,
                                    'monto_pendiente' => $fact->monto_pendiente,
                                    'referencia' => $fact->cfdi ? ($fact->cfdi->folio ?? '') : ($fact->cobrable ? ($fact->cobrable->folio ?? '') : ''),
                                    'nombre_auxiliar' => $fact->cliente ? $fact->cliente->nombre_razon_social : 'Cliente',
                                    'rfc' => $fact->cliente ? $fact->cliente->rfc : '',
                                    'fecha_vencimiento' => $fact->fecha_vencimiento,
                                ];
                            }
                        } else {
                            $fact = $cxp->firstWhere('id', $match['cuenta_id']);
                            if ($fact) {
                                $facturaInfo = [
                                    'id' => $fact->id,
                                    'monto_pendiente' => $fact->monto_pendiente,
                                    'referencia' => $fact->cfdi ? ($fact->cfdi->folio ?? '') : ($fact->compra ? $fact->compra->numero_compra : ''),
                                    'nombre_auxiliar' => $fact->proveedor ? $fact->proveedor->nombre_razon_social : ($fact->compra && $fact->compra->proveedor ? $fact->compra->proveedor->nombre_razon_social : 'Proveedor'),
                                    'rfc' => $fact->proveedor ? $fact->proveedor->rfc : ($fact->compra && $fact->compra->proveedor ? $fact->compra->proveedor->rfc : ''),
                                    'fecha_vencimiento' => $fact->fecha_vencimiento,
                                ];
                            }
                        }

                        if ($mov && $facturaInfo) {
                            $matchesCompletos[] = [
                                'movimiento' => [
                                    'id' => $mov->id,
                                    'fecha' => $mov->fecha->format('Y-m-d'),
                                    'concepto' => $mov->concepto,
                                    'monto' => $mov->monto,
                                    'tipo' => $mov->tipo,
                                    'banco' => $mov->banco,
                                    'referencia' => $mov->referencia,
                                ],
                                'tipo_cuenta' => $match['tipo_cuenta'],
                                'cuenta_id' => $match['cuenta_id'],
                                'factura' => $facturaInfo,
                                'score' => $match['score'],
                                'razonamiento' => $match['razonamiento'],
                            ];
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'matches' => $matchesCompletos,
                        'message' => 'Análisis de conciliación completado con éxito por Gemini.'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron generar sugerencias de conciliación inteligentes.'
            ], 500);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error Conciliacion AI', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

