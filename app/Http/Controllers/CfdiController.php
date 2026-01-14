<?php

namespace App\Http\Controllers;

use App\Models\Cfdi;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Cfdi\CfdiService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Mail;
use App\Mail\FacturaMail;
use App\Models\Proveedor;
use App\Services\Cfdi\CfdiUploadService;
use App\Services\CfdiXmlParserService;
use App\Services\SatDescargaMasivaService;
use App\Models\SatDescargaMasiva;
use App\Jobs\SatDescargaMasivaJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Services\Cfdi\CfdiPdfService;

class CfdiController extends Controller
{
    protected $cfdiService;
    protected $satDescargaService;
    protected $pdfService;

    public function __construct(
        CfdiService $cfdiService,
        SatDescargaMasivaService $satDescargaService,
        CfdiPdfService $pdfService
    ) {
        $this->cfdiService = $cfdiService;
        $this->satDescargaService = $satDescargaService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $query = Cfdi::with(['cliente', 'venta', 'conceptos']);
        $hasDireccionColumn = Schema::hasColumn('cfdis', 'direccion');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('folio', 'like', "%{$search}%")
                    ->orWhere('serie', 'like', "%{$search}%")
                    ->orWhere('nombre_emisor', 'like', "%{$search}%")
                    ->orWhere('rfc_emisor', 'like', "%{$search}%")
                    // Buscar en receptor almacenado en datos_adicionales (JSON) - PostgreSQL syntax
                    ->orWhereRaw("datos_adicionales->'receptor'->>'Nombre' ilike ?", ["%{$search}%"])
                    ->orWhereRaw("datos_adicionales->'receptor'->>'Rfc' ilike ?", ["%{$search}%"]);
            });
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_emision', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_emision', '<=', $request->input('fecha_fin'));
        }

        if ($hasDireccionColumn && $request->filled('direccion')) {
            $query->where('direccion', $request->direccion);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        // --- Filtros Avanzados ---
        if ($request->filled('rfc_emisor')) {
            $query->where('rfc_emisor', 'like', "%{$request->rfc_emisor}%");
        }
        if ($request->filled('rfc_receptor')) {
            $query->where('rfc_receptor', 'like', "%{$request->rfc_receptor}%");
        }
        if ($request->filled('serie')) {
            $query->where('serie', 'like', "%{$request->serie}%");
        }
        if ($request->filled('folio')) {
            $query->where('folio', 'like', "%{$request->folio}%");
        }

        $sort = $request->input('sort');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort) {
            switch ($sort) {
                case 'folio':
                    $query->orderBy('folio', $sortDir);
                    break;
                case 'nombre':
                    $query->orderByRaw("COALESCE(nombre_emisor, nombre_receptor) {$sortDir}");
                    break;
                case 'fecha_emision':
                    $query->orderBy('fecha_emision', $sortDir);
                    break;
                case 'total':
                    $query->orderBy('total', $sortDir);
                    break;
                case 'estatus':
                    $query->orderBy('estatus', $sortDir);
                    break;
                case 'created_at':
                    $query->orderBy('created_at', $sortDir);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Calcular contadores generales
        $contadores = [
            'total' => Cfdi::count(),
            'emitidos' => $hasDireccionColumn ? Cfdi::where('direccion', 'emitido')->count() : 0,
            'recibidos' => $hasDireccionColumn ? Cfdi::where('direccion', 'recibido')->count() : 0,
        ];

        // Obtener historial de descargas masivas
        $descargas = SatDescargaMasiva::orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function (SatDescargaMasiva $descarga) {
                return [
                    'id' => $descarga->id,
                    'direccion' => $descarga->direccion,
                    'fecha_inicio' => $descarga->fecha_inicio ? Carbon::parse($descarga->fecha_inicio)->toDateString() : null,
                    'fecha_fin' => $descarga->fecha_fin ? Carbon::parse($descarga->fecha_fin)->toDateString() : null,
                    'status' => $descarga->status,
                    'total_cfdis' => $descarga->total_cfdis ?? 0,
                    'inserted_cfdis' => $descarga->inserted_cfdis ?? 0,
                    'imported_cfdis' => $descarga->detalles()->where('importado', true)->count(),
                    'pending_cfdis' => $descarga->detalles()->where('importado', false)->count(),
                    'duplicate_cfdis' => $descarga->duplicate_cfdis ?? 0,
                    'error_cfdis' => $descarga->error_cfdis ?? 0,
                    'request_id' => $descarga->request_id,
                    'last_error' => $descarga->last_error,
                    'errors' => $descarga->errors,
                ];
            });

        // =====================================================================
        // ESTADÍSTICAS FINANCIERAS (Basadas en filtros actuales)
        // =====================================================================
        // Clonamos el query para no afectar la paginación
        // Ojo: Si el query es muy complejo o trae muchos resultados, esto impacta performance.
        // Para optimization, se podría hacer una consulta separada con agregados DB directos.

        $statsQuery = clone $query;
        // Quitamos los eager loads para que sea más ligero el count/sum
        $statsQuery->setEagerLoads([]);
        // El query base trae ORDER BY y puede fallar con agregados en Postgres
        $statsQuery->reorder();

        $statsRaw = $statsQuery->selectRaw('
            SUM(CASE WHEN tipo_comprobante = \'I\' THEN total ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo_comprobante = \'E\' THEN total ELSE 0 END) as total_egresos,
            SUM(CASE WHEN tipo_comprobante = \'P\' THEN total ELSE 0 END) as total_pagos,
            COUNT(*) as total_count
        ')->first();

        $stats = [
            'ingresos' => $statsRaw->total_ingresos ?? 0,
            'egresos' => $statsRaw->total_egresos ?? 0,
            'pagos' => $statsRaw->total_pagos ?? 0,
            'count' => $statsRaw->total_count ?? 0,
        ];

        /** @var \Illuminate\Pagination\LengthAwarePaginator $cfdis */
        $cfdis = $query->paginate(15);

        // Obtener RFC de la empresa para determinar dirección dinámica
        $empresaRfc = EmpresaConfiguracion::getConfig()->rfc;

        $cfdis->setCollection($cfdis->getCollection()->map(function ($cfdi) use ($empresaRfc) {
            $rawFechaEmision = $cfdi->getRawOriginal('fecha_emision');
            $hasTimeInEmision = $rawFechaEmision && (Str::contains($rawFechaEmision, ' ') || Str::contains($rawFechaEmision, 'T'));
            $fechaBase = $hasTimeInEmision
                ? $rawFechaEmision
                : ($cfdi->fecha_timbrado?->toDateTimeString() ?? $rawFechaEmision);
            $fechaFormateada = $fechaBase ? Carbon::parse($fechaBase)->format('d/m/Y H:i') : null;

            return [
                'id' => $cfdi->id,
                'uuid' => $cfdi->uuid,
                'folio' => $cfdi->folio ?: $cfdi->uuid,
                'serie' => $cfdi->serie,
                'fecha' => $fechaFormateada,
                'emisor' => $cfdi->nombre_emisor,
                'rfc_emisor' => $cfdi->rfc_emisor,
                // Receptor con fallback a datos_adicionales
                'receptor' => $cfdi->nombre_receptor ?: ($cfdi->datos_adicionales['receptor']['Nombre'] ?? $cfdi->datos_adicionales['receptor']['nombre'] ?? null),
                'rfc_receptor' => $cfdi->rfc_receptor ?: ($cfdi->datos_adicionales['receptor']['Rfc'] ?? $cfdi->datos_adicionales['receptor']['rfc'] ?? null),
                'direccion' => $this->determineDireccion($cfdi, $empresaRfc),
                'total' => $cfdi->total,
                'estado_sat' => $cfdi->estado_sat,
                'estado_sistema' => $cfdi->estado_sistema,
                'tipo_comprobante' => $cfdi->tipo_comprobante,
                'tipo_comprobante_nombre' => match ($cfdi->tipo_comprobante) {
                    'I' => 'Ingreso',
                    'E' => 'Egreso',
                    'P' => 'Pago',
                    'N' => 'Nómina',
                    'T' => 'Traslado',
                    default => 'Otro'
                },
                'venta_id' => $cfdi->venta_id,
                'subtotal' => $cfdi->subtotal,
                'total_impuestos_trasladados' => $cfdi->total_impuestos_trasladados,
                'total_impuestos_retenidos' => $cfdi->total_impuestos_retenidos,
                'metodo_pago' => $cfdi->metodo_pago,
                'forma_pago' => $cfdi->forma_pago,
                'uso_cfdi' => $cfdi->uso_cfdi,
                'moneda' => $cfdi->moneda,
                'conceptos' => $cfdi->conceptos,
                'complementos' => $cfdi->complementos,
                'datos_adicionales' => array_merge($cfdi->datos_adicionales ?? [], [
                    'metodo_pago' => $cfdi->metodo_pago,
                    'forma_pago' => $cfdi->forma_pago,
                    'uso_cfdi' => $cfdi->uso_cfdi,
                ]),
                'tiene_pdf' => Storage::disk('public')->exists('cfdis/' . $cfdi->uuid . '.pdf'),
                'tiene_xml' => Storage::disk('public')->exists('cfdis/' . $cfdi->uuid . '.xml'),
            ];
        }));
        $cfdis->appends($request->query());

        $filters = $request->only([
            'direccion',
            'cliente_id',
            'tipo_comprobante',
            'estatus',
            'search',
            'fecha_inicio',
            'fecha_fin',
            'sort',
            'sort_dir',
            'rfc_emisor',
            'rfc_receptor',
            'serie',
            'folio'
        ]);

        return Inertia::render('Cfdi/Index', [
            'cfdis' => $cfdis,
            'filters' => $filters,
            'contadores' => $contadores,
            'descargasMasivas' => $descargas,
            'stats' => $stats
        ]);
    }

    public function create()
    {
        return Inertia::render('Cfdi/Create', [
            'clientes' => Cliente::all(),
            'productos' => Producto::all(),
            'empresa' => EmpresaConfiguracion::getConfig(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml',
        ]);

        try {
            $uploadService = app(CfdiUploadService::class);
            $cfdi = $uploadService->uploadFromXml($request->file('xml_file'));

            return response()->json([
                'success' => true,
                'message' => 'CFDI cargado correctamente',
                'cfdi' => [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar CFDI', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function descargarXml(Request $request, $uuid)
    {
        $cfdi = Cfdi::where('uuid', $uuid)->first();
        $paths = [
            'cfdis/xml/' . $uuid . '.xml',
            'cfdis/' . $uuid . '.xml',
        ];

        if ($cfdi?->fecha_emision) {
            $date = Carbon::parse($cfdi->fecha_emision);
            $year = $date->format('Y');
            $month = $date->format('m');
            $tipo = $cfdi->direccion === 'recibido' ? 'recibidos' : 'emitidos';
            $paths[] = "cfdis/{$tipo}/{$year}/{$month}/{$uuid}.xml";
            $paths[] = "cfdis/{$tipo}/{$uuid}.xml";
        }

        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                return response()->file(Storage::path($path), [
                    'Content-Type' => 'application/xml; charset=utf-8',
                    'Content-Disposition' => $disposition . '; filename="' . $uuid . '.xml"',
                ]);
            }
            if (Storage::disk('public')->exists($path)) {
                return response()->file(Storage::disk('public')->path($path), [
                    'Content-Type' => 'application/xml; charset=utf-8',
                    'Content-Disposition' => $disposition . '; filename="' . $uuid . '.xml"',
                ]);
            }
        }

        if ($cfdi && !empty($cfdi->xml_content)) {
            return response($cfdi->xml_content)
                ->header('Content-Type', 'application/xml; charset=utf-8')
                ->header('Content-Disposition', $disposition . '; filename="' . $uuid . '.xml"');
        }

        return abort(404, 'XML no encontrado');
    }

    public function verPdf($uuid)
    {
        $cfdi = Cfdi::where('uuid', $uuid)->first();
        $paths = [
            'cfdis/pdf/' . $uuid . '.pdf',
            'cfdis/' . $uuid . '.pdf',
        ];

        if ($cfdi && $cfdi->pdf_url) {
            $paths[] = ltrim($cfdi->pdf_url, '/');
        }

        $download = request()->boolean('download');

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                return $download ?
                    response()->download(Storage::path($path), $uuid . '.pdf') :
                    response()->file(Storage::path($path));
            }
            if (Storage::disk('public')->exists($path)) {
                return $download
                    ? response()->download(Storage::disk('public')->path($path), $uuid . '.pdf')
                    : response()->file(Storage::disk('public')->path($path));
            }
        }

        return $this->generarPdfAlVuelo($uuid, $download);
    }

    public function verPdfView($uuid)
    {
        $cfdiRecord = Cfdi::where('uuid', $uuid)->firstOrFail();

        return view('ventas.cfdi_pdf_view', [
            'uuid' => $uuid,
            'pdf_url' => route('cfdi.ver-pdf', $uuid),
            'folio' => $cfdiRecord->folio ?? null,
            'tipo' => $cfdiRecord->tipo_comprobante === 'P' ? 'REP' : 'CFDI',
        ]);
    }

    public function previewXml(Request $request, CfdiUploadService $uploadService, CfdiXmlParserService $parserService)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml',
        ]);

        try {
            $preview = $uploadService->previewXml($request->file('xml_file'));
            $data = $preview['data'] ?? [];

            $proveedor = null;
            if (!empty($data['emisor']['rfc'])) {
                $proveedor = $parserService->findProveedorByRfc($data['emisor']['rfc']);
            }

            if (!empty($preview['existing_cfdi']['id'])) {
                $data['id'] = $preview['existing_cfdi']['id'];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $data,
                    'is_duplicate' => $preview['is_duplicate'] ?? false,
                    'existing_cfdi' => $preview['existing_cfdi'] ?? null,
                    'emisor_exists' => $proveedor !== null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function checkSatStatus($id)
    {
        $cfdi = Cfdi::findOrFail($id);

        try {
            $res = $this->cfdiService->consultarEstadoSat($cfdi->uuid, (float) $cfdi->total, $cfdi->rfc_emisor, $cfdi->rfc_receptor);

            $cfdi->update([
                'estado_sat' => $res['estado'],
                'es_cancelable' => $res['es_cancelable'],
                'estatus_cancelacion' => $res['estatus_cancelacion'],
                'ultima_verificacion_sat' => now()
            ]);

            return response()->json([
                'success' => true,
                'status' => $res['estado'],
                'message' => 'Saturación del SAT consultada correctamente.'
            ]);
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al consultar SAT: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error al consultar SAT: ' . $e->getMessage());
        }
    }

    public function solicitarCancelacion(Request $request, $id)
    {
        $cfdi = Cfdi::findOrFail($id);
        $motivo = $request->input('motivo', '02');
        $sustitucion = $request->input('uuid_sustitucion');

        try {
            $this->cfdiService->cancelar($cfdi->uuid, $motivo, $sustitucion);
            return back()->with('success', 'Solicitud de cancelación enviada');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cancelar: ' . $e->getMessage());
        }
    }

    public function importar(Request $request)
    {
        $request->validate([
            'xml_file' => 'required|file|mimes:xml',
            'direccion' => 'required|in:emitido,recibido',
        ]);

        try {
            $this->cfdiService->importarXml($request->file('xml_file'), $request->direccion);
            return back()->with('success', 'CFDI importado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function show(Cfdi $cfdi)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $cfdi->load(['cliente', 'venta']),
            ]);
        }

        return redirect()->route('cfdi.index');
    }

    public function verXmlView(Request $request, $uuid)
    {
        $request->merge(['inline' => true]);

        return $this->descargarXml($request, $uuid);
    }

    public function enviarCorreo(Request $request, $uuid)
    {
        $cfdi = Cfdi::where('uuid', $uuid)->firstOrFail();
        $email = $request->input('email') ?? $cfdi->venta?->cliente?->email;

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => 'El cliente no tiene email configurado.',
            ], 422);
        }

        try {
            Mail::to($email)->send(new FacturaMail($cfdi));

            return response()->json([
                'success' => true,
                'message' => "Correo enviado a {$email}.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Cfdi $cfdi)
    {
        $uuid = $cfdi->uuid;

        $paths = [
            "cfdis/xml/{$uuid}.xml",
            "cfdis/pdf/{$uuid}.pdf",
            "cfdis/{$uuid}.xml",
            "cfdis/{$uuid}.pdf",
        ];

        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $cfdi->delete();

        return response()->json([
            'success' => true,
            'message' => 'CFDI eliminado correctamente.',
        ]);
    }

    public function bulkCheckSatStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:cfdis,id'
        ]);

        $ids = $request->input('ids');
        $successCount = 0;
        $errorCount = 0;

        foreach ($ids as $id) {
            try {
                $cfdi = Cfdi::findOrFail($id);
                $status = $this->cfdiService->consultarEstadoSat($cfdi->uuid, (float) $cfdi->total, $cfdi->rfc_emisor, $cfdi->rfc_receptor);

                $cfdi->update([
                    'estado_sat' => $status['estado'],
                    'es_cancelable' => $status['es_cancelable'] ?? null,
                    'estatus_cancelacion' => $status['estatus_cancelacion'] ?? null,
                    'ultima_verificacion_sat' => now(),
                ]);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proceso completado. Actualizados: {$successCount}, Errores: {$errorCount}",
        ]);
    }

    public function bulkSendEmail(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:cfdis,id'
        ]);

        $ids = $request->input('ids');
        $successCount = 0;
        $errorCount = 0;

        foreach ($ids as $id) {
            try {
                $cfdi = Cfdi::with('venta.cliente')->findOrFail($id);
                $email = $cfdi->venta?->cliente?->email;

                if (empty($email)) {
                    $errorCount++;
                    continue;
                }

                Mail::to($email)->send(new FacturaMail($cfdi));
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proceso completado. Enviados: {$successCount}, Errores: {$errorCount}",
        ]);
    }

    public function bulkDownload(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:cfdis,id'
        ]);

        $ids = $request->input('ids');
        $cfdis = Cfdi::whereIn('id', $ids)->get();

        if ($cfdis->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No se encontraron CFDIs'], 404);
        }

        // Crear ZIP temporal
        $zipFileName = 'cfdis_bulk_' . time() . '.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);

        // Asegurar que exista el directorio temp
        if (!Storage::disk('public')->exists('temp')) {
            Storage::disk('public')->makeDirectory('temp');
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return response()->json(['success' => false, 'message' => 'No se pudo crear el archivo ZIP'], 500);
        }

        $addedCount = 0;

        foreach ($cfdis as $cfdi) {
            // == Agregar XML ==
            $xmlContent = $this->pdfService->getXmlContent($cfdi);

            if ($xmlContent) {
                $zip->addFromString($cfdi->uuid . '.xml', $xmlContent);
                $addedCount++;
            }

            // == Agregar PDF ==
            try {
                $pdfContent = $this->pdfService->generatePdfContent($cfdi, $xmlContent);
                if ($pdfContent) {
                    $zip->addFromString($cfdi->uuid . '.pdf', $pdfContent);
                }
            } catch (\Exception $e) {
                // Log error pero continuar
                Log::warning("Fallo al generar PDF para ZIP uuid: {$cfdi->uuid} - {$e->getMessage()}");
            }
        }

        $zip->close();

        if ($addedCount === 0) {
            return response()->json(['success' => false, 'message' => 'No se pudieron procesar los archivos para el ZIP'], 422);
        }

        // Retornar URL de descarga
        return response()->json([
            'success' => true,
            'url' => Storage::url('temp/' . $zipFileName)
        ]);
    }




    public function createProviderFromCfdi(string $uuid)
    {
        $cfdi = Cfdi::where('id', $uuid)->orWhere('uuid', $uuid)->first();

        if (!$cfdi) {
            return response()->json([
                'success' => false,
                'message' => 'CFDI no encontrado.',
            ], 404);
        }

        if (empty($cfdi->rfc_emisor)) {
            return response()->json([
                'success' => false,
                'message' => 'El CFDI no tiene RFC del emisor.',
            ], 422);
        }

        $existing = Proveedor::where('rfc', $cfdi->rfc_emisor)->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'El proveedor ya existe.',
                'proveedor_id' => $existing->id,
            ]);
        }

        $rfc = strtoupper(trim($cfdi->rfc_emisor));
        $tipoPersona = strlen($rfc) === 12 ? 'moral' : 'fisica';

        $proveedor = Proveedor::create([
            'nombre_razon_social' => $cfdi->nombre_emisor ?: $rfc,
            'tipo_persona' => $tipoPersona,
            'rfc' => $rfc,
            'regimen_fiscal' => $cfdi->regimen_fiscal_emisor,
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proveedor creado correctamente.',
            'proveedor_id' => $proveedor->id,
        ]);
    }

    /**
     * Muestra la vista para crear una nueva descarga masiva
     */
    public function solicitarDescargaMasiva(Request $request)
    {
        return $this->solicitarDescargaMasivaNueva($request);
    }

    /**
     * Verifica el estado de una descarga masiva y procesa si está lista
     */
    public function verificarDescargaMasiva($id)
    {
        return $this->verificarDescargaMasivaNueva($id);
    }

    /**
     * Revisa los documentos descargados (Staging Area)
     */
    public function getDescargaDetalles($id)
    {
        $descarga = SatDescargaMasiva::with('detalles')->findOrFail($id);
        // Obtener todos los detalles, ordenando los no importados primero
        $detalles = $descarga->detalles()->orderBy('importado', 'asc')->orderBy('fecha_emision', 'desc')->get();

        $duplicados = [];
        $errors = $descarga->errors;
        $dupUuids = [];
        if (is_array($errors) && array_key_exists('duplicates', $errors)) {
            $dupUuids = array_values(array_unique(array_filter($errors['duplicates'] ?? [])));
        }

        if (!empty($dupUuids)) {
            $cfdis = Cfdi::whereIn('uuid', $dupUuids)->get()->keyBy('uuid');
            foreach ($dupUuids as $uuid) {
                $cfdi = $cfdis->get($uuid);
                if (!$cfdi) {
                    continue;
                }
                $receptor = $cfdi->datos_adicionales['receptor'] ?? [];
                $duplicados[] = [
                    'id' => $cfdi->id,
                    'uuid' => $cfdi->uuid,
                    'direccion' => $cfdi->direccion,
                    'fecha_emision' => $cfdi->fecha_emision,
                    'rfc_emisor' => $cfdi->rfc_emisor,
                    'nombre_emisor' => $cfdi->nombre_emisor,
                    'rfc_receptor' => $receptor['rfc'] ?? $receptor['Rfc'] ?? null,
                    'nombre_receptor' => $receptor['nombre'] ?? $receptor['Nombre'] ?? null,
                    'tipo_comprobante' => $cfdi->tipo_comprobante,
                    'total' => $cfdi->total,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'descarga' => $descarga,
            'detalles' => $detalles,
            'duplicados' => $duplicados,
        ]);
    }

    /**
     * Importa documentos seleccionados desde Staging a la tabla principal
     */
    public function importarSeleccionados(Request $request)
    {
        return $this->importarSeleccionadosNueva($request);
    }

    private function solicitarDescargaMasivaNueva(Request $request)
    {
        $validated = $request->validate([
            'direccion' => 'required|in:emitido,recibido',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $config = EmpresaConfiguracion::getConfig();
        if (!$config || empty($config->fiel_cer_path) || empty($config->fiel_key_path) || empty($config->fiel_password)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una FIEL configurada para la descarga masiva.',
            ], 422);
        }

        // Cap fecha_fin a hoy si es futura (SAT no acepta fechas futuras)
        $fechaFin = Carbon::parse($validated['fecha_fin']);
        $today = Carbon::today();
        if ($fechaFin->gt($today)) {
            $fechaFin = $today;
        }

        $descargas = $this->satDescargaService->crearSolicitudesPorRango(
            $validated['direccion'],
            Carbon::parse($validated['fecha_inicio']),
            $fechaFin,
            $request->user()?->id
        );

        foreach ($descargas as $descarga) {
            SatDescargaMasivaJob::dispatch($descarga->id, 'request');
        }

        return response()->json([
            'success' => true,
            'message' => count($descargas) > 1
                ? 'Se enviaron ' . count($descargas) . ' solicitudes. Podras consultar el resultado en unos minutos.'
                : 'Solicitud enviada. Podras consultar el resultado en unos minutos.',
            'descarga_id' => $descargas[0]->id ?? null,
            'descarga_ids' => collect($descargas)->pluck('id')->all(),
        ]);
    }

    private function verificarDescargaMasivaNueva(int $id)
    {
        $descarga = SatDescargaMasiva::findOrFail($id);
        SatDescargaMasivaJob::dispatch($descarga->id, 'verify');

        return response()->json([
            'success' => true,
            'message' => 'Consulta en proceso. Revisa el estatus en unos momentos.',
        ]);
    }

    private function importarSeleccionadosNueva(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sat_descarga_detalles,id'
        ]);

        try {
            $stats = $this->satDescargaService->importarDesdeStaging($request->ids);
            return response()->json([
                'success' => true,
                'message' => "Importacion completada. Insertados: {$stats['inserted']}, Errores: {$stats['errors']}",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al importar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function eliminarDescargaMasiva(SatDescargaMasiva $descarga)
    {
        $descarga->delete();

        return response()->json([
            'success' => true,
            'message' => 'Descarga masiva eliminada.',
        ]);
    }

    public function revalidarDescargaMasiva(SatDescargaMasiva $descarga)
    {
        SatDescargaMasivaJob::dispatch($descarga->id, 'recheck');

        return response()->json([
            'success' => true,
            'message' => 'Revalidacion SAT en proceso. Revisa en unos momentos.',
        ]);
    }

    /**
     * Reintentar descarga masiva manualmente (para estados pausado/esperando)
     * Esto reinicia los contadores y dispara un nuevo Job
     */
    public function reintentarDescargaMasiva(SatDescargaMasiva $descarga)
    {
        // Solo permitir reintentar si está pausado o esperando
        if (!in_array($descarga->status, ['pausado', 'esperando', 'error'])) {
            return response()->json([
                'success' => false,
                'message' => 'Esta descarga no puede reintentarse en su estado actual.',
            ], 422);
        }

        // Reiniciar contadores para dar otro ciclo de reintentos
        $descarga->update([
            'status' => 'pendiente',
            'retry_count' => 0,
            'next_retry_at' => null,
            'limite_tipo' => null,
            'mensaje_usuario' => null,
            'last_error' => null,
        ]);

        // Disparar nuevo Job
        SatDescargaMasivaJob::dispatch($descarga->id, 'request');

        return response()->json([
            'success' => true,
            'message' => 'Reintento iniciado. Se procesará en segundo plano.',
        ]);
    }

    /**
     * Previsualizar PDF para un documento en staging
     */
    public function previewStagingPdf(Request $request, $id)
    {
        try {
            $detalle = \App\Models\SatDescargaDetalle::findOrFail($id);
            $xmlContent = $detalle->xml_content;

            // 3. Parsear XML para obtener datos (limpiando namespaces para facilitar acceso)
            $xml = simplexml_load_string($xmlContent);
            $ns = $xml->getNamespaces(true);

            // Detectar namespace cfdi (puede ser 'cfdi' o el namespace por defecto)
            $cfdiNs = $ns['cfdi'] ?? $ns[''] ?? 'http://www.sat.gob.mx/cfd/4';
            $xml->registerXPathNamespace('cfdi', $cfdiNs);

            // Registrar tfd
            $tfdNs = $ns['tfd'] ?? 'http://www.sat.gob.mx/TimbreFiscalDigital';
            $xml->registerXPathNamespace('tfd', $tfdNs);

            // Registrar namespaces de pagos para evitar errores
            if (isset($ns['pago10']))
                $xml->registerXPathNamespace('pago10', $ns['pago10']);
            if (isset($ns['pago20']))
                $xml->registerXPathNamespace('pago20', $ns['pago20']);
            if (isset($ns['pago']))
                $xml->registerXPathNamespace('pago', $ns['pago']);

            // Helper para convertir SimpleXML a array
            $comprobante = json_decode(json_encode($xml), true);

            // Extraer Timbre Fiscal
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $timbre = count($tfd) > 0 ? json_decode(json_encode($tfd[0]), true) : [];

            $uuid = $timbre['@attributes']['UUID'] ?? $detalle->uuid;

            // Reconstruir $data para la vista
            $data = [
                'uuid' => $uuid,
                'fechaTimbrado' => $timbre['@attributes']['FechaTimbrado'] ?? '',
                'selloCFDI' => $timbre['@attributes']['SelloCFD'] ?? '',
                'selloSAT' => $timbre['@attributes']['SelloSAT'] ?? '',
                'noCertificadoSAT' => $timbre['@attributes']['NoCertificadoSAT'] ?? '',
                'cadenaOriginal' => '||' . $uuid . '||'
            ];

            // Reconstruir estructura para vista con XPath para evitar problemas de namespaces
            $emisorNode = $xml->xpath('//cfdi:Emisor');
            $receptorNode = $xml->xpath('//cfdi:Receptor');
            $conceptosNode = $xml->xpath('//cfdi:Conceptos/cfdi:Concepto');

            $emisor = count($emisorNode) > 0 ? json_decode(json_encode($emisorNode[0]), true)['@attributes'] : [];
            $receptor = count($receptorNode) > 0 ? json_decode(json_encode($receptorNode[0]), true)['@attributes'] : [];

            // Procesar Conceptos
            $conceptos = [];
            foreach ($conceptosNode as $node) {
                $asArray = json_decode(json_encode($node), true);
                $conceptos[] = $asArray['@attributes'] ?? $asArray;
            }

            $comprobanteAttrs = $comprobante['@attributes'] ?? $comprobante;
            $comprobanteAttrs['Conceptos'] = $conceptos;

            // Extraer Impuestos
            $impuestosNode = $xml->xpath('//cfdi:Impuestos');
            if (count($impuestosNode) > 0) {
                $impGen = json_decode(json_encode($impuestosNode[0]), true);
                $comprobanteAttrs['Impuestos'] = $impGen['@attributes'] ?? $impGen;
            }

            // Extraer Complemento de Pago
            $pagosNode = $xml->xpath('//pago10:Pagos') ?: $xml->xpath('//pago20:Pagos');
            if (count($pagosNode) > 0) {
                $pagosData = json_decode(json_encode($pagosNode[0]), true);
                // Normalizar estructura si es un solo pago
                if (isset($pagosData['Pago']['@attributes'])) {
                    $pagosData['Pago'] = [$pagosData['Pago']];
                } elseif (isset($pagosData['pago20:Pago']['@attributes'])) { // Namespace prefix variations
                    $pagosData['Pago'] = [$pagosData['pago20:Pago']];
                }
                $comprobanteAttrs['ComplementoPago'] = $pagosData;
            }

            // Mock del objeto CFDI
            $cfdiRecord = new Cfdi([
                'uuid' => $uuid,
                'serie' => $comprobanteAttrs['Serie'] ?? null,
                'folio' => $comprobanteAttrs['Folio'] ?? null,
                'tipo_comprobante' => $comprobanteAttrs['TipoDeComprobante'] ?? 'I',
                'metodo_pago' => $comprobanteAttrs['MetodoPago'] ?? null,
                'forma_pago' => $comprobanteAttrs['FormaPago'] ?? null,
                'moneda' => $comprobanteAttrs['Moneda'] ?? 'MXN',
                'total' => $comprobanteAttrs['Total'] ?? 0,
            ]);
            $cfdiRecord->estado_sat = 'Vigente';

            // Configuración de empresa
            $empresaConfig = EmpresaConfiguracion::getConfig();
            $empresaData = $empresaConfig ? $empresaConfig->toArray() : [];

            // QR Code
            $totalQR = $comprobanteAttrs['Total'] ?? 0;
            $totalQR = str_pad(number_format((float) $totalQR, 6, '.', ''), 17, '0', STR_PAD_LEFT);

            $qr_url_string = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=" . $uuid . "&re=" . ($emisor['Rfc'] ?? '') . "&rr=" . ($receptor['Rfc'] ?? '') . "&tt=" . $totalQR . "&fe=" . substr($data['selloCFDI'] ?? '', -8);

            $qrBase64 = null;
            try {
                $renderer = new ImageRenderer(
                    new RendererStyle(300, 1),
                    new SvgImageBackEnd()
                );
                $writer = new Writer($renderer);
                $qrSvg = $writer->writeString($qr_url_string);
                $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
            } catch (\Exception $e) {
            }
            // Para REPs (tipo P), usar plantilla especializada o adaptar la genérica
            $viewName = ($cfdiRecord->tipo_comprobante === 'P') ? 'ventas.cfdi_pdf_pago' : 'ventas.cfdi_pdf';

            // Verificar si existe la vista, si no usar genérica
            if (!View::exists($viewName)) {
                $viewName = 'ventas.cfdi_pdf';
            }

            $pdf = Pdf::loadView($viewName, [
                'venta' => null,
                'cfdi' => $cfdiRecord,
                'comprobante' => $comprobanteAttrs,
                'emisor' => $emisor,
                'receptor' => $receptor,
                'data' => $data,
                'qr_url' => $qr_url_string,
                'qr_base64' => $qrBase64,
                'empresa' => $empresaData,
                'color_principal' => '#10b981'
            ]);

            $pdf->setOptions(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
            $pdf->setPaper('letter', 'portrait');

            return $pdf->stream($uuid . '.pdf');

        } catch (\Exception $e) {
            Log::error("Error generating staging PDF: " . $e->getMessage());
            return response("Error generando PDF: " . $e->getMessage(), 500);
        }
    }

    // Método para generar PDF al vuelo de CFDI importado
    private function generarPdfAlVuelo($uuid, bool $download = false)
    {
        $cfdiRecord = Cfdi::where('uuid', $uuid)->first();
        if (!$cfdiRecord) {
            return abort(404, 'CFDI no encontrado');
        }

        $pdfContent = $this->pdfService->generatePdfContent($cfdiRecord);

        if (!$pdfContent) {
            return abort(500, 'Error al generar el PDF');
        }

        if ($download) {
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $uuid . '.pdf');
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $uuid . '.pdf"'
        ]);
    }
    /**
     * Elimina un registro de descarga masiva
     */


    // =====================================================================
    // MÉTODOS PARA CREAR CUENTAS POR PAGAR/COBRAR DESDE CFDI
    // =====================================================================

    /**
     * Preparar datos del CFDI para el modal de creación de cuenta
     */
    public function prepararCuenta(Cfdi $cfdi)
    {
        try {
            $service = app(\App\Services\Cfdi\CfdiCuentasService::class);
            $datos = $service->prepararDatosCuenta($cfdi);
            return response()->json(['success' => true, 'data' => $datos]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear cuenta por pagar desde CFDI recibido
     */
    public function crearCuentaPorPagar(Request $request, Cfdi $cfdi)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'fecha_vencimiento' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
        ]);
        try {
            $service = app(\App\Services\Cfdi\CfdiCuentasService::class);
            $proveedor = Proveedor::findOrFail($request->proveedor_id);
            $cuenta = $service->crearCuentaPorPagar($cfdi, $proveedor, [
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'notas' => $request->notas,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Cuenta por pagar creada correctamente.',
                'cuenta' => [
                    'id' => $cuenta->id,
                    'monto_total' => $cuenta->monto_total,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento ? Carbon::parse($cuenta->fecha_vencimiento)->format('Y-m-d') : null
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear cuenta por cobrar desde CFDI emitido
     */
    public function crearCuentaPorCobrar(Request $request, Cfdi $cfdi)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha_vencimiento' => 'nullable|date',
            'notas' => 'nullable|string|max:1000',
        ]);
        try {
            $service = app(\App\Services\Cfdi\CfdiCuentasService::class);
            $cliente = Cliente::findOrFail($request->cliente_id);
            $cuenta = $service->crearCuentaPorCobrar($cfdi, $cliente, [
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'notas' => $request->notas,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Cuenta por cobrar creada correctamente.',
                'cuenta' => [
                    'id' => $cuenta->id,
                    'monto_total' => $cuenta->monto_total,
                    'fecha_vencimiento' => $cuenta->fecha_vencimiento ? Carbon::parse($cuenta->fecha_vencimiento)->format('Y-m-d') : null
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear proveedor durante el flujo de creación de cuenta desde CFDI
     */
    public function crearProveedorDesdeCfdiCuenta(Request $request)
    {
        $request->validate([
            'rfc' => 'required|string|max:13|unique:proveedores,rfc',
            'nombre_razon_social' => 'required|string|max:255',
        ]);
        try {
            $service = app(\App\Services\Cfdi\CfdiCuentasService::class);
            $proveedor = $service->crearProveedorDesdeCfdi($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado correctamente.',
                'proveedor' => ['id' => $proveedor->id, 'nombre' => $proveedor->nombre_razon_social, 'rfc' => $proveedor->rfc]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear cliente durante el flujo de creación de cuenta desde CFDI
     */
    public function crearClienteDesdeCfdiCuenta(Request $request)
    {
        $request->validate([
            'rfc' => 'required|string|max:13|unique:clientes,rfc',
            'nombre' => 'required|string|max:255',
        ]);
        try {
            $service = app(\App\Services\Cfdi\CfdiCuentasService::class);
            $cliente = $service->crearClienteDesdeCfdi($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente.',
                'cliente' => ['id' => $cliente->id, 'nombre' => $cliente->nombre ?? $cliente->razon_social, 'rfc' => $cliente->rfc]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    private function determineDireccion($cfdi, $empresaRfc)
    {
        // Si no hay RFC de empresa, usar el valor de base de datos como fallback
        if (!$empresaRfc)
            return $cfdi->direccion;

        $rfcEmisor = $cfdi->rfc_emisor;
        // Normalizar RFCs
        $empresaRfc = strtoupper(trim($empresaRfc));
        $rfcEmisor = strtoupper(trim((string) $rfcEmisor));

        if ($rfcEmisor === $empresaRfc) {
            return 'emitido';
        }

        // Si no soy el emisor, asumo recibido (gastos, nómina recibida, etc)
        // Podría validar también contra rfc_receptor, pero el default recibido es más seguro para visualización
        return 'recibido';
    }
}
