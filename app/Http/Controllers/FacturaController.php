<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Venta;
use App\Models\Cliente;
use App\Services\ContpaqiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturaController extends Controller
{
    use Concerns\ConfiguracionEmpresa;

    public function __construct(
        private readonly \App\Services\PdfGeneratorService $pdfService,
        private readonly \App\Services\Cfdi\CfdiPdfService $cfdiPdfService,
        protected ContpaqiService $contpaqiService
    ) {
    }

    /**
     * Mostrar listado de facturas
     */
    public function index(Request $request)
    {
        $query = Factura::with(['cliente', 'ventas'])
            ->orderByDesc('created_at');

        // Filtros
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_factura', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente', fn($c) => $c->where('nombre_razon_social', 'like', "%{$buscar}%"));
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_emision', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->hasta);
        }

        $facturas = $query->paginate(20)->withQueryString();

        // Estadísticas
        $stats = [
            'total' => Factura::count(),
            'pendientes' => Factura::where('estado', 'enviada')->count(),
            'pagadas' => Factura::where('estado', 'pagada')->count(),
            'canceladas' => Factura::where('estado', 'cancelada')->count(),
            'monto_pendiente' => Factura::where('estado', 'enviada')->sum('total'),
        ];

        return Inertia::render('Facturas/Index', [
            'facturas' => $facturas,
            'filtros' => $request->only(['buscar', 'estado', 'desde', 'hasta']),
            'stats' => $stats,
        ]);
    }

    /**
     * Formulario para crear factura agrupada
     */
    public function create(Request $request)
    {
        // Obtener clientes (todos, para el buscador)
        $clientes = Cliente::where('activo', true)
            ->select('id', 'nombre_razon_social', 'rfc', 'regimen_fiscal', 'uso_cfdi', 'forma_pago_default')
            ->orderBy('nombre_razon_social')
            ->limit(1000)
            ->get();

        // Si se selecciona un cliente, obtener sus ventas pendientes
        $ventasPendientes = collect();
        $clienteSeleccionado = null;
        $datosPrellenado = [];

        // Pre-carga por Venta ID (desde botón "Facturar" en Ventas/Index)
        if ($request->filled('venta_id')) {
            $venta = Venta::find($request->venta_id);
            if ($venta && !$request->filled('cliente_id')) {
                $request->merge(['cliente_id' => $venta->cliente_id]);
            }
            if ($venta) {
                $datosPrellenado = [
                    'forma_pago' => $venta->forma_pago_sat ?? ($venta->metodo_pago === 'efectivo' ? '01' : '99'),
                    'metodo_pago' => $venta->metodo_pago_sat ?? ($venta->pagado ? 'PUE' : 'PPD'),
                    'uso_cfdi' => $venta->uso_cfdi // Si la venta lo tuviera guardado
                ];
            }
        }

        if ($request->filled('cliente_id')) {
            $clienteSeleccionado = Cliente::with(['regimen', 'uso'])->find($request->cliente_id);

            $ventasPendientes = Venta::where('cliente_id', $request->cliente_id)
                ->whereNull('factura_id')
                ->where('estado', '!=', 'cancelada')
                ->with(['items.ventable'])
                ->orderByDesc('fecha')
                ->get()
                ->map(fn($v) => [
                    'id' => $v->id,
                    'folio' => $v->numero_venta ?? $v->id,
                    'fecha' => $v->fecha ? $v->fecha->format('d/m/Y') : '-',
                    'subtotal' => $v->subtotal,
                    'iva' => $v->iva,
                    'total' => $v->total,
                    'items_count' => $v->items->count(),
                    'descripcion' => $v->items->isNotEmpty()
                        ? ($v->items->first()->ventable?->nombre ?? 'Sin descripción')
                        : 'Sin productos',
                    'pagado' => (bool) $v->pagado,
                    'metodo_pago_sugerido' => $v->pagado ? 'PUE' : 'PPD',
                    'forma_pago_sugerida' => $v->forma_pago_sat ?? ($v->metodo_pago === 'efectivo' ? '01' : ($v->pagado ? '03' : '99')), // Default simple
                    'etiqueta_pago' => $v->pagado ? 'Pagado (' . ($v->metodo_pago ?? 'N/A') . ')' : 'Pendiente de Pago',
                    'selected' => $request->venta_id == $v->id
                ]);
        }

        // Catálogos necesarios
        $catalogos = [
            'regimenes' => \App\Models\SatRegimenFiscal::orderBy('clave')->get(),
            'usosCfdi' => \App\Models\SatUsoCfdi::where('activo', true)->orderBy('clave')->get(),
            'formasPago' => [
                ['clave' => '01', 'descripcion' => 'Efectivo'],
                ['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos'],
                ['clave' => '04', 'descripcion' => 'Tarjeta de crédito'],
                ['clave' => '28', 'descripcion' => 'Tarjeta de débito'],
                ['clave' => '99', 'descripcion' => 'Por definir'],
            ],
            'metodosPago' => [
                ['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición'],
                ['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido'],
            ],
        ];

        return Inertia::render('Facturas/Create', [
            'clientes' => $clientes,
            'clienteSeleccionado' => $clienteSeleccionado,
            'ventasPendientes' => $ventasPendientes,
            'catalogos' => $catalogos,
            'ventaPreseleccionada' => $request->venta_id,
            'datosPrellenado' => $datosPrellenado,
        ]);
    }

    /**
     * Procesar y timbrar factura agrupada
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ventas_ids' => 'required|array|min:1',
            'ventas_ids.*' => 'exists:ventas,id',
            'uso_cfdi' => 'required|string',
            'forma_pago' => 'required|string',
            'metodo_pago' => 'required|string|in:PUE,PPD',
            'regimen_fiscal' => 'required|string', // Validar régimen
            'codigo_postal' => 'required|string', // Validar CP (CFDI 4.0)
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        // Actualizar datos del cliente (Régimen y CP) inmediatamente, 
        // para que persistan aunque falle el timbrado posterior.
        $datosUpdate = [];
        if ($cliente->regimen_fiscal !== $validated['regimen_fiscal']) {
            $datosUpdate['regimen_fiscal'] = $validated['regimen_fiscal'];
        }
        if ($cliente->domicilio_fiscal_cp !== $validated['codigo_postal']) {
            $datosUpdate['domicilio_fiscal_cp'] = $validated['codigo_postal'];
            $datosUpdate['codigo_postal'] = $validated['codigo_postal'];
        }

        if (!empty($datosUpdate)) {
            $cliente->update($datosUpdate);
        }

        DB::beginTransaction();
        try {
            // Validación Estricta: Pertenencia y Estado
            $ventas = Venta::whereIn('id', $validated['ventas_ids'])
                ->lockForUpdate() // Bloquear filas para evitar condiciones de carrera
                ->get();

            // 1. Verificar cantidad
            if ($ventas->count() !== count($validated['ventas_ids'])) {
                DB::rollBack();
                return back()->with('error', 'Algunas ventas seleccionadas no existen.');
            }

            // 2. Verificar integridad
            foreach ($ventas as $venta) {
                if ($venta->cliente_id != $cliente->id) {
                    DB::rollBack();
                    return back()->with('error', "La venta #{$venta->numero_venta} no pertenece al cliente seleccionado.");
                }
                if ($venta->factura_id) {
                    DB::rollBack();
                    return back()->with('error', "La venta #{$venta->numero_venta} ya ha sido facturada.");
                }
                if ($venta->estado === 'cancelada') {
                    DB::rollBack();
                    return back()->with('error', "La venta #{$venta->numero_venta} está cancelada.");
                }
            }

            // Calcular totales
            $subtotal = $ventas->sum('subtotal');
            $iva = $ventas->sum('iva');
            $total = $ventas->sum('total');

            // Crear factura local primero (solo columnas que existen en la tabla)
            $factura = Factura::create([
                'empresa_id' => $cliente->empresa_id ?? auth()->user()->empresa_id,
                'cliente_id' => $cliente->id,
                'fecha_emision' => now(),
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'estado' => 'borrador',
                'notas' => $validated['observaciones'] ?? null,
            ]);

            // Vincular ventas a la factura
            foreach ($ventas as $venta) {
                $venta->factura_id = $factura->id;
                $venta->forma_pago_sat = $validated['forma_pago'];
                $venta->metodo_pago_sat = $validated['metodo_pago'];
                $venta->save();
            }

            DB::commit();

            // Intentar timbrar fuera de la transacción
            if (config('services.contpaqi.enabled', false)) {
                return $this->timbrar($factura);
            }

            return redirect()
                ->route('facturas.show', $factura)
                ->with('warning', 'Factura guardada como borrador (Timbrado deshabilitado o no intentado).');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creando factura: " . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error al crear la factura: ' . $e->getMessage());
        }
    }

    /**
     * Crear factura agrupada en CONTPAQi y registrar CFDI
     */
    protected function crearFacturaAgrupada(Factura $factura, $ventas)
    {
        // 1. Sincronizar
        $this->contpaqiService->syncCliente($factura->cliente);
        foreach ($ventas as $venta) {
            foreach ($venta->items as $item) {
                if ($item->ventable) {
                    $this->contpaqiService->syncItem($item->ventable);
                }
            }
        }

        $productosPayload = [];
        foreach ($ventas as $venta) {
            foreach ($venta->items as $item) {
                $ventable = $item->ventable;
                if (!$ventable)
                    continue;

                $productosPayload[] = [
                    "codigo" => $ventable->codigo,
                    "nombre" => substr($ventable->nombre, 0, 60),
                    "cantidad" => (float) $item->cantidad,
                    "precio" => (float) $item->precio,
                ];
            }
        }

        $cliente = $factura->cliente;
        // Usar el RFC como código de cliente (consistente con ContpaqiService)
        if (strtoupper($cliente->nombre_razon_social) === 'PÚBLICO EN GENERAL' || $cliente->rfc === 'XAXX010101000') {
            $codigoCliente = 'PG';
        } elseif (!empty($cliente->rfc)) {
            $codigoCliente = strtoupper($cliente->rfc);
        } elseif (!empty($cliente->codigo)) {
            $codigoCliente = $cliente->codigo;
        } else {
            $codigoCliente = 'CTE_' . str_pad($cliente->id, 5, '0', STR_PAD_LEFT);
        }

        $payload = [
            "rutaEmpresa" => config('services.contpaqi.ruta_empresa'),
            "codigoConcepto" => config('services.contpaqi.concept_code', '4'),
            "codigoCliente" => $codigoCliente,
            "fecha" => now()->format('Y-m-d\TH:i:s'),
            "productos" => $productosPayload,
            "formaPago" => $factura->datos_fiscales['forma_pago'] ?? '99',
            "metodoPago" => $factura->metodo_pago ?? 'PUE',
            "usoCFDI" => $factura->datos_fiscales['uso_cfdi'] ?? 'G03',
        ];

        Log::info("CONTPAQi: Creando factura agrupada", ['payload' => $payload]);

        Log::info("CONTPAQi: Creando factura agrupada - INICIO", ['payload' => $payload]);

        // LOG ESPECÍFICO PARA PUBLICO EN GENERAL
        if ($payload['codigoCliente'] === 'PG' || $payload['codigoCliente'] === 'XAXX010101000') {
            Log::info("[[TIMBRADO PUBLICO GENERAL]] Payload enviado: ", $payload);
        }

        $response = \Illuminate\Support\Facades\Http::timeout(120)
            ->post(config('services.contpaqi.url') . "/api/Documentos/factura", $payload);

        if ($response->failed()) {
            throw new \Exception("Error CONTPAQi (Crear): " . $response->body());
        }

        $resJson = $response->json();
        Log::info("CONTPAQi: Respuesta completa del Bridge", ['json' => $resJson]);

        $folio = null;
        $serie = $resJson['serie'] ?? null;

        // Extraer Serie y Folio del mensaje de texto si es necesario
        if (isset($resJson['message'])) {
            // Buscar Serie: CDD
            if (preg_match('/Serie:\s*([A-Z0-9]+)/i', $resJson['message'], $matches)) {
                $serie = $matches[1];
                Log::debug("CONTPAQi: Serie extraída del mensaje: $serie");
            }
            // Buscar Folio: 313
            if (preg_match('/Folio:\s*(\d+(\.\d+)?)/i', $resJson['message'], $matches)) {
                $folio = $matches[1];
                Log::debug("CONTPAQi: Folio extraído del mensaje: $folio");
            }
        }

        // Fallbacks si no se encontraron en el mensaje
        $folio = $folio ?? $resJson['folio'] ?? $resJson['idDocumento'] ?? null;
        $serie = $serie ?? 'CDD';

        Log::info("CONTPAQi: DATOS FINALES PARA TIMBRAR", ['serie' => $serie, 'folio' => $folio]);

        if (!$folio) {
            throw new \Exception("No se obtuvo folio de la factura creada de CONTPAQi.");
        }

        // Timbrar
        $passCSD = config('services.contpaqi.csd_pass') ?: env('CONTPAQI_CSD_PASS');
        $timbradoExitoso = false;

        $timbradoErrorMsg = null;

        if (!empty($passCSD)) {
            try {
                Log::info("CONTPAQi: Intentando timbrar folio $folio con serie $serie");
                $timbrarPayload = [
                    "rutaEmpresa" => config('services.contpaqi.ruta_empresa'),
                    "codigoConcepto" => config('services.contpaqi.concept_code', '4'),
                    "serie" => $serie ?? '',
                    "folio" => (double) $folio,
                    "passCSD" => $passCSD
                ];

                // LOG ESPECÍFICO
                if ($payload['codigoCliente'] === 'PG' || $payload['codigoCliente'] === 'XAXX010101000') {
                    Log::info("[[TIMBRADO PUBLICO GENERAL]] Intentando timbrar: ", $timbrarPayload);
                }

                $timbrarResponse = \Illuminate\Support\Facades\Http::timeout(120)
                    ->post(config('services.contpaqi.url') . "/api/Documentos/timbrar", $timbrarPayload);

                if ($timbrarResponse->successful() && ($timbrarResponse->json()['success'] ?? false)) {
                    $timbradoExitoso = true;
                    if ($payload['codigoCliente'] === 'PG' || $payload['codigoCliente'] === 'XAXX010101000') {
                        Log::info("[[TIMBRADO PUBLICO GENERAL]] EXITO.");
                    }
                } else {
                    $timbradoErrorMsg = $timbrarResponse->body();
                    Log::warning("Error timbrando factura Folio {$folio}: " . $timbradoErrorMsg);
                    if ($payload['codigoCliente'] === 'PG' || $payload['codigoCliente'] === 'XAXX010101000') {
                        Log::error("[[TIMBRADO PUBLICO GENERAL]] ERROR: " . $timbradoErrorMsg);
                    }
                }
            } catch (\Exception $e) {
                $timbradoErrorMsg = $e->getMessage();
                Log::error("Excepción timbrando: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'folio' => $folio,
            'serie' => $serie,
            'timbrado' => $timbradoExitoso,
            'timbrado_error' => $timbradoErrorMsg
        ];
    }

    /**
     * Mostrar detalle de una factura
     */
    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'ventas.items.ventable', 'cfdi']);

        return Inertia::render('Facturas/Show', [
            'factura' => $factura,
            'cfdi' => $factura->cfdi,
        ]);
    }

    /**
     * Reintentar timbrado de una factura en borrador o recién creada
     */
    public function timbrar(Factura $factura)
    {
        if ($factura->estado === 'enviada' && $factura->cfdi()->exists()) {
            return redirect()->route('facturas.show', $factura)->with('info', 'La factura ya está timbrada.');
        }

        try {
            $folio = null;
            $serie = null;
            $timbradoExitoso = false;

            // 1. Detectar si ya tiene un folio de CONTPAQi (no es el dummy FAC-...)
            $tieneFolioReal = !empty($factura->numero_factura) && !str_starts_with($factura->numero_factura, 'FAC-');

            if ($tieneFolioReal) {
                // Extraer serie y folio (asumiendo que los últimos dígitos son el folio si no hay separador claro)
                // O guardando serie y folio en datos_fiscales en el futuro.
                // Por ahora, intentaremos timbrar el numero_factura completo si es numérico
                $folio = preg_replace('/[^0-9]/', '', $factura->numero_factura);
                $serie = preg_replace('/[0-9]/', '', $factura->numero_factura);

                Log::info("Reintentando timbrado para folio existente: {$serie}{$folio}");

                try {
                    $resTimbrado = $this->contpaqiService->timbrarFactura($folio, $serie);
                    if ($resTimbrado['success'] ?? false) {
                        $timbradoExitoso = true;
                    }
                } catch (\Exception $e) {
                    Log::warning("Fallo timbrado de folio existente, intentando recrear o verificar: " . $e->getMessage());
                }
            }

            // 2. Si no tiene folio o falló el timbrado previo, intentar crear
            if (!$timbradoExitoso && !$tieneFolioReal) {
                $ventas = $factura->ventas()->with('items.ventable')->get();
                $resultado = $this->crearFacturaAgrupada($factura, $ventas);

                if ($resultado['success']) {
                    $folio = $resultado['folio'];
                    $serie = $resultado['serie'];
                    $timbradoExitoso = $resultado['timbrado'];

                    // IMPORTANTE: Guardar el folio generado AUNQUE falle el timbrado,
                    // para evitar duplicar documentos en Contpaqi al reintentar.
                    if ($folio) {
                        $factura->update([
                            'numero_factura' => ($serie ?? '') . $folio
                        ]);
                    }

                    if (!$timbradoExitoso && !empty($resultado['timbrado_error'])) {
                        // Retornar error específico para el Modal
                        return redirect()->route('facturas.show', $factura)
                            ->with('stamping_error', $resultado['timbrado_error']);
                    }

                } else {
                    return redirect()->route('facturas.show', $factura)
                        ->with('error', 'No se pudo crear el documento en CONTPAQi.');
                }
            }

            // 3. Procesar éxito
            if ($timbradoExitoso || $tieneFolioReal) {
                try {
                    // Si llegamos aquí y no tenemos serie/folio, intentamos sacarlos de numero_factura
                    $folio = $folio ?: preg_replace('/[^0-9]/', '', $factura->numero_factura);
                    $serie = $serie ?: preg_replace('/[0-9]/', '', $factura->numero_factura);

                    $xmlRes = $this->contpaqiService->getDocumentoXml($folio, $serie);

                    if (!empty($xmlRes['xml'])) {
                        // Usar el nuevo método mejorado de ContpaqiService
                        $cfdi = $this->contpaqiService->procesarXmlEmitido($xmlRes['xml'], null, $factura);

                        if ($cfdi) {
                            $factura->update([
                                'numero_factura' => ($serie ?? '') . $folio,
                                'estado' => 'enviada',
                            ]);
                            return redirect()->route('facturas.show', $factura)->with('success', 'Factura timbrada exitosamente.');
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error obteniendo/procesando XML: " . $e->getMessage());

                    // Si falló el XML pero sabemos que se timbró (timbradoExitoso=true), actualizamos folio al menos
                    $factura->update([
                        'numero_factura' => ($serie ?? '') . $folio,
                        'estado' => 'borrador', // Queda en borrador para reintentar obtener XML
                    ]);

                    return redirect()->route('facturas.show', $factura)->with('warning', 'La factura se timbró pero hubo un error al obtener el XML: ' . $e->getMessage());
                }
            }

            return redirect()->route('facturas.show', $factura)->with('error', 'No se pudo completar el proceso de timbrado.');

        } catch (\Exception $e) {
            Log::error("Error crítico en timbrar factura {$factura->id}: " . $e->getMessage());
            return redirect()->route('facturas.show', $factura)->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar factura
     */
    public function cancelar(Request $request, Factura $factura)
    {
        $validated = $request->validate([
            'motivo' => 'required|string|in:01,02,03,04',
            'uuid_sustitucion' => 'nullable|required_if:motivo,01|string',
        ]);

        try {
            $cfdi = $factura->cfdi;

            if ($cfdi && config('services.contpaqi.enabled')) {
                $resultado = $this->contpaqiService->cancelarFactura(
                    $cfdi->folio,
                    $cfdi->serie ?? '',
                    $validated['motivo'],
                    $validated['uuid_sustitucion'] ?? null
                );

                if (!($resultado['success'] ?? false)) {
                    throw new \Exception($resultado['message'] ?? 'Error desconocido al cancelar');
                }
            }

            $factura->update(['estado' => 'cancelada']);
            Venta::where('factura_id', $factura->id)->update(['factura_id' => null]);

            if ($cfdi) {
                $cfdi->update([
                    'estatus' => 'cancelado',
                    'motivo_cancelacion' => $validated['motivo'],
                    'fecha_cancelacion' => now(),
                    'folio_sustitucion' => $validated['uuid_sustitucion'] ?? null,
                ]);
            }

            return back()->with('success', 'Factura cancelada exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error cancelando factura {$factura->id}: " . $e->getMessage());
            return back()->with('error', 'Error al cancelar: ' . $e->getMessage());
        }
    }

    /**
     * Descargar el archivo XML de la factura
     */
    public function descargarXML(Factura $factura)
    {
        $cfdi = $factura->cfdi;
        if (!$cfdi || !$cfdi->xml_url || $cfdi->xml_url === 'PENDIENTE') {
            return back()->with('error', 'El archivo XML no está disponible.');
        }

        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($cfdi->xml_url);

        if (!file_exists($path)) {
            return back()->with('error', 'El archivo físico XML no se encuentra en el servidor.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'inline; filename="factura-' . $factura->numero_factura . '.xml"'
        ]);
    }

    /**
     * Generar PDF de factura
     */
    public function generarPDF($id)
    {
        $factura = Factura::with(['cliente', 'ventas.items.ventable', 'cfdi'])->findOrFail($id);

        // Si ya está timbrada y tiene un registro CFDI, usar el servicio profesional de PDF
        if ($factura->cfdi) {
            $pdfContent = $this->cfdiPdfService->generatePdfContent($factura->cfdi);
            if ($pdfContent) {
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=\"factura-{$factura->numero_factura}.pdf\"");
            }
        }

        // Si no está timbrada o falló el servicio profesional, usar el PDF básico
        $pdf = $this->pdfService->loadView('factura', [
            'factura' => $factura
        ]);

        return $pdf->stream("factura-{$factura->numero_factura}.pdf");
    }

    /**
     * Mostrar vista previa de factura
     */
    public function preview($id)
    {
        $factura = Factura::with(['cliente', 'ventas.items.ventable', 'cfdi'])->findOrFail($id);

        if ($factura->cfdi) {
            $pdfContent = $this->cfdiPdfService->generatePdfContent($factura->cfdi);
            if ($pdfContent) {
                return response($pdfContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=\"factura-{$factura->numero_factura}.pdf\"");
            }
        }

        return view('factura', compact('factura'));
    }

    /**
     * Eliminar una factura en borrador y liberar sus ventas
     */
    public function destroy(Factura $factura)
    {
        if ($factura->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar facturas en borrador.');
        }

        try {
            DB::transaction(function () use ($factura) {
                // Liberar ventas
                Venta::where('factura_id', $factura->id)->update([
                    'factura_id' => null
                ]);

                // Eliminar CFDI asociado si existe (local)
                if ($factura->cfdi) {
                    $factura->cfdi->delete();
                }

                $factura->delete();
            });

            return redirect()->route('facturas.index')
                ->with('success', 'Borrador eliminado correctamente. Las ventas ahora están disponibles para facturar de nuevo.');

        } catch (\Exception $e) {
            Log::error("Error eliminando factura borrador {$factura->id}: " . $e->getMessage());
            return back()->with('error', 'Error al eliminar el borrador: ' . $e->getMessage());
        }
    }
}
