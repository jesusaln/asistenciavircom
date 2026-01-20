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
            'monto_pendiente' => Factura::where('estado', 'enviada')->sum('total'),
        ];

        return Inertia::render('Facturas/Index', [
            'facturas' => $facturas,
            'filters' => $request->only(['buscar', 'estado', 'desde', 'hasta']),
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
            ->select('id', 'nombre_razon_social', 'rfc', 'regimen_fiscal', 'uso_cfdi')
            ->orderBy('nombre_razon_social')
            ->limit(1000)
            ->get();

        // Si se selecciona un cliente, obtener sus ventas pendientes
        $ventasPendientes = collect();
        $clienteSeleccionado = null;

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
            'observaciones' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $cliente = Cliente::findOrFail($validated['cliente_id']);

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

            // Crear factura local primero
            $factura = Factura::create([
                'empresa_id' => $cliente->empresa_id ?? auth()->user()->empresa_id,
                'cliente_id' => $cliente->id,
                'fecha_emision' => now(),
                'fecha_vencimiento' => now()->addDays($cliente->dias_credito ?? 0),
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total,
                'estado' => 'borrador', // Inicia en borrador
                'metodo_pago' => $validated['metodo_pago'],
                'forma_pago' => $validated['forma_pago'],
                'uso_cfdi' => $validated['uso_cfdi'],
                'observaciones' => $validated['observaciones'],
                'moneda' => 'MXN',
                'datos_fiscales' => [
                    'rfc' => $cliente->rfc,
                    'razon_social' => $cliente->razon_social ?? $cliente->nombre_razon_social,
                    'regimen_fiscal' => $cliente->regimen_fiscal,
                    'uso_cfdi' => $validated['uso_cfdi'],
                    'forma_pago' => $validated['forma_pago'],
                    'metodo_pago' => $validated['metodo_pago'],
                    'domicilio_fiscal_cp' => $cliente->domicilio_fiscal_cp ?? $cliente->codigo_postal,
                ],
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
        $codigoCliente = !empty($cliente->codigo) ? $cliente->codigo : ('CTE_' . str_pad($cliente->id, 5, '0', STR_PAD_LEFT));
        if (strtoupper($cliente->nombre_razon_social) === 'PÚBLICO EN GENERAL' || $cliente->rfc === 'XAXX010101000') {
            $codigoCliente = 'PG';
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

        $response = \Illuminate\Support\Facades\Http::timeout(60)
            ->post(config('services.contpaqi.url') . "/api/Documentos/factura", $payload);

        if ($response->failed()) {
            throw new \Exception("Error CONTPAQi (Crear): " . $response->body());
        }

        $resJson = $response->json();

        $folio = $resJson['folio'] ?? null;
        $serie = $resJson['serie'] ?? null;

        if (!$folio && isset($resJson['message'])) {
            if (preg_match('/Folio: (\d+)/', $resJson['message'], $matches)) {
                $folio = $matches[1];
            }
        }

        if (!$folio) {
            throw new \Exception("No se obtuvo folio de la factura creada.");
        }

        // Timbrar
        $passCSD = config('services.contpaqi.csd_pass');
        $timbradoExitoso = false;

        if (!empty($passCSD)) {
            try {
                $timbrarPayload = [
                    "rutaEmpresa" => config('services.contpaqi.ruta_empresa'),
                    "codigoConcepto" => config('services.contpaqi.concept_code', '4'),
                    "serie" => $serie ?? '',
                    "folio" => (int) $folio,
                    "passCSD" => $passCSD
                ];

                $timbrarResponse = \Illuminate\Support\Facades\Http::timeout(90)
                    ->post(config('services.contpaqi.url') . "/api/Documentos/timbrar", $timbrarPayload);

                if ($timbrarResponse->successful() && ($timbrarResponse->json()['success'] ?? false)) {
                    $timbradoExitoso = true;
                } else {
                    Log::warning("Error timbrando factura Folio {$folio}: " . $timbrarResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("Excepción timbrando: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'folio' => $folio,
            'serie' => $serie,
            'timbrado' => $timbradoExitoso
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
        if ($factura->estado === 'timbrado' || $factura->estado === 'pagada') {
            return redirect()->route('facturas.show', $factura)->with('info', 'La factura ya está timbrada.');
        }

        try {
            $ventas = $factura->ventas()->with('items.ventable')->get();

            $resultado = $this->crearFacturaAgrupada($factura, $ventas);

            if ($resultado['success']) {
                $uuid = null;

                if ($resultado['timbrado']) {
                    try {
                        $xmlRes = $this->contpaqiService->getDocumentoXml($resultado['folio'], $resultado['serie']);

                        if (!empty($xmlRes['xml'])) {
                            $parser = app(\App\Services\CfdiXmlParserService::class);
                            $data = $parser->parseCfdiXml($xmlRes['xml']);

                            if (!empty($data['uuid'])) {
                                $uuid = $data['uuid'];

                                // Resetear relación anterior si existe (caso re-timbrado? no debería existir)
                                $factura->cfdi()->delete();

                                $factura->cfdi()->create([
                                    'empresa_id' => $factura->empresa_id,
                                    'cliente_id' => $factura->cliente_id,
                                    'uuid' => $uuid,
                                    'fecha_timbrado' => now(),
                                    'xml_url' => 'PENDIENTE',
                                    'estatus' => 'vigente',
                                    'metodo_pago' => $factura->metodo_pago,
                                    'forma_pago' => $factura->datos_fiscales['forma_pago'],
                                    'uso_cfdi' => $factura->datos_fiscales['uso_cfdi'],
                                    'total' => $factura->total,
                                    'subtotal' => $factura->subtotal,
                                ]);

                                $factura->update([
                                    'numero_factura' => ($resultado['serie'] ?? '') . $resultado['folio'],
                                    'estado' => 'enviada',
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error procesando XML post-timbrado: " . $e->getMessage());
                    }
                } else {
                    $factura->update([
                        'numero_factura' => ($resultado['serie'] ?? '') . $resultado['folio'],
                        'estado' => 'borrador',
                    ]);
                    return redirect()->route('facturas.show', $factura)->with('warning', 'Factura creada en CONTPAQi pero NO timbrada. Revise la conexión con PAC.');
                }

                return redirect()->route('facturas.show', $factura)->with('success', 'Factura creada y timbrada exitosamente.');
            }

            return back()->with('error', 'No se pudo crear la factura en CONTPAQi.');

        } catch (\Exception $e) {
            Log::error("Error timbrando factura {$factura->id}: " . $e->getMessage());
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
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
                    $cfdi->uuid,
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
                $cfdi->update(['estatus' => 'cancelado']);
            }

            return back()->with('success', 'Factura cancelada exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error cancelando factura {$factura->id}: " . $e->getMessage());
            return back()->with('error', 'Error al cancelar: ' . $e->getMessage());
        }
    }

    /**
     * Generar PDF de factura
     */
    public function generarPDF($id)
    {
        $factura = Factura::with(['cliente', 'ventas.items.ventable'])->findOrFail($id);

        $pdf = $this->pdfService->loadView('factura', [
            'factura' => $factura
        ]);

        return $this->pdfService->download($pdf, "factura-{$factura->numero_factura}.pdf");
    }

    /**
     * Mostrar vista previa de factura
     */
    public function preview($id)
    {
        $factura = Factura::with(['cliente', 'ventas.items.ventable'])->findOrFail($id);
        return view('factura', compact('factura'));
    }
}
