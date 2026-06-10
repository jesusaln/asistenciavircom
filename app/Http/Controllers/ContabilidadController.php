<?php

namespace App\Http\Controllers;

use App\Models\Contab\CuentaContable;
use App\Models\Contab\PolizaContable;
use App\Models\Contab\AsientoContable;
use App\Models\Cfdi;
use App\Services\BankStatementParserService;
use App\Services\Contab\ContabilidadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ContabilidadController extends Controller
{
    public function __construct(
        protected ContabilidadService $service,
        protected BankStatementParserService $parserService
    ) {}

    /**
     * Listado de pólizas (Vista Web)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $sortField = $request->input('sort', 'fecha');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['fecha', 'tipo', 'numero', 'concepto', 'total'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'fecha';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $query = PolizaContable::where('empresa_id', $user->empresa_id)
            ->with(['asientos.cuenta'])
            ->orderBy($sortField, $sortDir);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('concepto', 'ilike', "%{$search}%")
                  ->orWhere('numero', 'ilike', "%{$search}%")
                  ->orWhere('cfdi_uuid', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        $polizas = $query->paginate(30)->withQueryString();

        // Optimización N+1: Cargar todos los UUIDs necesarios de una vez
        $allUuids = [];
        foreach ($polizas as $p) {
            $uuids = $p->cfdi_uuids ?? ($p->cfdi_uuid ? [$p->cfdi_uuid] : []);
            foreach ($uuids as $u) if ($u) $allUuids[] = strtolower($u);
        }
        $allUuids = array_unique($allUuids);
        $cfdisMap = Cfdi::whereIn('uuid', $allUuids)->get()->keyBy(fn($c) => strtolower($c->uuid));

        $polizas->getCollection()->transform(function ($poliza) use ($cfdisMap) {
            $totalDebe = round((float) $poliza->asientos->sum('debe'), 2);
            $totalHaber = round((float) $poliza->asientos->sum('haber'), 2);
            $poliza->descuadrada = $totalDebe !== $totalHaber;
            $poliza->diferencia = abs($totalDebe - $totalHaber);

            $poliza->conceptos = [];
            $uuids = $poliza->cfdi_uuids ?? ($poliza->cfdi_uuid ? [$poliza->cfdi_uuid] : []);
            $lowerUuids = [];
            
            foreach ($uuids as $uuid) {
                if (!$uuid) continue;
                $lower = strtolower($uuid);
                $lowerUuids[] = $lower;
                $cfdi = $cfdisMap->get($lower);
                if ($cfdi && $cfdi->conceptos) {
                    $conceptosArray = $cfdi->conceptos instanceof \Illuminate\Support\Collection ? $cfdi->conceptos->toArray() : (array) $cfdi->conceptos;
                    $employeeName = $cfdi->nombre_receptor ?: ($cfdi->datos_adicionales['receptor']['Nombre'] ?? '');
                    foreach ($conceptosArray as &$conc) {
                        $conc['empleado'] = $employeeName;
                    }
                    $poliza->conceptos = array_merge($poliza->conceptos, $conceptosArray);
                }
            }
            $poliza->setAttribute('multi_uuids', $lowerUuids);
            return $poliza;
        });

        return Inertia::render('Contabilidad/Index', [
            'polizas' => $polizas,
            'filters' => $request->only(['search', 'tipo', 'fecha_inicio', 'fecha_fin', 'sort', 'sort_dir']),
        ])->with([
            'cuentasBancarias' => Inertia::defer(fn () => \App\Models\CuentaBancaria::where('empresa_id', $user->empresa_id)
                ->where('activa', true)
                ->get()),
            'cuentasContables' => Inertia::defer(fn () => CuentaContable::where('empresa_id', $user->empresa_id)
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'nombre', 'naturaleza'])),
            'stats' => Inertia::defer(fn () => [
                'total' => PolizaContable::where('empresa_id', $user->empresa_id)->count(),
                'diario' => PolizaContable::where('empresa_id', $user->empresa_id)->where('tipo', 'diario')->count(),
                'ingreso' => PolizaContable::where('empresa_id', $user->empresa_id)->where('tipo', 'ingreso')->count(),
                'egreso' => PolizaContable::where('empresa_id', $user->empresa_id)->where('tipo', 'egreso')->count(),
            ]),
        ]);
    }

    /**
     * Catálogo de cuentas (Vista Web)
     */
    public function catalog(Request $request)
    {
        $user = $request->user();
        $cuentas = CuentaContable::where('empresa_id', $user->empresa_id)
            ->with('padre')
            ->withSum(['asientos as total_debe' => function($query) {
                $query->whereHas('poliza', function($q) {
                    $q->where('estado', '!=', 'anulada');
                });
            }], 'debe')
            ->withSum(['asientos as total_haber' => function($query) {
                $query->whereHas('poliza', function($q) {
                    $q->where('estado', '!=', 'anulada');
                });
            }], 'haber')
            ->orderBy('codigo')
            ->get();

        // Inicializar debe/haber con los asientos directos
        foreach ($cuentas as $cta) {
            $cta->debe = (float) ($cta->total_debe ?? 0);
            $cta->haber = (float) ($cta->total_haber ?? 0);
        }

        // Agregación recursiva (de abajo hacia arriba por nivel)
        $cuentasPorNivel = $cuentas->sortByDesc('nivel');
        foreach ($cuentasPorNivel as $cta) {
            if ($cta->padre_id) {
                $padre = $cuentas->firstWhere('id', $cta->padre_id);
                if ($padre) {
                    $padre->debe += $cta->debe;
                    $padre->haber += $cta->haber;
                }
            }
        }

        // Calcular saldo final según naturaleza
        foreach ($cuentas as $cta) {
            $cta->saldo = ($cta->naturaleza === 'deudora') ? ($cta->debe - $cta->haber) : ($cta->haber - $cta->debe);
        }

        return Inertia::render('Contabilidad/Catalog', [
            'catalog' => $cuentas,
        ]);
    }

    /**
     * Catálogo de cuentas PDF
     */
    public function catalogPdf(Request $request)
    {
        $user = $request->user();
        $cuentas = CuentaContable::where('empresa_id', $user->empresa_id)
            ->with('padre')
            ->withSum(['asientos as total_debe' => function($query) {
                $query->whereHas('poliza', function($q) {
                    $q->where('estado', '!=', 'anulada');
                });
            }], 'debe')
            ->withSum(['asientos as total_haber' => function($query) {
                $query->whereHas('poliza', function($q) {
                    $q->where('estado', '!=', 'anulada');
                });
            }], 'haber')
            ->orderBy('codigo')
            ->get();

        foreach ($cuentas as $cta) {
            $cta->debe = (float) ($cta->total_debe ?? 0);
            $cta->haber = (float) ($cta->total_haber ?? 0);
        }

        $cuentasPorNivel = $cuentas->sortByDesc('nivel');
        foreach ($cuentasPorNivel as $cta) {
            if ($cta->padre_id) {
                $padre = $cuentas->firstWhere('id', $cta->padre_id);
                if ($padre) {
                    $padre->debe += $cta->debe;
                    $padre->haber += $cta->haber;
                }
            }
        }

        foreach ($cuentas as $cta) {
            $cta->saldo = ($cta->naturaleza === 'deudora') ? ($cta->debe - $cta->haber) : ($cta->haber - $cta->debe);
        }

        $empresa = \App\Models\EmpresaConfiguracion::getConfig();

        $pdf = Pdf::loadView('pdf.contab.catalogo', [
            'catalog' => $cuentas,
            'empresa' => $empresa,
            'fecha' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Catalogo_Cuentas_" . now()->format('Ymd') . ".pdf");
    }

    public function storeCuenta(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'codigo' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'padre_id' => 'nullable|exists:contab_cuentas,id',
            'es_detalle' => 'required|boolean',
            'sat_codigo' => 'nullable|string|max:50',
            'tipo' => 'nullable|string|in:activo,pasivo,capital,ingreso,egreso',
            'naturaleza' => 'nullable|string|in:deudora,acreedora',
        ]);

        $exists = CuentaContable::where('empresa_id', $user->empresa_id)
            ->where('codigo', $validated['codigo'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['codigo' => 'El código de cuenta ya existe en esta empresa.']);
        }

        $cuenta = new CuentaContable();
        $cuenta->empresa_id = $user->empresa_id;
        $cuenta->codigo = $validated['codigo'];
        $cuenta->nombre = $validated['nombre'];
        $cuenta->padre_id = $validated['padre_id'];
        $cuenta->es_detalle = $validated['es_detalle'];
        $cuenta->sat_codigo = $validated['sat_codigo'];

        if ($validated['padre_id']) {
            $padre = CuentaContable::findOrFail($validated['padre_id']);
            $cuenta->nivel = $padre->nivel + 1;
            $cuenta->tipo = $padre->tipo;
            $cuenta->naturaleza = $padre->naturaleza;
        } else {
            $cuenta->nivel = 1;
            $cuenta->tipo = $validated['tipo'] ?? 'activo';
            $cuenta->naturaleza = $validated['naturaleza'] ?? 'deudora';
        }

        $cuenta->save();
        return redirect()->route('contabilidad.catalog')->with('message', 'Cuenta creada exitosamente.');
    }

    public function destroyCuenta(CuentaContable $cuenta)
    {
        if ($cuenta->asientos()->exists()) {
            return back()->withErrors(['cuenta' => 'No se puede eliminar la cuenta porque tiene movimientos contables registrados.']);
        }

        if ($cuenta->hijos()->exists()) {
            return back()->withErrors(['cuenta' => 'No se puede eliminar la cuenta porque tiene subcuentas asociadas.']);
        }

        $cuenta->delete();
        return redirect()->route('contabilidad.catalog')->with('message', 'Cuenta eliminada exitosamente.');
    }

    /**
     * Previsualizar póliza desde XML (sin guardar)
     */
    public function previewXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file',
        ]);

        try {
            $user = $request->user();
            $file = $request->file('xml');
            $xmlContent = file_get_contents($file->getRealPath());

            // Usamos un método del service que solo parsee y devuelva el array de asientos
            $preview = $this->service->previsualizarPolizaDesdeXml(
                $xmlContent,
                $user->empresa_id,
                $user->id
            );

            return response()->json($preview);

        } catch (\Exception $e) {
            Log::error("Error en previewXml: " . $e->getMessage());
            return response()->json(['error' => "Error al procesar el XML: " . $e->getMessage()], 422);
        }
    }

    /**
     * Procesar y guardar XML desde la web
     */
    public function uploadXml(Request $request)
    {
        $request->validate([
            'xml_content' => 'required|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ]);

        try {
            $user = $request->user();
            $xmlContent = $request->xml_content;
            $bancoId = $request->cuenta_bancaria_id;

            $poliza = $this->service->generarPolizaDesdeXml(
                $xmlContent,
                $user->empresa_id,
                $user->id,
                $bancoId
            );

            return back()->with('success', "Póliza {$poliza->numero} generada correctamente.");

        } catch (\Exception $e) {
            Log::error("Error al cargar XML contable (Web): " . $e->getMessage());
            return back()->withErrors(['xml' => $e->getMessage()]);
        }
    }

    /**
     * Adjuntar soportes (PDF/Imagen) a una póliza
     */
    public function uploadSoportes(Request $request, PolizaContable $poliza)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
        ]);

        $soportes = $poliza->soportes ?? [];
        
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('contab/soportes', 'public');
                $soportes[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'date' => now()->format('Y-m-d H:i'),
                ];
            }
        }

        $poliza->update(['soportes' => $soportes]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'poliza' => $poliza->fresh()]);
        }

        return back()->with('success', "Documentos adjuntados correctamente.");
    }

    /**
     * Eliminar un soporte de una póliza
     */
    public function destroySoporte(PolizaContable $poliza, $index)
    {
        $soportes = $poliza->soportes ?? [];
        
        if (isset($soportes[$index])) {
            $path = $soportes[$index]['path'];
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            array_splice($soportes, $index, 1);
            $poliza->update(['soportes' => $soportes]);

            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'poliza' => $poliza->fresh()]);
            }

            return back()->with('success', "Documento eliminado correctamente.");
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => false, 'message' => "No se encontró el documento."], 404);
        }

        return back()->withErrors(['error' => "No se encontró el documento."]);
    }

    /**
     * Ver detalle de una póliza
     */
    public function show(PolizaContable $poliza)
    {
        $this->authorize('view', $poliza);
        $poliza->load(['asientos.cuenta', 'creador', 'movimientoBancario.cuentaBancaria']);

        // Buscar documentos relacionados
        $uuids = $poliza->cfdi_uuids ?? ($poliza->cfdi_uuid ? [$poliza->cfdi_uuid] : []);
        $documentos = [];
        
        if (!empty($uuids)) {
            $cfdis = Cfdi::whereIn('uuid', $uuids)->get();
            foreach ($cfdis as $cfdi) {
                $documentos[] = [
                    'uuid' => $cfdi->uuid,
                    'serie' => $cfdi->serie,
                    'folio' => $cfdi->folio,
                    'emisor' => $cfdi->emisor_nombre,
                    'receptor' => $cfdi->receptor_nombre,
                    'total' => (float)$cfdi->total,
                    'subtotal' => (float)$cfdi->subtotal,
                    'iva' => (float)$cfdi->iva,
                    'tipo' => $cfdi->tipo_comprobante,
                    'relacion' => $cfdi->compra ? 'Compra' : ($cfdi->venta ? 'Venta' : 'CFDI Externo')
                ];
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'poliza' => $poliza,
                'documentos' => $documentos,
                'documento' => $documentos[0] ?? null // Mantener compatibilidad con UI vieja
            ]);
        }

        return Inertia::render('Contabilidad/Show', [
            'poliza' => $poliza,
            'documentos' => $documentos,
            'documento' => $documentos[0] ?? null
        ]);
    }

    /**
     * Actualizar póliza
     */
    public function update(Request $request, PolizaContable $poliza)
    {
        $this->authorize('update', $poliza);
        
        $validated = $request->validate([
            'concepto' => 'required|string|max:500',
            'fecha' => 'required|date',
            'estado' => 'required|in:borrador,asentada,anulada',
        ]);

        $poliza->update($validated);

        $poliza->load('asientos');
        $totalDebe = round((float) $poliza->asientos->sum('debe'), 2);
        $totalHaber = round((float) $poliza->asientos->sum('haber'), 2);
        $warning = null;
        if ($totalDebe !== $totalHaber) {
            $diff = abs($totalDebe - $totalHaber);
            $warning = "La póliza está descuadrada: Debe (\${$totalDebe}) ≠ Haber (\${$totalHaber}), diferencia de \${$diff}.";
        }

        return back()->with('success', "Póliza actualizada correctamente." . ($warning ? " Aviso: {$warning}" : ''));
    }

    /**
     * Eliminar póliza
     */
    public function destroy(PolizaContable $poliza)
    {
        $this->authorize('delete', $poliza);
        
        $poliza->delete();

        return back()->with('success', "Póliza eliminada correctamente.");
    }

    /**
     * Crear póliza manual
     */
    public function store(Request $request)
    {
        if (is_string($request->input('asientos'))) {
            $request->merge([
                'asientos' => json_decode($request->input('asientos'), true)
            ]);
        }

        $request->validate([
            'fecha' => 'required|date',
            'tipo' => 'required|in:diario,ingreso,egreso',
            'concepto' => 'required|string|max:500',
            'asientos' => 'required|array|min:2',
            'asientos.*.cuenta_id' => 'required|exists:contab_cuentas,id',
            'asientos.*.debe' => 'required|numeric|min:0',
            'asientos.*.haber' => 'required|numeric|min:0',
            'files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
        ]);

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            $totalDebe = collect($request->asientos)->sum('debe');

            $soportes = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('contab/soportes', 'public');
                    $soportes[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'date' => now()->format('Y-m-d H:i'),
                    ];
                }
            }

            $poliza = PolizaContable::create([
                'empresa_id' => $user->empresa_id,
                'created_by' => $user->id,
                'tipo' => $request->tipo,
                'fecha' => $request->fecha,
                'numero' => $this->service->generarSiguienteNumero($user->empresa_id, $request->tipo, date('Y', strtotime($request->fecha))),
                'concepto' => $request->concepto,
                'total' => $totalDebe,
                'estado' => 'asentada',
                'soportes' => $soportes,
            ]);

            foreach ($request->asientos as $a) {
                AsientoContable::create([
                    'poliza_id' => $poliza->id,
                    'cuenta_id' => $a['cuenta_id'],
                    'debe' => $a['debe'],
                    'haber' => $a['haber'],
                ]);
            }

            return back()->with('success', "Póliza {$poliza->numero} creada correctamente.");
        });
    }

    /**
     * Reporte: Balanza de Comprobación
     */
    public function balanza(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        
        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));

        $data = $this->service->obtenerBalanza($user->empresa_id, $inicio, $fin);

        // Pre-calcular totales del calce desde Cuentas de Nivel 1 (garantizado cuadrado por partida doble)
        $nivel1 = collect($data)->where('nivel', 1);
        $totales = [
            'inicial_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_inicial'),
            'inicial_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_inicial'),
            'cargos' => $nivel1->sum('cargos'),
            'abonos' => $nivel1->sum('abonos'),
            'final_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_final'),
            'final_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_final'),
        ];

        return Inertia::render('Contabilidad/Reportes/Balanza', [
            'reportData' => $data,
            'totales' => $totales,
            'filters' => ['mes' => $mes, 'anio' => $anio]
        ]);
    }

    /**
     * Reporte: Balanza de Comprobación PDF
     */
    public function balanzaPdf(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        
        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));

        $data = $this->service->obtenerBalanza($user->empresa_id, $inicio, $fin);
        
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        $empresa = \App\Models\EmpresaConfiguracion::getConfig();

        $nivel1 = collect($data)->where('nivel', 1);
        $totales = [
            'inicial_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_inicial'),
            'inicial_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_inicial'),
            'cargos' => $nivel1->sum('cargos'),
            'abonos' => $nivel1->sum('abonos'),
            'final_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_final'),
            'final_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_final'),
        ];

        $pdf = Pdf::loadView('pdf.balanza_reporte', [
            'reportData' => $data,
            'totales' => $totales,
            'mes_nombre' => $meses[$mes] ?? 'Desconocido',
            'anio' => $anio,
            'empresa' => $empresa,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Balanza_{$mes}_{$anio}.pdf");
    }

    /**
     * Reporte: Estado de Resultados
     */
    public function estadoResultados(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        
        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));

        $data = $this->service->obtenerEstadoResultados($user->empresa_id, $inicio, $fin);

        return Inertia::render('Contabilidad/Reportes/EstadoResultados', [
            'reportData' => $data,
            'filters' => ['mes' => $mes, 'anio' => $anio]
        ]);
    }

    public function estadoResultadosPdf(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        
        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));

        $data = $this->service->obtenerEstadoResultados($user->empresa_id, $inicio, $fin);
        
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        $empresa = \App\Models\EmpresaConfiguracion::getConfig();

        $pdf = Pdf::loadView('pdf.estado_resultados_reporte', [
            'reportData' => $data,
            'mes_nombre' => $meses[$mes] ?? 'Desconocido',
            'anio' => $anio,
            'empresa' => $empresa,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Estado_Resultados_{$mes}_{$anio}.pdf");
    }

    /**
     * Reporte: Pago de IVA (Mensual)
     */
    public function ivaMensual(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        
        if ($mes === 'anual') {
            $inicio = "{$anio}-01-01";
            $fin = "{$anio}-12-31";
        } else {
            $inicio = "{$anio}-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
            $fin = date('Y-m-t', strtotime($inicio));
        }

        // Reporte 1: Contabilidad (Pólizas)
        $dataContable = $this->service->obtenerReporteIva($user->empresa_id, $inicio, $fin);

        // Reporte 2: XML (Fiscal SAT)
        $dataXml = $this->service->obtenerReporteIvaXml($user->empresa_id, $inicio, $fin);

        return Inertia::render('Contabilidad/Reportes/IvaMensual', [
            'reportData' => $dataContable,
            'xmlData' => $dataXml,
            'filters' => ['mes' => $mes, 'anio' => $anio]
        ]);
    }

    /**
     * Reporte: Balance General
     */
    public function balanceGeneral(Request $request)
    {
        $user = $request->user();
        $fecha = $request->input('fecha', now()->format('Y-m-d'));

        $data = $this->service->obtenerBalanceGeneral($user->empresa_id, $fecha);

        return Inertia::render('Contabilidad/Reportes/BalanceGeneral', [
            'reportData' => $data,
            'filters' => ['fecha' => $fecha]
        ]);
    }

    /**
     * Reporte: Flujo de Efectivo
     */
    public function flujoEfectivo(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));

        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));

        $data = $this->service->obtenerFlujoEfectivo($user->empresa_id, $inicio, $fin);

        return Inertia::render('Contabilidad/Reportes/FlujoEfectivo', [
            'reportData' => $data,
            'filters' => ['mes' => $mes, 'anio' => $anio]
        ]);
    }

    public function detalleCuenta($cuentaId)
    {
        $asientos = AsientoContable::where('cuenta_id', $cuentaId)
            ->with(['poliza' => function($q) {
                $q->select('id', 'numero', 'fecha', 'concepto', 'tipo', 'total', 'cfdi_uuid', 'cfdi_uuids');
            }])
            ->whereHas('poliza', fn($q) => $q->where('estado', '!=', 'anulada'))
            ->orderBy('id', 'desc')
            ->limit(300) // Increased limit for history
            ->get()
            ->map(function($a) {
                $cfdis = [];
                if ($a->poliza) {
                    $uuids = $a->poliza->cfdi_uuids ?: ($a->poliza->cfdi_uuid ? [$a->poliza->cfdi_uuid] : []);
                    if (!empty($uuids)) {
                        $cfdis = Cfdi::whereIn('uuid', $uuids)
                            ->get(['uuid', 'serie', 'folio', 'nombre_emisor', 'nombre_receptor', 'total', 'tipo_comprobante'])
                            ->map(fn($c) => [
                                'uuid' => $c->uuid,
                                'folio_completo' => ($c->serie ? $c->serie . '-' : '') . ($c->folio ?: substr($c->uuid, 0, 8)),
                                'emisor' => $c->nombre_emisor,
                                'receptor' => $c->nombre_receptor,
                                'total' => (float)$c->total,
                                'tipo' => $c->tipo_comprobante
                            ]);
                    }
                }

                return [
                    'id' => $a->id,
                    'poliza_id' => $a->poliza_id,
                    'poliza_numero' => $a->poliza?->numero,
                    'poliza_tipo' => $a->poliza?->tipo,
                    'fecha' => $a->poliza?->fecha?->format('Y-m-d'),
                    'concepto' => $a->poliza?->concepto,
                    'debe' => (float) $a->debe,
                    'haber' => (float) $a->haber,
                    'referencia' => $a->referencia,
                    'cfdis' => $cfdis
                ];
            });

        $cuenta = CuentaContable::find($cuentaId);

        return response()->json([
            'success' => true,
            'asientos' => $asientos,
            'cuenta' => $cuenta ? ['codigo' => $cuenta->codigo, 'nombre' => $cuenta->nombre] : null,
        ]);
    }

    public function cfdisPendientes(Request $request)
    {
        $user = $request->user();
        $empresaRfc = \App\Models\EmpresaConfiguracion::getConfig()->rfc;
        $mes = $request->input('mes');
        $anio = $request->input('anio');

        $query = Cfdi::with(['conceptos'])
            ->where('empresa_id', $user->empresa_id)
            ->where(function ($q) use ($empresaRfc) {
                $q->where('rfc_emisor', $empresaRfc)
                  ->orWhere('rfc_receptor', $empresaRfc);
            });

        if ($mes) {
            $query->whereMonth('fecha_emision', $mes);
        }
        if ($anio) {
            $query->whereYear('fecha_emision', $anio);
        } else {
            // Si no hay año, mostrar últimos 100 por defecto
            if (!$mes) $query->take(100);
        }

        $cfdisData = $query->orderBy('fecha_emision', 'desc')
            ->get();

        $uuidsEncontrados = $cfdisData->pluck('uuid')->toArray();
        
        // Buscar de una vez cuáles de estos UUIDs ya están en pólizas (como uuid principal)
        $integratedDirectos = PolizaContable::whereIn('cfdi_uuid', $uuidsEncontrados)
            ->get(['id', 'numero', 'cfdi_uuid'])
            ->keyBy(fn($i) => (string)$i->cfdi_uuid);

        // Para los que no están directo, buscar en xml_content (esto sigue siendo un poco lento pero lo hacemos una sola vez para el grupo)
        // Solo buscamos los que no se encontraron directo para ahorrar tiempo
        $posiblesPendientes = array_diff($uuidsEncontrados, $integratedDirectos->keys()->toArray());
        $integratedMulti = [];
        
        if (!empty($posiblesPendientes)) {
            // Buscamos pólizas que contengan alguno de estos UUIDs en su xml_content
            // Limitamos la búsqueda a pólizas recientes para performance si es necesario, 
            // pero aquí buscaremos en todas las que tengan contenido JSON de multi-CFDI
            $polizasMulti = PolizaContable::where('xml_content', 'like', '{"uuids":%')
                ->where(function($q) use ($posiblesPendientes) {
                    foreach (array_chunk($posiblesPendientes, 50) as $chunk) {
                        $q->orWhere(function($sq) use ($chunk) {
                            foreach ($chunk as $uuid) {
                                $sq->orWhere('xml_content', 'like', '%' . $uuid . '%');
                            }
                        });
                    }
                })
                ->get(['id', 'numero', 'xml_content']);

            foreach ($polizasMulti as $pm) {
                foreach ($posiblesPendientes as $uuid) {
                    if (str_contains($pm->xml_content, $uuid)) {
                        $integratedMulti[$uuid] = $pm;
                    }
                }
            }
        }

        $cfdis = $cfdisData->map(function ($cfdi) use ($empresaRfc, $integratedDirectos, $integratedMulti) {
                $poliza = $integratedDirectos[(string)$cfdi->uuid] ?? $integratedMulti[(string)$cfdi->uuid] ?? null;
                
                // Si se encontró solo en xml_content (no como uuid directo) y la póliza es un pago,
                // y el CFDI es una factura (tipo I/E), considerar como NO integrado para permitir integrar por separado
                $integrada = !is_null($poliza);
                if ($poliza && !isset($integratedDirectos[(string)$cfdi->uuid])) {
                    $esPago = str_contains(strtoupper($poliza->concepto ?? ''), 'PAGO') || str_contains($poliza->concepto ?? '', 'Pago');
                    if ($esPago && in_array($cfdi->tipo_comprobante, ['I', 'E', 'N'])) {
                        $integrada = false;
                    }
                }
                $polizaId = $poliza ? $poliza->id : null;
                $polizaNumero = $poliza ? $poliza->numero : null;
                $direccion = $cfdi->rfc_emisor === $empresaRfc ? 'emitido' : 'recibido';
                
                $total = (float) $cfdi->total;
                $pagoMonto = 0;
                $descripcionPago = null;

                // Si es un complemento de pago, el total suele ser 0, buscamos el monto en complementos
                if ($cfdi->tipo_comprobante === 'P' && !empty($cfdi->complementos['pagos'])) {
                    foreach ($cfdi->complementos['pagos'] as $pago) {
                        $pagoMonto += (float) ($pago['monto'] ?? 0);
                        if (!empty($pago['doctos_relacionados'])) {
                            $docs = array_map(fn($d) => ($d['serie'] ?? '') . ($d['folio'] ?? ''), $pago['doctos_relacionados']);
                            $descripcionPago = "Pago a: " . implode(', ', array_unique(array_filter($docs)));
                        }
                    }
                    if ($pagoMonto > 0) $total = $pagoMonto;
                }

                $periodo = null;
                $fechaInicialPagoRaw = null;
                if (!empty($cfdi->complementos['nomina'])) {
                    $nom = $cfdi->complementos['nomina'];
                    $fechaInicialPagoRaw = $nom['fecha_inicial_pago'] ?? null;
                    $start = !empty($nom['fecha_inicial_pago']) ? \Carbon\Carbon::parse($nom['fecha_inicial_pago'])->format('d/m') : '';
                    $end = !empty($nom['fecha_final_pago']) ? \Carbon\Carbon::parse($nom['fecha_final_pago'])->format('d/m/Y') : '';
                    if ($start && $end) $periodo = "{$start} al {$end}";
                }

                return [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'folio' => $cfdi->folio ?: substr($cfdi->uuid, 0, 8),
                    'metodo_pago' => $cfdi->metodo_pago,
                    'fecha' => $cfdi->fecha_emision ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y') : null,
                    'fecha_raw' => $cfdi->fecha_emision,
                    'periodo' => $periodo,
                    'fecha_inicial_pago_raw' => $fechaInicialPagoRaw,
                    'emisor' => $cfdi->nombre_emisor,
                    'rfc_emisor' => $cfdi->rfc_emisor,
                    'receptor' => $cfdi->nombre_receptor,
                    'total' => $total,
                    'direccion' => $direccion,
                    'tipo' => $cfdi->tipo_comprobante,
                    'tipo_nombre' => $cfdi->tipo_comprobante_nombre,
                    'integrada' => $integrada,
                    'poliza_id' => $polizaId,
                    'poliza_numero' => $polizaNumero,
                    'descripcion_pago' => $descripcionPago,
                    'estado_sistema' => $cfdi->estado_sistema,
                    'serie' => $cfdi->serie,
                ];
            });

        return response()->json(['success' => true, 'cfdis' => $cfdis]);
    }

    public function rayosX(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        $inicio = "{$anio}-{$mes}-01";
        $fin = date('Y-m-t', strtotime($inicio));
        $data = $this->service->obtenerDetalleConciliacionIva($user->empresa_id, $inicio, $fin);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function auditRayosXAi(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        if ($mes === 'anual') {
            $inicio = "{$anio}-01-01";
            $fin = "{$anio}-12-31";
        } else {
            $inicio = "{$anio}-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
            $fin = date('Y-m-t', strtotime($inicio));
        }

        $breakdown = $this->service->obtenerDetalleConciliacionIva($user->empresa_id, $inicio, $fin);
        $dataContable = $this->service->obtenerReporteIva($user->empresa_id, $inicio, $fin);
        $dataXml = $this->service->obtenerReporteIvaXml($user->empresa_id, $inicio, $fin);

        $resumen = [
            'periodo' => "{$mes}/{$anio}",
            'polizas' => [
                'iva_trasladado' => $dataContable['trasladado'] ?? 0,
                'iva_acreditable' => $dataContable['acreditable'] ?? 0,
                'iva_devoluciones' => $dataContable['iva_devoluciones_gastos'] ?? 0,
                'ingresos_brutos' => $dataContable['ingresos_brutos'] ?? 0,
                'isr_retenido' => $dataContable['isr_retenido_clientes'] ?? 0,
                'isr_resico_pagar' => $dataContable['isr_neto_pagar'] ?? 0,
            ],
            'xml_sat' => [
                'iva_trasladado' => $dataXml['trasladado'] ?? 0,
                'iva_acreditable' => $dataXml['acreditable'] ?? 0,
                'iva_devoluciones' => $dataXml['iva_devoluciones_gastos'] ?? 0,
                'ingresos_brutos' => $dataXml['ingresos_brutos'] ?? 0,
                'isr_retenido' => $dataXml['isr_retenido_clientes'] ?? 0,
                'isr_resico_pagar' => $dataXml['isr_neto_pagar'] ?? 0,
            ]
        ];

        $aiService = app(\App\Services\Contab\AICategorizationService::class);
        $result = $aiService->auditRayosX($breakdown, $resumen);

        return response()->json($result);
    }

    public function previewMultiCfdi(Request $request)
    {
        $request->validate(['uuids' => 'required|array|min:1', 'uuids.*' => 'string']);
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $fileService = app(\App\Services\Cfdi\CfdiFileService::class);
        $xmlContents = [];
        $items = [];
        $totalSum = 0;

        foreach ($request->uuids as $uuid) {
            $cfdi = Cfdi::where('uuid', $uuid)->firstOrFail();
            $prevPoliza = PolizaContable::where('cfdi_uuid', $uuid)
                ->orWhere('xml_content', 'ilike', '%' . $uuid . '%')
                ->first();
            $esSoloPago = $prevPoliza && $prevPoliza->cfdi_uuid !== $uuid
                && str_contains(strtoupper($prevPoliza->concepto ?? ''), 'PAGO')
                && in_array($cfdi->tipo_comprobante, ['I', 'E', 'N']);
            if ($prevPoliza && !$esSoloPago) {
                $label = $prevPoliza->numero ? " #{$prevPoliza->numero}" : '';
                return response()->json(['success' => false, 'message' => "El CFDI {$uuid} ya fue integrado en la póliza{$label}."], 422);
            }
            $xml = $fileService->getXmlContent($cfdi);
            if (!$xml) {
                return response()->json(['success' => false, 'message' => "No se encontró el XML del CFDI {$uuid}."], 404);
            }
            $xmlContents[] = $xml;
            $items[] = ['uuid' => $uuid, 'folio' => $cfdi->folio ?: substr($cfdi->uuid, 0, 8), 'total' => (float) $cfdi->total, 'emisor' => $cfdi->nombre_emisor, 'receptor' => $cfdi->nombre_receptor];
            $totalSum += (float) $cfdi->total;
        }

        try {
            $preview = $this->service->previsualizarPolizaMultiCfdi($xmlContents, $empresaId, $user->id);
            return response()->json(['success' => true, 'preview' => $preview, 'items' => $items, 'total' => $totalSum]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function integrarMultiCfdi(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array|min:1',
            'uuids.*' => 'string',
            'fecha' => 'nullable|date',
        ]);

        $user = $request->user();
        $empresaId = $user->empresa_id;
        $fecha = $request->fecha;
        $xmlContents = [];
        $fileService = app(\App\Services\Cfdi\CfdiFileService::class);

        foreach ($request->uuids as $uuid) {
            $cfdi = Cfdi::where('uuid', $uuid)->firstOrFail();
            $prevPoliza = PolizaContable::where('cfdi_uuid', $uuid)
                ->orWhere('xml_content', 'ilike', '%' . $uuid . '%')
                ->first();
            $esSoloPago = $prevPoliza && $prevPoliza->cfdi_uuid !== $uuid
                && str_contains(strtoupper($prevPoliza->concepto ?? ''), 'PAGO')
                && in_array($cfdi->tipo_comprobante, ['I', 'E', 'N']);
            if ($prevPoliza && !$esSoloPago) {
                $label = $prevPoliza->numero ? " #{$prevPoliza->numero}" : '';
                return response()->json(['success' => false, 'message' => "El CFDI {$uuid} ya fue integrado en la póliza{$label}."], 422);
            }
            $xml = $fileService->getXmlContent($cfdi);
            if (!$xml) {
                return response()->json(['success' => false, 'message' => "No se encontró el XML del CFDI {$uuid}."], 404);
            }
            $xmlContents[] = $xml;
        }

        try {
            $poliza = $this->service->generarPolizaMultiCfdi($xmlContents, $empresaId, $user->id, null, $fecha);
            return response()->json([
                'success' => true,
                'message' => "Póliza {$poliza->numero} generada con " . count($xmlContents) . " CFDI.",
                'poliza_id' => $poliza->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al integrar múltiples CFDI: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Auditoría: Buscar pólizas descuadradas
     */
    public function auditBalance(Request $request)
    {
        $user = $request->user();
        $polizas = $this->service->obtenerPolizasDescuadradas($user->empresa_id);

        return response()->json([
            'success' => true,
            'polizas' => $polizas
        ]);
    }

    /**
     * Auditoría: Comparativo en tiempo real de saldos en libro mayor (102) vs saldos de cuentas bancarias reales
     */
    public function auditBancosBalanza(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        // 1. Obtener todas las cuentas bancarias activas
        $cuentasBancarias = \App\Models\Bancos\BancoCuenta::where('empresa_id', $empresaId)
            ->with('cuentaContable')
            ->get();

        if ($cuentasBancarias->isEmpty()) {
            $cuentasBancarias = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)->get();
        }

        $totalSaldosBancarios = round($cuentasBancarias->sum('saldo_actual'), 2);

        // 2. Obtener saldo contable del libro mayor de la cuenta 102 (Bancos)
        $cuentas102 = CuentaContable::where('empresa_id', $empresaId)
            ->where('codigo', 'like', '102%')
            ->withSum(['asientos as total_debe' => function($q) {
                $q->whereHas('poliza', fn($query) => $query->where('estado', '!=', 'anulada'));
            }], 'debe')
            ->withSum(['asientos as total_haber' => function($q) {
                $q->whereHas('poliza', fn($query) => $query->where('estado', '!=', 'anulada'));
            }], 'haber')
            ->get();

        $saldoContableMayor = 0;
        $desgloseMayor = [];

        foreach ($cuentas102 as $cta) {
            $debe = (float) ($cta->total_debe ?? 0);
            $haber = (float) ($cta->total_haber ?? 0);
            $saldo = $debe - $haber; // 102 es deudora
            if ($cta->es_detalle) {
                $saldoContableMayor += $saldo;
                $desgloseMayor[] = [
                    'codigo' => $cta->codigo,
                    'nombre' => $cta->nombre,
                    'saldo' => round($saldo, 2),
                ];
            }
        }

        $diferencia = round(abs($saldoContableMayor - $totalSaldosBancarios), 2);
        $descuadrado = $diferencia > 0.05;

        return response()->json([
            'success' => true,
            'total_bancos_real' => $totalSaldosBancarios,
            'total_contable_mayor' => round($saldoContableMayor, 2),
            'diferencia' => $diferencia,
            'descuadrado' => $descuadrado,
            'cuentas_bancarias' => $cuentasBancarias->map(fn($c) => [
                'banco' => $c->nombre_banco,
                'alias' => $c->alias,
                'numero_cuenta' => $c->numero_cuenta,
                'saldo' => round($c->saldo_actual ?? $c->saldo_inicial, 2),
                'cuenta_contable' => $c->cuentaContable ? $c->cuentaContable->codigo . ' - ' . $c->cuentaContable->nombre : 'Sin vincular'
            ]),
            'desglose_mayor' => $desgloseMayor,
        ]);
    }

    /**
     * Reporte de Saldos (AR/AP) basado en XML - Nueva Página Dedicada
     */
    public function saldosXmlPage(Request $request)
    {
        return Inertia::render('Contabilidad/SaldosXml');
    }

    /**
     * Reporte de Saldos (AR/AP) basado en XML
     */
    public function saldosXml(Request $request)
    {
        $user = $request->user();
        $mes = $request->input('mes', 'todos');
        $anio = $request->input('anio', 'todos');

        $data = $this->service->obtenerSaldosXml($user->empresa_id, $mes, $anio);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Obtener el detalle de un CFDI PPD (Conceptos, XML y Pólizas vinculadas)
     */
    public function obtenerDetalleFacturaXml(Request $request, string $uuid)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        if (str_starts_with($uuid, 'NV-')) {
            $id = (int) substr($uuid, 3);
            $cxc = \App\Models\CuentasPorCobrar::with(['cliente', 'venta.items.ventable', 'venta.cliente'])->find($id);
            if (!$cxc) {
                return response()->json(['success' => false, 'message' => 'Nota de Venta no encontrada.'], 404);
            }
            $venta = $cxc->venta;
            $conceptos = [];
            if ($venta && $venta->items) {
                foreach ($venta->items as $item) {
                    $conceptos[] = [
                        'clave_prod_serv' => $item->ventable?->sat_clave_prod_serv ?? '01010101',
                        'descripcion' => $item->producto_nombre ?? $item->ventable?->nombre ?? 'Producto/Servicio',
                        'cantidad' => $item->cantidad,
                        'valor_unitario' => $item->precio_unitario ?? $item->precio ?? 0.0,
                        'importe' => $item->subtotal,
                    ];
                }
            }
            return response()->json([
                'success' => true,
                'cfdi' => [
                    'id' => $cxc->id,
                    'uuid' => $uuid,
                    'folio' => $venta?->numero_venta ?? $cxc->id,
                    'serie' => 'NV',
                    'fecha_emision' => $cxc->created_at ? $cxc->created_at->format('Y-m-d H:i:s') : null,
                    'rfc_emisor' => 'RECEPTOR INTERNO',
                    'nombre_emisor' => 'Empresa Local',
                    'rfc_receptor' => $cxc->cliente?->rfc ?? $venta?->cliente?->rfc ?? 'XAXX010101000',
                    'nombre_receptor' => $cxc->cliente?->nombre_razon_social ?? $venta?->cliente?->nombre_razon_social ?? 'Público en General',
                    'email_receptor' => $cxc->cliente?->email ?? $venta?->cliente?->email,
                    'telefono_receptor' => $cxc->cliente?->whatsapp ?? $cxc->cliente?->telefono ?? $venta?->cliente?->whatsapp ?? $venta?->cliente?->telefono,
                    'subtotal' => $venta ? (float)$venta->subtotal : (float)$cxc->monto_total,
                    'descuento' => $venta ? (float)$venta->descuento_general : 0.0,
                    'total_impuestos_trasladados' => $venta ? (float)$venta->iva : 0.0,
                    'total_impuestos_retenidos' => 0.0,
                    'total' => (float)$cxc->monto_total,
                    'metodo_pago' => $cxc->metodo_pago ?? 'Efectivo',
                    'forma_pago' => '01',
                    'pdf_url' => $venta ? route('ventas.pdf', $venta->id) : null,
                    'xml_content' => null,
                    'conceptos' => $conceptos,
                    'datos_adicionales' => $cxc->notas,
                ],
                'polizas' => []
            ]);
        }

        if (str_starts_with($uuid, 'OC-')) {
            $id = (int) substr($uuid, 3);
            $cxp = \App\Models\CuentasPorPagar::with(['proveedor', 'compra.compraItems.comprable', 'compra.proveedor'])->find($id);
            if (!$cxp) {
                return response()->json(['success' => false, 'message' => 'Nota de Compra no encontrada.'], 404);
            }
            $compra = $cxp->compra;
            $conceptos = [];
            if ($compra && $compra->compraItems) {
                foreach ($compra->compraItems as $item) {
                    $conceptos[] = [
                        'clave_prod_serv' => $item->comprable?->sat_clave_prod_serv ?? '01010101',
                        'descripcion' => $item->descripcion ?? $item->comprable?->nombre ?? 'Producto/Servicio',
                        'cantidad' => $item->cantidad,
                        'valor_unitario' => $item->costo_unitario ?? $item->precio ?? 0.0,
                        'importe' => $item->subtotal,
                    ];
                }
            }
            return response()->json([
                'success' => true,
                'cfdi' => [
                    'id' => $cxp->id,
                    'uuid' => $uuid,
                    'folio' => $compra?->numero_compra ?? $cxp->id,
                    'serie' => 'OC',
                    'fecha_emision' => $cxp->fecha_emision ? $cxp->fecha_emision->format('Y-m-d H:i:s') : ($cxp->created_at ? $cxp->created_at->format('Y-m-d H:i:s') : null),
                    'rfc_emisor' => $cxp->proveedor?->rfc ?? $compra?->proveedor?->rfc ?? 'XEXX010101000',
                    'nombre_emisor' => $cxp->proveedor?->nombre_razon_social ?? $compra?->proveedor?->nombre_razon_social ?? 'Proveedor Interno',
                    'rfc_receptor' => 'EMPRESA LOCAL',
                    'nombre_receptor' => 'Empresa Local',
                    'email_receptor' => $cxp->proveedor?->email ?? $compra?->proveedor?->email,
                    'telefono_receptor' => $cxp->proveedor?->telefono ?? $compra?->proveedor?->telefono,
                    'subtotal' => $compra ? (float)$compra->subtotal : (float)$cxp->monto_total,
                    'descuento' => $compra ? (float)$compra->descuento_general : 0.0,
                    'total_impuestos_trasladados' => $compra ? (float)$compra->iva : 0.0,
                    'total_impuestos_retenidos' => 0.0,
                    'total' => (float)$cxp->monto_total,
                    'metodo_pago' => $cxp->metodo_pago ?? 'Transferencia',
                    'forma_pago' => '03',
                    'pdf_url' => $compra && $compra->orden_compra_id ? route('ordenescompra.pdf', $compra->orden_compra_id) : null,
                    'xml_content' => null,
                    'conceptos' => $conceptos,
                    'datos_adicionales' => $cxp->notas,
                ],
                'polizas' => []
            ]);
        }

        $cfdi = Cfdi::where('empresa_id', $empresaId)
            ->where('uuid', $uuid)
            ->with(['conceptos', 'cliente', 'venta.cliente'])
            ->first();

        if (!$cfdi) {
            return response()->json([
                'success' => false,
                'message' => 'Factura CFDI no encontrada.'
            ], 404);
        }

        // Buscar pólizas vinculadas a este UUID
        $polizas = \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)
            ->where('estado', '!=', 'anulada')
            ->where(function($q) use ($uuid) {
                $q->where('cfdi_uuid', $uuid)
                  ->orWhere('xml_content', 'ilike', '%' . $uuid . '%')
                  ->orWhereJsonContains('cfdi_uuids', $uuid)
                  ->orWhereJsonContains('cfdi_uuids', strtoupper($uuid));
            })
            ->with(['asientos.cuenta'])
            ->get();

        // Cargar XML content desde el disco si existe
        $xmlContent = null;
        try {
            $fileService = app(\App\Services\Cfdi\CfdiFileService::class);
            $xmlContent = $fileService->getXmlContent($cfdi);
        } catch (\Exception $e) {
            \Log::warning("No se pudo cargar el XML físico para el CFDI {$uuid}: " . $e->getMessage());
        }

        $conceptos = $cfdi->conceptos->map(function ($c) {
            return [
                'clave_prod_serv' => $c->clave_prod_serv,
                'cantidad' => (float) $c->cantidad,
                'descripcion' => $c->descripcion,
                'valor_unitario' => (float) $c->valor_unitario,
                'importe' => (float) ($c->importe ?? ($c->cantidad * $c->valor_unitario)),
                'descuento' => (float) ($c->descuento ?? 0),
            ];
        })->toArray();

        if (empty($conceptos) && !empty($xmlContent)) {
            try {
                $xml = @simplexml_load_string($xmlContent);
                if ($xml !== false) {
                    $ns = $xml->getNamespaces(true);
                    if (isset($ns['cfdi'])) {
                        $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
                        $nodes = $xml->xpath('//cfdi:Conceptos/cfdi:Concepto');
                        if ($nodes !== false) {
                            foreach ($nodes as $node) {
                                $attr = $node->attributes();
                                $conceptos[] = [
                                    'clave_prod_serv' => (string) ($attr['ClaveProdServ'] ?? ''),
                                    'cantidad' => (float) ($attr['Cantidad'] ?? 1),
                                    'descripcion' => (string) ($attr['Descripcion'] ?? ''),
                                    'valor_unitario' => (float) ($attr['ValorUnitario'] ?? 0),
                                    'importe' => (float) ($attr['Importe'] ?? 0),
                                    'descuento' => (float) ($attr['Descuento'] ?? 0),
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("No se pudo parsear Conceptos del XML: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'cfdi' => [
                'id' => $cfdi->id,
                'uuid' => $cfdi->uuid,
                'folio' => $cfdi->folio,
                'serie' => $cfdi->serie,
                'fecha_emision' => $cfdi->fecha_emision ? $cfdi->fecha_emision->format('Y-m-d H:i:s') : null,
                'rfc_emisor' => $cfdi->rfc_emisor,
                'nombre_emisor' => $cfdi->nombre_emisor,
                'rfc_receptor' => $cfdi->rfc_receptor,
                'nombre_receptor' => $cfdi->nombre_receptor,
                'email_receptor' => $cfdi->cliente?->email ?? $cfdi->venta?->cliente?->email ?? ($cfdi->datos_adicionales['receptor']['email'] ?? null),
                'telefono_receptor' => $cfdi->cliente?->whatsapp ?? $cfdi->cliente?->telefono ?? $cfdi->venta?->cliente?->whatsapp ?? $cfdi->venta?->cliente?->telefono,
                'subtotal' => (float)$cfdi->subtotal,
                'descuento' => (float)$cfdi->descuento,
                'total_impuestos_trasladados' => (float)$cfdi->total_impuestos_trasladados,
                'total_impuestos_retenidos' => (float)$cfdi->total_impuestos_retenidos,
                'total' => (float)$cfdi->total,
                'metodo_pago' => $cfdi->metodo_pago,
                'forma_pago' => $cfdi->forma_pago,
                'pdf_url' => route('cfdi.ver-pdf', $cfdi->uuid),
                'xml_content' => $xmlContent,
                'conceptos' => $conceptos,
                'datos_adicionales' => $cfdi->datos_adicionales,
            ],
            'polizas' => $polizas
        ]);
    }

    /**
     * Enviar comprobante de Saldos XML por correo
     */
    public function enviarCorreoXml(Request $request, string $uuid)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $destino = $request->input('email');

        if (!$destino) {
            return response()->json(['success' => false, 'message' => 'El correo de destino es obligatorio.'], 400);
        }

        // Limite de correos
        $rateLimiter = app(\App\Services\EmailRateLimiterService::class);
        if (!$rateLimiter->canSendEmail()) {
            $stats = $rateLimiter->getStats();
            $waitMinutes = $stats['burst']['reset_in_minutes'];
            return response()->json(['success' => false, 'message' => "Límite de correos alcanzado. Espere {$waitMinutes} minutos."], 429);
        }

        try {
            if (str_starts_with($uuid, 'NV-')) {
                $id = (int) substr($uuid, 3);
                $cxc = \App\Models\CuentasPorCobrar::with(['venta.cliente'])->find($id);
                if (!$cxc || !$cxc->venta) {
                    return response()->json(['success' => false, 'message' => 'No se encontró la venta asociada a esta nota.'], 404);
                }
                \Illuminate\Support\Facades\Mail::to($destino)->send(new \App\Mail\VentaMail($cxc->venta));
            } elseif (str_starts_with($uuid, 'OC-')) {
                $id = (int) substr($uuid, 3);
                $cxp = \App\Models\CuentasPorPagar::with(['compra.ordenCompra'])->find($id);
                if (!$cxp || !$cxp->compra) {
                    return response()->json(['success' => false, 'message' => 'No se encontró la compra asociada a esta nota.'], 404);
                }
                if ($cxp->compra->ordenCompra) {
                    \Illuminate\Support\Facades\Mail::to($destino)->send(new \App\Mail\OrdenCompraMail($cxp->compra->ordenCompra));
                } else {
                    return response()->json(['success' => false, 'message' => 'La nota de compra no tiene una orden en PDF para adjuntar.'], 400);
                }
            } else {
                $cfdi = Cfdi::where('empresa_id', $empresaId)->where('uuid', $uuid)->first();
                if (!$cfdi) {
                    return response()->json(['success' => false, 'message' => 'Factura CFDI no encontrada.'], 404);
                }
                \Illuminate\Support\Facades\Mail::to($destino)->send(new \App\Mail\FacturaMail($cfdi, true));
            }

            $rateLimiter->recordEmailSent();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante enviado exitosamente a ' . $destino
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando correo en Saldos XML: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al enviar correo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Enviar notificación de Saldos XML por WhatsApp
     */
    public function enviarWhatsAppXml(Request $request, string $uuid)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $telefono = $request->input('telefono');

        if (!$telefono) {
            return response()->json(['success' => false, 'message' => 'El número de teléfono de destino es obligatorio.'], 400);
        }

        $empresa = \App\Models\Empresa::find($empresaId);
        $mensaje = "";

        if (str_starts_with($uuid, 'NV-')) {
            $id = (int) substr($uuid, 3);
            $cxc = \App\Models\CuentasPorCobrar::with(['cliente', 'venta'])->find($id);
            if (!$cxc || !$cxc->venta) {
                return response()->json(['success' => false, 'message' => 'No se encontró la venta asociada.'], 404);
            }
            $venta = $cxc->venta;
            $nombre = $cxc->cliente?->nombre_razon_social ?? 'Cliente';
            $folio = $venta->numero_venta ?? 'NV-' . $id;
            $totalFmt = number_format((float)$cxc->monto_total, 2, '.', ',');
            $urlPdf = $venta->sharing_token ? url('/share/venta/' . $venta->sharing_token . '/pdf') : '';
            $mensaje = "Hola {$nombre}, le enviamos su Nota de Venta {$folio}. Total: \${$totalFmt} MXN\n\n";
            if ($urlPdf) {
                $mensaje .= "Puede ver o descargar el PDF aquí:\n{$urlPdf}";
            } else {
                $mensaje .= "Gracias por su preferencia.";
            }
        } elseif (str_starts_with($uuid, 'OC-')) {
            $id = (int) substr($uuid, 3);
            $cxp = \App\Models\CuentasPorPagar::with(['proveedor', 'compra'])->find($id);
            if (!$cxp) {
                return response()->json(['success' => false, 'message' => 'No se encontró la nota de compra asociada.'], 404);
            }
            $nombre = $cxp->proveedor?->nombre_razon_social ?? 'Proveedor';
            $folio = $cxp->compra?->numero_compra ?? 'OC-' . $id;
            $totalFmt = number_format((float)$cxp->monto_total, 2, '.', ',');
            $mensaje = "Hola {$nombre}, le enviamos la referencia de Orden/Compra {$folio}. Total: \${$totalFmt} MXN\n\nQuedamos a su disposición.";
        } else {
            $cfdi = Cfdi::where('empresa_id', $empresaId)->where('uuid', $uuid)->with(['cliente', 'venta.cliente'])->first();
            if (!$cfdi) {
                return response()->json(['success' => false, 'message' => 'Factura CFDI no encontrada.'], 404);
            }
            $isRecibido = $cfdi->direccion === 'recibido';
            $nombre = $isRecibido ? ($cfdi->nombre_emisor ?: 'Proveedor') : ($cfdi->cliente?->nombre_razon_social ?? $cfdi->venta?->cliente?->nombre_razon_social ?? $cfdi->nombre_receptor ?? 'Cliente');
            $folio = $cfdi->serie . $cfdi->folio;
            $totalFmt = number_format((float)$cfdi->total, 2, '.', ',');
            $fechaFmt = isset($cfdi->fecha_emision) ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y') : 'N/A';
            $mensaje = "Hola {$nombre}, le compartimos los datos de la Factura Fiscal {$folio} (UUID: {$uuid}). Emisión: {$fechaFmt}. Total: \${$totalFmt} MXN\n\n";
            
            if ($isRecibido) {
                $mensaje .= "Actualmente estamos realizando nuestra conciliación contable y fiscal. En nuestros registros del SAT, esta factura emitida por su empresa aparece con saldo pendiente o carece de su Recibo Electrónico de Pago (REP).\n\n";
                $mensaje .= "Le solicitamos atentamente que, si ya fue liquidada por nuestra parte, nos apoye enviando el archivo XML y PDF del REP correspondiente; de lo contrario, infórmenos para verificar nuestro estatus de pagos.\n\n";
            } else {
                $mensaje .= "Actualmente estamos realizando nuestra conciliación contable y bancaria, y tenemos registrada esta factura como pendiente de pago o acreditación.\n\n";
                $mensaje .= "Le invitamos amablemente a que, si ya fue liquidada, nos apoye compartiendo el comprobante de pago bancario (transferencia o ficha de depósito); de lo contrario, le invitamos a realizar o programar el pago de la misma.\n\n";
            }
            if ($cfdi->pdf_url) {
                $urlPdf = url('/storage/' . $cfdi->pdf_url);
                $mensaje .= "Puede descargar su PDF aquí:\n{$urlPdf}";
            } else {
                $mensaje .= "Quedamos a su atenta disposición para cualquier duda o aclaración.";
            }
        }

        if ($empresa && $empresa->whatsapp_enabled) {
            try {
                $waId = \App\Services\WhatsAppService::canonicalWaId((string) $telefono);
                $whatsappService = \App\Services\WhatsAppService::fromEmpresa($empresa);
                $response = $whatsappService->sendTextMessage($waId, $mensaje);

                $messageId = $response['messages'][0]['id'] ?? 'out_' . time();
                $chatMsg = \App\Models\WhatsAppChat::create([
                    'empresa_id' => $empresaId,
                    'user_id' => $user->id,
                    'wa_id' => $waId,
                    'body' => $mensaje,
                    'direction' => 'outbound',
                    'type' => 'text',
                    'message_id' => $messageId,
                    'status' => 'sent',
                    'received_at' => now(),
                ]);
                \App\Models\WhatsAppConversation::updateOrCreate(
                    ['empresa_id' => $empresaId, 'wa_id' => $waId],
                    ['last_message_at' => now(), 'status' => 'open']
                );
                event(new \App\Events\WhatsAppMessageReceived($chatMsg));

                return response()->json([
                    'success' => true,
                    'via' => 'api',
                    'message' => 'Mensaje enviado exitosamente por WhatsApp a ' . $telefono
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("WhatsApp API falló en Saldos XML, usando fallback web: " . $e->getMessage());
            }
        }

        $urlWeb = "https://api.whatsapp.com/send?phone=" . preg_replace('/[^0-9]/', '', $telefono) . "&text=" . urlencode($mensaje);
        return response()->json([
            'success' => true,
            'via' => 'web',
            'url' => $urlWeb,
            'message' => 'Abriendo WhatsApp Web para enviar mensaje a ' . $telefono
        ]);
    }

    public function conciliarCsv(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $request->validate([
            'csv' => 'required',
        ]);

        try {
            $archivos = $request->file('csv');
            // Si viene como csv[] en FormData, a veces Laravel lo entrega directo o como array
            if (!$archivos) {
                $archivos = $request->file('csv[]'); // Respaldo para nombres literales con corchetes
            }

            if (!$archivos) {
                return response()->json(['success' => false, 'message' => 'No se recibió ningún archivo.'], 422);
            }

            if (!is_array($archivos)) $archivos = [$archivos];

            $todosLosMovimientos = [];
            $erroresParsing = [];

            foreach ($archivos as $archivo) {
                try {
                    $extension = strtolower($archivo->getClientOriginalExtension());
                    
                    if ($extension === 'pdf') {
                        $resultado = $this->parserService->parsearPdf($archivo->path());
                    } elseif (in_array($extension, ['xls', 'xlsx'])) {
                        $resultado = $this->parserService->parsearExcel($archivo->path());
                    } else {
                        $contenido = file_get_contents($archivo->path());
                        $resultado = $this->parserService->parsear($contenido);
                    }

                    if (!empty($resultado['movimientos'])) {
                        $todosLosMovimientos = array_merge($todosLosMovimientos, $resultado['movimientos']);
                    } else {
                        $erroresParsing[] = "{$archivo->getClientOriginalName()}: No se detectaron movimientos.";
                    }
                } catch (\Exception $e) {
                    $erroresParsing[] = "{$archivo->getClientOriginalName()}: " . $e->getMessage();
                }
            }

            if (empty($todosLosMovimientos)) {
                $msg = 'No se encontraron movimientos válidos.';
                if (!empty($erroresParsing)) $msg .= ' Detalles: ' . implode(' ', $erroresParsing);
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            // Transformar movimientos al formato que espera el conciliador de contabilidad
            $pagos = [];
            $noiseWords = ['PAGO', 'RECIBIDO', 'DE', 'DEL', 'AL', 'EL', 'LA', 'PARA', 'POR', 'CANALES', 'SERVICIO', 'TRANSFERENCIA', 'DEPOSITO', 'SPEI', 'TRASPASO', 'ABONO', 'SA', 'CV', 'S.A.', 'C.V.', 'SAPI', 'S.A.P.I.'];

            foreach ($todosLosMovimientos as $mov) {
                // El conciliador contable solo busca depósitos (ingresos)
                if ($mov['tipo'] !== 'deposito') continue;

                $concepto = strtoupper($mov['concepto']);
                
                // Clean concept to extract client name
                $rawWords = explode(' ', strtoupper(preg_replace('/[^A-Z0-9 ]/', ' ', $concepto)));
                $cleanWords = array_values(array_filter($rawWords, fn($w) => !in_array($w, $noiseWords) && strlen($w) > 2));
                
                $pagos[] = [
                    'nombre' => implode(' ', $cleanWords),
                    'clean_words' => $cleanWords,
                    'monto' => abs($mov['monto']),
                    'fecha' => $mov['fecha'],
                    'original_concepto' => $mov['concepto']
                ];
            }

            // Get all PPD pending invoices
        $facturas = Cfdi::where('empresa_id', $empresaId)
            ->where('metodo_pago', 'PPD')
            ->where(function($q) { $q->whereNull('estado_sat')->orWhere('estado_sat', '!=', 'Cancelado'); })
            ->where('tipo_comprobante', 'I')
            ->get();

        $coincidencias = [];
        $sinCoincidencia = [];

        foreach ($pagos as $p) {
            $encontrada = false;
            
            if (empty($p['clean_words'])) {
                $sinCoincidencia[] = ['nombre' => $p['original_concepto'], 'monto' => $p['monto']];
                continue;
            }

            foreach ($facturas as $f) {
                $cliente = strtoupper($f->direccion === 'emitido' ? ($f->nombre_receptor ?? '') : $f->nombre_emisor);
                
                // Tolerance for small rounding differences
                if (abs((float)$f->total - $p['monto']) > 0.10) continue;
                
                // Match if any significant word from the CSV is found in the client name
                $wordMatch = false;
                foreach ($p['clean_words'] as $word) {
                    if (str_contains($cliente, $word)) {
                        $wordMatch = true;
                        break;
                    }
                }

                if ($wordMatch) {
                    // Validar que la factura sea anterior o del mismo día que el pago
                    // Damos 1 día de tolerancia por temas de zona horaria o registros tardíos
                    $fechaPago = \Illuminate\Support\Carbon::parse($p['fecha']);
                    $fechaFactura = \Illuminate\Support\Carbon::parse($f->fecha_emision);
                    
                    if ($fechaFactura->greaterThan($fechaPago->copy()->addDay())) {
                        continue;
                    }

                    $integrada = PolizaContable::where('cfdi_uuid', $f->uuid)->exists();
                    $coincidencias[] = [
                        'folio' => $f->folio,
                        'cliente' => $cliente,
                        'total' => (float) $f->total,
                        'monto_pagado' => $p['monto'],
                        'fecha_factura' => $f->fecha_emision,
                        'fecha_pago' => $p['fecha'],
                        'uuid' => $f->uuid,
                        'integrada' => $integrada,
                        'concepto_banco' => $p['original_concepto']
                    ];
                    $encontrada = true;
                    // No hacemos break porque un pago podría saldar múltiples facturas
                }
            }
            if (!$encontrada) {
                $sinCoincidencia[] = ['nombre' => $p['original_concepto'], 'monto' => $p['monto']];
            }
        }

            return response()->json([
                'success' => true,
                'coincidencias' => $coincidencias,
                'sin_coincidencia' => $sinCoincidencia,
                'total_coincidencias' => count($coincidencias),
                'total_pagos' => count($pagos),
            ]);

        } catch (\Exception $e) {
            Log::error("Error en conciliarCsv: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar el archivo: ' . $e->getMessage()], 500);
        }
    }

    public function syncAnualAi(Request $request)
    {
        set_time_limit(300); // 5 minutos de tiempo de ejecución para soportar lotes de 100 XML
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $anio = $request->input('anio', date('Y'));
        $limit = (int) $request->input('limit', 100);

        // Buscar CFDIs pendientes (ignorando pólizas eliminadas en papelera / soft deletes)
        $subQuery = 'EXISTS (SELECT 1 FROM contab_polizas WHERE contab_polizas.deleted_at IS NULL AND (CAST(contab_polizas.cfdi_uuid AS TEXT) = cfdis.uuid OR contab_polizas.xml_content LIKE \'%\' || cfdis.uuid || \'%\'))';
        
        $query = Cfdi::whereRaw('NOT ' . $subQuery)
            ->where('estatus', 'vigente')
            ->where('empresa_id', $empresaId);

        if ($anio !== 'todo') {
            $query->whereYear('fecha_emision', $anio);
        }

        $totalPendientes = $query->count();
        $cfdis = $query->orderBy('fecha_emision', 'asc')->take($limit)->get();

        if ($cfdis->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $anio === 'todo' ? 'Todos los años históricos están 100% sincronizados.' : "El año {$anio} ya está 100% sincronizado y contabilizado.",
                'procesados' => 0,
                'restantes' => 0
            ]);
        }

        $fileService = app(\App\Services\Cfdi\CfdiFileService::class);
        $exitos = 0;
        $errores = 0;
        $cuentasCreadas = [];

        foreach ($cfdis as $cfdi) {
            try {
                $xmlContent = $fileService->getXmlContent($cfdi);
                if (!$xmlContent) {
                    $errores++;
                    continue;
                }

                $poliza = $this->service->generarPolizaDesdeXml($xmlContent, $empresaId, $user->id);
                if ($poliza) {
                    $exitos++;
                    $mapeo = \App\Models\Contab\RfcMapping::with('cuenta')->where('empresa_id', $empresaId)->where('rfc', $cfdi->rfc_emisor ?? '')->first();
                    if ($mapeo && str_contains($mapeo->ai_reasoning ?? '', '[CUENTA CREADA POR IA]')) {
                        $cuentasCreadas[] = $mapeo->cuenta ? "[{$mapeo->cuenta->codigo}] {$mapeo->cuenta->nombre}" : 'Cuenta Autónoma';
                    }
                } else {
                    $errores++;
                }
            } catch (\Exception $e) {
                $errores++;
                Log::error("Error en syncAnualAi: " . $e->getMessage(), ['uuid' => $cfdi->uuid]);
            }
        }

        $restantes = max(0, $totalPendientes - $exitos - $errores);
        $cuentasUnicas = array_unique($cuentasCreadas);
        $cuentasTxt = !empty($cuentasUnicas) ? " Se crearon autónomamente " . count($cuentasUnicas) . " cuentas nuevas." : "";

        return response()->json([
            'success' => true,
            'message' => "Se procesó un lote de " . count($cfdis) . " XMLs. Éxitos: {$exitos}, Errores: {$errores}.{$cuentasTxt}",
            'procesados' => $exitos,
            'errores' => $errores,
            'restantes' => $restantes,
            'total_inicial' => $totalPendientes,
            'cuentas_creadas' => array_values($cuentasUnicas)
        ]);
    }

    public function analizarEstadoResultadosAi(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        $forceRefresh = filter_var($request->input('refresh', false), FILTER_VALIDATE_BOOLEAN);

        $cacheKey = "ai_estado_resultados_{$empresaId}_{$anio}_{$mes}";

        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        try {
            $analisis = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400 * 30, function() use ($empresaId, $mes, $anio) {
                $inicio = "{$anio}-{$mes}-01";
                $fin = date('Y-m-t', strtotime($inicio));
                $data = $this->service->obtenerEstadoResultados($empresaId, $inicio, $fin);

                $gemini = app(\App\Services\AI\GeminiService::class);
                if (!$gemini->isAvailable()) {
                    throw new \Exception("La API de Gemini AI no está configurada o disponible en el sistema.");
                }

                $mesNombre = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'][$mes] ?? $mes;
                
                $jsonContext = json_encode([
                    'periodo' => "{$mesNombre} {$anio}",
                    'resumen' => [
                        'ventas_netas_totales' => $data['resumen']['ventas_netas'] ?? 0,
                        'utilidad_bruta' => $data['resumen']['utilidad_bruta'] ?? 0,
                        'utilidad_operacion' => $data['resumen']['utilidad_operacion'] ?? 0,
                        'utilidad_neta' => $data['resumen']['utilidad_neta'] ?? 0,
                        'margen_bruto_porcentaje' => $data['resumen']['margen_bruto'] ?? 0,
                        'margen_operativo_porcentaje' => $data['resumen']['margen_operativo'] ?? 0,
                        'margen_neto_porcentaje' => $data['resumen']['margen_neto'] ?? 0,
                    ],
                    'rubros_desglose' => collect($data['secciones'] ?? [])->map(function($s) {
                        return [
                            'seccion' => $s['titulo'],
                            'subtotal_seccion' => $s['total'],
                            'top_cuentas' => collect($s['items'] ?? [])->sortByDesc('monto')->take(6)->map(fn($i) => [
                                'cuenta' => $i['nombre'],
                                'codigo' => $i['codigo'],
                                'monto_mxn' => $i['monto'],
                                'porcentaje' => $i['porcentaje']
                            ])->values()->all()
                        ];
                    })->all()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $prompt = "Eres el Director Financiero Experto de Inteligencia Artificial (CFO AI) de una empresa comercial y de servicios.
A continuación te presento los datos contables reales y exactos del Estado de Resultados del periodo {$mesNombre} {$anio}.

ESTRUCTURA DE DATOS FINANCIEROS:
{$jsonContext}

Por favor, elabora un reporte de Análisis Ejecutivo y Diagnóstico Financiero en formato Markdown limpio y altamente profesional (sin usar etiquetas HTML), utilizando la siguiente estructura obligatoria:

### 📊 Resumen Ejecutivo Financiero
Elabora un diagnóstico directo en 2 párrafos sobre el desempeño del periodo. Menciona las ventas netas y si la utilidad neta es positiva o si hay focos de alerta.

### 📈 Evaluación de Márgenes de Ganancia
Analiza el Margen Bruto, Operativo y Neto. Explica en términos sencillos para el dueño del negocio si la estructura de costos directos y gastos fijos está en un nivel competitivo o si se está perdiendo rentabilidad.

### 🔍 Hallazgos Clave en Gastos e Ingresos
Destaca 3 rubros o cuentas específicas de gastos (ya sea de venta, administración o costos directos) que representaron el mayor desembolso en este periodo.

### 💡 Recomendaciones Estratégicas AI
Brinda 4 viñetas de acción ejecutiva y realista para optimizar el flujo de caja, controlar fugas de capital o potenciar el margen operativo el próximo mes.

Sé sumamente riguroso con las cifras exactas y mantén un tono de asesoría empresarial premium.";

                $res = $gemini->chat([
                    ['role' => 'user', 'content' => $prompt]
                ]);

                if (!$res['success']) {
                    throw new \Exception($res['error'] ?? 'Error comunicándose con los servidores de IA.');
                }

                return $res['message']['content'] ?? 'No se pudo generar el análisis financiero.';
            });

            return response()->json([
                'success' => true,
                'analisis' => $analisis,
                'periodo' => "{$mes}-{$anio}",
                'cached' => !$forceRefresh
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function analizarBalanzaAi(Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        $forceRefresh = filter_var($request->input('refresh', false), FILTER_VALIDATE_BOOLEAN);

        $cacheKey = "ai_balanza_{$empresaId}_{$anio}_{$mes}";

        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        try {
            $analisis = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400 * 30, function() use ($empresaId, $mes, $anio) {
                $inicio = "{$anio}-{$mes}-01";
                $fin = date('Y-m-t', strtotime($inicio));
                $data = $this->service->obtenerBalanza($empresaId, $inicio, $fin);

                $gemini = app(\App\Services\AI\GeminiService::class);
                if (!$gemini->isAvailable()) {
                    throw new \Exception("La API de Gemini AI no está configurada o disponible en el sistema.");
                }

                $mesNombre = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'][$mes] ?? $mes;
                
                $nivel1 = collect($data)->where('nivel', 1);
                $totales = [
                    'inicial_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_inicial'),
                    'inicial_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_inicial'),
                    'cargos' => $nivel1->sum('cargos'),
                    'abonos' => $nivel1->sum('abonos'),
                    'final_deudor' => $nivel1->where('naturaleza', 'deudora')->sum('saldo_final'),
                    'final_acreedor' => $nivel1->where('naturaleza', 'acreedora')->sum('saldo_final'),
                ];

                $cuentasRelevantes = collect($data)->filter(fn($c) => $c['nivel'] <= 2 && (abs($c['saldo_inicial']) > 100 || $c['cargos'] > 100 || $c['abonos'] > 100 || abs($c['saldo_final']) > 100))
                    ->map(fn($c) => [
                        'codigo' => $c['codigo'],
                        'nombre' => $c['nombre'],
                        'naturaleza' => $c['naturaleza'],
                        'inicial' => $c['saldo_inicial'],
                        'cargos' => $c['cargos'],
                        'abonos' => $c['abonos'],
                        'final' => $c['saldo_final']
                    ])->values()->all();

                $jsonContext = json_encode([
                    'periodo' => "{$mesNombre} {$anio}",
                    'totales_balanza' => $totales,
                    'cuentas_principales' => $cuentasRelevantes
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                $prompt = "Eres un Auditor Financiero Experto de Inteligencia Artificial (CPA AI) especializado en normativas contables mexicanas (Anexo 24 SAT y NIF).
A continuación te presento los datos reales de la Balanza de Comprobación contable de la empresa para el periodo {$mesNombre} {$anio}.

ESTRUCTURA DE DATOS CONTABLES:
{$jsonContext}

Por favor, elabora un reporte de Auditoría y Diagnóstico de Balanza en formato Markdown limpio y altamente profesional (sin usar etiquetas HTML), utilizando la siguiente estructura obligatoria:

### 🏛️ Auditoría de Cuadre y Partida Doble
Verifica que los cargos y abonos del periodo cuadren con exactitud. Menciona si la balanza cumple el principio de dualidad económica y la integridad de los saldos iniciales y finales.

### 💼 Análisis de Liquidez y Capital de Trabajo
Analiza los saldos finales de las cuentas de Activo Circulante (Efectivo, Clientes, Almacén) frente al Pasivo a Corto Plazo (Proveedores, Impuestos). Explica en términos directos si la empresa cuenta con solvencia para sus compromisos.

### 🔍 Cuentas con Mayor Variación en el Periodo
Identifica y comenta 3 cuentas específicas que tuvieron los cargos o abonos más representativos o inusuales durante este mes.

### 💡 Recomendaciones Contables y Fiscales AI
Proporciona 4 viñetas accionables para el equipo contable o el dueño del negocio enfocadas en cobranza, depuración de saldos, control de inventario o gestión de pasivos.

Sé sumamente riguroso con los montos exactos y mantén un tono de consultoría premium.";

                $res = $gemini->chat([
                    ['role' => 'user', 'content' => $prompt]
                ]);

                if (!$res['success']) {
                    throw new \Exception($res['error'] ?? 'Error comunicándose con los servidores de IA.');
                }

                return $res['message']['content'] ?? 'No se pudo generar el análisis de la balanza.';
            });

            return response()->json([
                'success' => true,
                'analisis' => $analisis,
                'periodo' => "{$mes}-{$anio}",
                'cached' => !$forceRefresh
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesa un estado de cuenta en PDF y auto-empareja sus movimientos con los XMLs y Pólizas existentes
     */
    public function procesarEstadoCuentaBancarioPdf(\Illuminate\Http\Request $request, \App\Services\AI\BankStatementReaderService $reader)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:15360',
            'password' => 'nullable|string'
        ]);

        $empresaId = auth()->user()?->empresa_id ?? (\App\Models\EmpresaConfiguracion::first()?->id ?? 1);

        try {
            $file = $request->file('file');
            $filename = uniqid('statement_') . '.pdf';
            $fullPath = storage_path('app/' . $filename);
            
            // Mover directamente a la ruta física garantizada en storage/app
            $file->move(storage_path('app'), $filename);

            $bankData = $reader->extractStatement($fullPath, $request->input('password'));

            if (\Illuminate\Support\Facades\File::exists($fullPath)) {
                \Illuminate\Support\Facades\File::delete($fullPath);
            }

            // Inicializar periodo y fechas para el cruce contable/fiscal
            $periodo = $bankData['periodo_anio_mes'] ?? date('Y-m');
            [$anio, $mes] = explode('-', $periodo) + [date('Y'), date('m')];

            $fechaBase = \Carbon\Carbon::createFromDate($anio, $mes, 1);
            $fechaPrevio = $fechaBase->copy()->subMonth();

            // Cargar Pólizas Contables del periodo y mes previo para cruce directo
            $polizas = \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)
                ->where(function($q) use ($anio, $mes, $fechaPrevio) {
                    $q->where(function($sub) use ($anio, $mes) {
                        $sub->whereYear('fecha', $anio)
                            ->whereMonth('fecha', $mes);
                    })->orWhere(function($sub) use ($fechaPrevio) {
                        $sub->whereYear('fecha', $fechaPrevio->year)
                            ->whereMonth('fecha', $fechaPrevio->month);
                    });
                })
                ->with('asientos.cuenta')
                ->get();

            $xmls = \App\Models\Cfdi::where('empresa_id', $empresaId)
                ->whereIn('tipo_comprobante', ['I', 'E']) // Solo Ingresos y Egresos, NO REPs (P)
                ->where(function($q) {
                    $q->whereNull('estatus')->orWhere('estatus', '!=', 'cancelado');
                })
                ->where(function($q) use ($anio, $mes, $fechaPrevio) {
                    $q->where(function($sub) use ($anio, $mes) {
                        $sub->whereYear('fecha_emision', $anio)
                            ->whereMonth('fecha_emision', $mes);
                    })->orWhere(function($sub) use ($fechaPrevio) {
                        $sub->whereYear('fecha_emision', $fechaPrevio->year)
                            ->whereMonth('fecha_emision', $fechaPrevio->month);
                    });
                })
                ->orderBy('fecha_emision', 'asc')
                ->get();

            $movimientosEnriquecidos = [];
            $usedXmlIds = [];     // Evitar que un XML cruce con 2+ movimientos
            $usedPolizaIds = [];  // Evitar que una póliza cruce con 2+ movimientos

            // Pre-cargar REPs y construir mapa UUID_factura → fecha_pago del REP
            $repMap = []; // ['uuid_factura' => ['fecha_pago' => Carbon, 'rep_folio' => 'CB1467']]
            $reps = \App\Models\Cfdi::where('empresa_id', $empresaId)
                ->where('tipo_comprobante', 'P')
                ->whereNotNull('complementos')
                ->get();
            foreach ($reps as $rep) {
                $pagos = $rep->complementos['pagos'] ?? [];
                foreach ($pagos as $pago) {
                    $fechaPago = null;
                    try { $fechaPago = \Carbon\Carbon::parse($pago['fecha_pago'] ?? ''); } catch (\Exception $e) {}
                    foreach ($pago['doctos_relacionados'] ?? [] as $doc) {
                        $uuid = strtolower(trim($doc['id_documento'] ?? ''));
                        if (!empty($uuid) && $fechaPago) {
                            $repMap[$uuid] = [
                                'fecha_pago' => $fechaPago,
                                'rep_folio' => $rep->serie . $rep->folio,
                                'rep_id' => $rep->id,
                                'monto_pagado' => (float)($doc['imp_pagado'] ?? 0),
                            ];
                        }
                    }
                }
            }
            foreach ($bankData['movimientos'] as $mov) {
                $monto = (float)($mov['monto'] ?? 0);
                $tipo = $mov['tipo'] ?? 'cargo';
                $fecha = $mov['fecha'] ?? '';
                $concepto = strtoupper($mov['concepto'] ?? '');
                $referencia = strtoupper(trim($mov['referencia'] ?? ''));

                $matchedPoliza = null;
                $matchedXml = null;
                $matchReason = '';
                $status = 'pending';
                $suggestedAction = null;

                $fechaMov = null;
                try {
                    $fechaMov = \Carbon\Carbon::parse($fecha);
                } catch (\Exception $e) {}

                // --- PRIORIDAD 1: BUSCAR COINCIDENCIA CON PÓLIZA CONTABLE ---
                $bestPolizaScore = 0;
                foreach ($polizas as $p) {
                    if (in_array($p->id, $usedPolizaIds)) continue; // dedup

                    $montoPoliza = (float)$p->total;
                    $tipoPoliza = match(strtolower($p->tipo)) {
                        'ingreso' => 'abono',
                        'egreso' => 'cargo',
                        'diario' => $tipo, // diario puede ser cualquiera
                        default => 'otro',
                    };

                    // Validar si el monto cuadra con el total de la póliza O con algún asiento específico de bancos
                    $montoCuadra = (round($montoPoliza, 2) === round($monto, 2));
                    if (!$montoCuadra && $p->relationLoaded('asientos')) {
                        foreach ($p->asientos as $asiento) {
                            if (round((float)$asiento->debe, 2) === round($monto, 2) || round((float)$asiento->haber, 2) === round($monto, 2)) {
                                $montoCuadra = true;
                                break;
                            }
                        }
                    }

                    if (!$montoCuadra) continue;
                    if ($tipoPoliza !== $tipo && strtolower($p->tipo) !== 'diario') continue;

                    $fechaPoliza = null;
                    try { $fechaPoliza = \Carbon\Carbon::parse($p->fecha); } catch (\Exception $e) {}
                    $diasDif = ($fechaMov && $fechaPoliza) ? abs($fechaMov->diffInDays($fechaPoliza)) : 999;
                    if ($diasDif > 15) continue;

                    // Scoring: fecha cercana + nombre en concepto bancario
                    $score = max(1, 16 - $diasDif); // 1-16 pts por cercanía
                    $conceptoPoliza = strtoupper($p->concepto ?? '');
                    // Extraer nombre del concepto de póliza (después del " - ")
                    if (preg_match('/- (.+)$/u', $conceptoPoliza, $m)) {
                        $nombrePoliza = trim($m[1]);
                        $palabras = array_filter(explode(' ', $nombrePoliza), fn($w) => mb_strlen($w) >= 4);
                        $hits = 0;
                        foreach ($palabras as $pal) {
                            if (str_contains($concepto, $pal)) $hits++;
                        }
                        if (count($palabras) > 0 && $hits >= 1) $score += 10 * $hits;
                    }

                    if ($score > $bestPolizaScore) {
                        $bestPolizaScore = $score;
                        $matchedPoliza = $p;
                        $status = 'matched';
                        $matchReason = "✅ Póliza " . ucfirst($p->tipo) . " #{$p->numero} en libros.";
                    }
                }

                // --- PRIORIDAD 2: SI NO HAY PÓLIZA, BUSCAR COINCIDENCIA FISCAL CON XML (FACTURA) ---
                if ($status !== 'matched') {
                    $bestXmlScore = 0;
                    $textoCompleto = $concepto . ' ' . $referencia;

                    // Recopilar candidatos con mismo monto y tipo
                    $candidatos = [];
                    foreach ($xmls as $x) {
                        if (in_array($x->id, $usedXmlIds)) continue;
                        $montoXml = (float)$x->total;
                        $tipoXml = ($x->tipo_comprobante === 'I') ? 'abono' : 'cargo';
                        if (round($montoXml, 2) !== round($monto, 2)) continue;
                        if ($tipo !== $tipoXml && !($tipo === 'abono' && $x->tipo_comprobante === 'I')) continue;
                        $candidatos[] = $x;
                    }

                    // Si hay múltiples candidatos con el mismo monto (pagos recurrentes),
                    // la factura correcta es la más antigua que AÚN NO tiene REP.
                    $hayRecurrencia = count($candidatos) > 1;

                    foreach ($candidatos as $x) {
                        $fechaXml = null;
                        try { $fechaXml = \Carbon\Carbon::parse($x->fecha_emision); } catch (\Exception $e) {}
                        $diasDif = ($fechaMov && $fechaXml) ? abs($fechaMov->diffInDays($fechaXml)) : 999;

                        $score = 0;
                        $reason = '';
                        $folio = trim($x->folio ?? '');
                        $serieFolio = trim(($x->serie ?? '') . $folio);
                        $uuidXml = strtolower(trim($x->uuid ?? ''));
                        $yaTieneRep = !empty($uuidXml) && isset($repMap[$uuidXml]);

                        // Fase 0: REP confirma que ESTE pago es de ESTA factura (fecha_pago = fecha banco)
                        if ($yaTieneRep) {
                            $repInfo = $repMap[$uuidXml];
                            $diasRepVsBanco = $fechaMov ? abs($fechaMov->diffInDays($repInfo['fecha_pago'])) : 999;
                            if ($diasRepVsBanco <= 3) {
                                $score = 200;
                                $reason = "🔒 REP {$repInfo['rep_folio']} confirma pago del " . $repInfo['fecha_pago']->format('d/m/Y') . " → {$serieFolio}";
                            } else {
                                // Ya tiene REP pero de otra fecha = esta factura ya fue pagada, SKIP
                                if ($hayRecurrencia) continue;
                            }
                        }

                        // Fase 0b: Pagos recurrentes → la más antigua SIN REP es la correcta
                        if ($score < 200 && $hayRecurrencia && !$yaTieneRep) {
                            $score = 150; // Alta confianza: es la siguiente factura pendiente de pago
                            $reason = "📋 Pago recurrente: {$serieFolio} es la factura más antigua sin REP.";
                        }

                        // Fase A: Folio explícito en concepto/referencia
                        if ($score < 150) {
                            $mencionFolio = false;
                            if (!empty($folio) && strlen($folio) >= 3) {
                                if (str_contains($textoCompleto, strtoupper($folio)) || (!empty($serieFolio) && str_contains($textoCompleto, strtoupper($serieFolio)))) {
                                    $mencionFolio = true;
                                }
                            }
                            if ($mencionFolio) {
                                $score = max($score, 100);
                                $reason = "🎯 Folio '{$serieFolio}' explícito en concepto bancario.";
                            }
                        }

                        // Fase B: Cercanía temporal + nombre/RFC (solo si no es recurrente o no hay mejor opción)
                        if ($score < 100 && $diasDif <= 15) {
                            $score = max($score, max(1, 16 - $diasDif));
                            $reason = "Monto (\$" . number_format($monto, 2) . ") + fecha cercana ({$diasDif}d) → CFDI " . $serieFolio;

                            $nombreContraparte = strtoupper($x->nombre_emisor ?? '');
                            if (strtoupper($x->rfc_emisor) === 'LONJ880321KMA') {
                                $nombreContraparte = strtoupper($x->nombre_receptor ?? '');
                            }
                            if (!empty($nombreContraparte)) {
                                $palabras = array_filter(explode(' ', $nombreContraparte), fn($w) => mb_strlen($w) >= 4);
                                foreach ($palabras as $pal) {
                                    if (str_contains($concepto, $pal)) $score += 10;
                                }
                            }
                            $rfcContraparte = (strtoupper($x->rfc_emisor) === 'LONJ880321KMA') ? $x->rfc_receptor : $x->rfc_emisor;
                            if (!empty($rfcContraparte) && str_contains($concepto, strtoupper($rfcContraparte))) {
                                $score += 20;
                            }
                        }

                        if ($score > $bestXmlScore) {
                            $bestXmlScore = $score;
                            $matchedXml = $x;
                            $status = 'matched';
                            $matchReason = $reason;
                        }
                    }
                }

                if ($status !== 'matched') {
                    if (str_contains($concepto, 'COMISION') || str_contains($concepto, 'INTERES') || str_contains($concepto, 'IVA COMISION') || str_contains($concepto, 'MEMBRESIA') || str_contains($concepto, 'ANUALIDAD')) {
                        $suggestedAction = 'create_comision';
                    } elseif (str_contains($concepto, 'SPEI RECIBIDO') || str_contains($concepto, 'DEP EN EFECTIVO') || str_contains($concepto, 'DEPOSITO') || str_contains($concepto, 'ABONO')) {
                        $suggestedAction = 'create_ingreso_directo';
                    } elseif (str_contains($concepto, 'SPEI ENVIADO') || str_contains($concepto, 'TRASPASO') || str_contains($concepto, 'DOMICILIACION') || str_contains($concepto, 'RETIRO') || str_contains($concepto, 'PAGO TARJETA') || str_contains($concepto, 'CARGO')) {
                        $suggestedAction = 'create_egreso_directo';
                    } elseif (str_contains($concepto, 'NOMINA') || str_contains($concepto, 'NÓMINA') || str_contains($concepto, 'DISPERSIÓN') || str_contains($concepto, 'DISPERSION')) {
                        $suggestedAction = 'create_egreso_directo';
                    }
                }

                // Detección de requisito de emisión/solicitud de REP para facturas PPD
                $requiereRep = false;
                $repTipo = null;
                $repMensaje = null;
                $tieneRep = false;

                // Si hay coincidencia de Póliza, asumimos que el REP y la Póliza están listos
                $statusBadgeClass = 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400';
                $statusBadgeText = '✅ Conciliado (Póliza)';

                if ($matchedPoliza) {
                    $statusBadgeText = '✅ Conciliado (Libros)';
                    // Si la póliza tiene un soporte PPD o UUID, podemos inspeccionar si hay un REP
                    if (!empty($matchedPoliza->cfdi_uuid)) {
                        $xmlSoporte = \App\Models\Cfdi::where('uuid', $matchedPoliza->cfdi_uuid)->first();
                        if ($xmlSoporte && $xmlSoporte->metodo_pago === 'PPD') {
                            $requiereRep = true;
                            $tieneRep = true;
                            $repMensaje = '✅ REP (Complemento de Pago) y Póliza contable correctamente enlazados.';
                        }
                    }
                } elseif ($matchedXml) {
                    // Si cruzó con XML pero no hay póliza contable todavía en el sistema
                    if ($matchedXml->metodo_pago === 'PPD') {
                        $requiereRep = true;
                        $matchedUuid = strtolower(trim($matchedXml->uuid ?? ''));
                        $tieneRep = !empty($matchedUuid) && isset($repMap[$matchedUuid]);

                        if (strtoupper($matchedXml->rfc_emisor) === 'LONJ880321KMA') {
                            $repTipo = 'emitir';
                            if ($tieneRep) {
                                $repMensaje = '✅ REP (Complemento de Pago) ya emitido en SAT (Falta Póliza).';
                                $statusBadgeClass = 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400';
                                $statusBadgeText = '⚡ Cruce XML (Falta Póliza)';
                            } else {
                                $repMensaje = '⚠️ Factura PPD: ¡Falta emitir el REP al cliente y generar la póliza!';
                                $statusBadgeClass = 'bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400';
                                $statusBadgeText = '⚠️ Conciliado (Falta REP)';
                            }
                        } else {
                            $repTipo = 'solicitar';
                            if ($tieneRep) {
                                $repMensaje = '✅ REP (Complemento de Pago) ya recibido de SAT (Falta Póliza).';
                                $statusBadgeClass = 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400';
                                $statusBadgeText = '⚡ Cruce XML (Falta Póliza)';
                            } else {
                                $repMensaje = '⚠️ Factura PPD: ¡Falta solicitar el REP al proveedor y generar la póliza!';
                                $statusBadgeClass = 'bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400';
                                $statusBadgeText = '⚠️ Conciliado (Falta REP)';
                            }
                        }
                    } else {
                        // Es una factura PUE, cruza visualmente pero no hay póliza contable todavía en el sistema
                        $statusBadgeClass = 'bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400';
                        $statusBadgeText = '⚡ Cruce XML (Sin Póliza)';
                    }
                    
                    // Siempre sugerir generar póliza si no la hay (PUE o PPD)
                    $suggestedAction = ($tipo === 'abono') ? 'create_ingreso_directo' : 'create_egreso_directo';
                }

                $movimientosEnriquecidos[] = [
                    'id' => uniqid('mov_'),
                    'fecha' => $fecha,
                    'concepto' => $mov['concepto'] ?? '',
                    'referencia' => $mov['referencia'] ?? '',
                    'tipo' => $tipo,
                    'monto' => $monto,
                    'saldo_posterior' => $mov['saldo_posterior'] ?? 0,
                    'status' => $status,
                    'match_reason' => $matchReason,
                    'poliza_id' => $matchedPoliza ? $matchedPoliza->id : null,
                    'cfdi_id' => $matchedXml ? $matchedXml->id : null,
                    'suggested_action' => $suggestedAction,
                    'cfdi_folio' => $matchedXml ? ($matchedXml->serie . $matchedXml->folio) : ($matchedPoliza ? $matchedPoliza->numero : null),
                    'cfdi_emisor' => $matchedXml ? ($matchedXml->nombre_emisor ?? $matchedXml->rfc_emisor) : ($matchedPoliza ? "Póliza #{$matchedPoliza->numero}" : null),
                    'requiere_rep' => $requiereRep,
                    'rep_tipo' => $repTipo,
                    'rep_mensaje' => $repMensaje,
                    'tiene_rep' => $tieneRep,
                    'status_badge_class' => $statusBadgeClass,
                    'status_badge_text' => $statusBadgeText,
                ];

                // Registrar IDs usados para deduplicación
                if ($matchedXml) $usedXmlIds[] = $matchedXml->id;
                if ($matchedPoliza) $usedPolizaIds[] = $matchedPoliza->id;
            }

            $bankData['movimientos'] = $movimientosEnriquecidos;
            $bankData['resumen_match'] = [
                'total_movimientos' => count($movimientosEnriquecidos),
                'conciliados' => collect($movimientosEnriquecidos)->where('status', 'matched')->count(),
                'pendientes' => collect($movimientosEnriquecidos)->where('status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'banco' => $bankData
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error PDF Banco en Controller: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el estado de cuenta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera al instante una póliza contable para un movimiento bancario suelto (comisiones, transferencias directas sin XML)
     */
    public function generarPolizaBancariaDirecta(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'concepto' => 'required|string',
            'monto' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:cargo,abono',
            'banco_nombre' => 'nullable|string',
            'cfdi_id' => 'nullable|integer'
        ]);

        $empresaId = auth()->user()?->empresa_id ?? 1;
        $monto = (float)$request->input('monto');
        $tipo = $request->input('tipo');
        $concepto = $request->input('concepto');
        $fecha = $request->input('fecha');
        $banco = $request->input('banco_nombre', 'Banco');
        $cfdiId = $request->input('cfdi_id');
        $cfdiUuid = null;

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($cfdiId) {
                $cfdi = \App\Models\Cfdi::find($cfdiId);
                if ($cfdi) {
                    $cfdiUuid = $cfdi->uuid;
                }
            }

            // Buscar cuenta de Banco en el catálogo
            $cuentaBanco = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                ->where('codigo', 'like', '102%')
                ->where('es_detalle', true)
                ->first();

            if (!$cuentaBanco) {
                $cuentaBanco = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)->where('codigo', 'like', '102%')->first();
            }
            if (!$cuentaBanco) {
                throw new \Exception("No se encontró una cuenta contable de Bancos (102) en el catálogo de esta empresa.");
            }

            // Determinar cuenta de contrapartida según concepto
            $conceptoUpper = strtoupper($concepto);
            $cuentaContrapartida = null;

            if (str_contains($conceptoUpper, 'COMISION') || str_contains($conceptoUpper, 'MEMBRESIA') || str_contains($conceptoUpper, 'INTERES')) {
                $cuentaContrapartida = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                    ->where('codigo', 'like', '603%') // Gastos financieros
                    ->where('es_detalle', true)
                    ->first();
                if (!$cuentaContrapartida) {
                    $cuentaContrapartida = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)->where('codigo', 'like', '603%')->first();
                }
            } elseif ($tipo === 'cargo') {
                $cuentaContrapartida = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                    ->where('codigo', 'like', '602%') // Gastos de administración generales
                    ->first();
            } else {
                $cuentaContrapartida = \App\Models\Contab\CuentaContable::where('empresa_id', $empresaId)
                    ->where('codigo', 'like', '401%') // Ventas y servicios directos
                    ->first();
            }

            if (!$cuentaContrapartida) {
                throw new \Exception("No se encontró una cuenta de contrapartida adecuada para el concepto: {$concepto}");
            }

            $tipoPoliza = ($tipo === 'cargo') ? 'egreso' : 'ingreso';

            $poliza = \App\Models\Contab\PolizaContable::create([
                'empresa_id' => $empresaId,
                'tipo' => $tipoPoliza,
                'numero' => \App\Models\Contab\PolizaContable::where('empresa_id', $empresaId)->whereYear('fecha', date('Y', strtotime($fecha)))->where('tipo', $tipoPoliza)->max('numero') + 1,
                'fecha' => $fecha,
                'concepto' => "Movimiento Bancario ({$banco}): {$concepto}",
                'total' => $monto,
                'estado' => 'A', // Activa
                'created_by' => auth()->id(),
                'cfdi_uuid' => $cfdiUuid,
                'cfdi_uuids' => $cfdiUuid ? [$cfdiUuid] : null,
            ]);

            if ($tipo === 'cargo') {
                // Retiro del banco: Debe a Gasto, Haber a Banco
                \App\Models\Contab\AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaContrapartida->id, 'debe' => $monto, 'haber' => 0, 'referencia' => substr($concepto, 0, 100)]);
                \App\Models\Contab\AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaBanco->id, 'debe' => 0, 'haber' => $monto, 'referencia' => substr($concepto, 0, 100)]);
            } else {
                // Depósito al banco: Debe a Banco, Haber a Ingreso/Cliente
                \App\Models\Contab\AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaBanco->id, 'debe' => $monto, 'haber' => 0, 'referencia' => substr($concepto, 0, 100)]);
                \App\Models\Contab\AsientoContable::create(['poliza_id' => $poliza->id, 'cuenta_id' => $cuentaContrapartida->id, 'debe' => 0, 'haber' => $monto, 'referencia' => substr($concepto, 0, 100)]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'poliza' => $poliza,
                'message' => "Póliza de {$tipoPoliza} #{$poliza->numero} generada con éxito."
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
