<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Venta;
use App\Models\Cliente;
use App\Services\Cfdi\CfdiService;
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
        protected CfdiService $cfdiService
    ) {
    }

    /**
     * Mostrar listado de facturas
     */
    public function index(Request $request)
    {
        $query = Factura::with(['cliente', 'ventas'])
            ->orderByDesc('created_at');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $searchPattern = "%{$buscar}%";
                $q->where('numero_factura', 'ILIKE', $searchPattern)
                    ->orWhereHas('cliente', function ($c) use ($searchPattern) {
                        $c->whereRaw("unaccent(nombre_razon_social) ILIKE unaccent(?)", [$searchPattern]);
                    });
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

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $facturas = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => Factura::count(),
            'pendientes' => Factura::where('estado', 'enviada')->count(),
            'pagadas' => Factura::where('estado', 'pagada')->count(),
            'canceladas' => Factura::where('estado', 'cancelada')->count(),
            'monto_pendiente' => Factura::where('estado', 'enviada')->sum('total'),
        ];

        return Inertia::render('Facturas/Index', [
            'facturas' => $facturas,
            'filtros' => $request->only(['buscar', 'estado', 'desde', 'hasta', 'cliente_id']),
            'stats' => $stats,
        ]);
    }

    /**
     * Formulario para crear factura agrupada
     */
    public function create(Request $request)
    {
        $clientes = Cliente::where('activo', true)
            ->select('id', 'nombre_razon_social', 'rfc', 'regimen_fiscal', 'uso_cfdi', 'forma_pago_default')
            ->orderBy('nombre_razon_social')
            ->limit(1000)
            ->get();

        $ventasPendientes = collect();
        $clienteSeleccionado = null;
        $datosPrellenado = [];

        if ($request->filled('venta_id')) {
            $venta = Venta::find($request->venta_id);
            if ($venta && !$request->filled('cliente_id')) {
                $request->merge(['cliente_id' => $venta->cliente_id]);
            }
            if ($venta) {
                $datosPrellenado = [
                    'forma_pago' => $venta->forma_pago_sat ?? ($venta->metodo_pago === 'efectivo' ? '01' : '99'),
                    'metodo_pago' => $venta->metodo_pago_sat ?? ($venta->pagado ? 'PUE' : 'PPD'),
                    'uso_cfdi' => $venta->uso_cfdi
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
                    'items' => $v->items->map(fn($item) => [
                        'id' => $item->id,
                        'ventable_id' => $item->ventable_id,
                        'ventable_type' => $item->ventable_type,
                        'nombre' => $item->ventable?->nombre ?? $item->nombre ?? $item->descripcion ?? 'Servicio/Producto',
                        'sat_clave_prod_serv' => $item->ventable?->sat_clave_prod_serv ?? $item->ventable?->clave_sat ?? $item->clave_sat ?? '',
                        'sat_clave_unidad' => $item->ventable?->sat_clave_unidad ?? $item->ventable?->unidad_sat ?? $item->unidad_sat ?? '',
                    ]),
                    'pagado' => (bool) $v->pagado,
                    'metodo_pago_sugerido' => $v->pagado ? 'PUE' : 'PPD',
                    'forma_pago_sugerida' => $v->forma_pago_sat ?? ($v->metodo_pago === 'efectivo' ? '01' : ($v->pagado ? '03' : '99')),
                    'etiqueta_pago' => $v->pagado ? 'Pagado (' . ($v->metodo_pago ?? 'N/A') . ')' : 'Pendiente de Pago',
                    'selected' => $request->venta_id == $v->id
                ]);
        }

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
            'rfc' => 'required|string|min:12|max:13',
            'uso_cfdi' => 'required|string',
            'forma_pago' => 'required|string',
            'metodo_pago' => 'required|string|in:PUE,PPD',
            'regimen_fiscal' => 'required|string',
            'codigo_postal' => 'required|string',
            'observaciones' => 'nullable|string|max:1000',
            'extra_services' => 'nullable|array',
            'extra_services.*.id' => 'required|exists:servicios,id',
            'extra_services.*.cantidad' => 'required|numeric|min:0.1',
            'extra_services.*.precio' => 'required|numeric|min:0',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $datosUpdate = [];
        if (!$cliente->requiere_factura) {
            $datosUpdate['requiere_factura'] = true;
        }
        if (strtoupper($cliente->rfc) !== strtoupper($validated['rfc'])) {
            $datosUpdate['rfc'] = strtoupper($validated['rfc']);
        }
        if ($cliente->regimen_fiscal !== $validated['regimen_fiscal']) {
            $datosUpdate['regimen_fiscal'] = $validated['regimen_fiscal'];
        }
        if ($cliente->uso_cfdi !== $validated['uso_cfdi']) {
            $datosUpdate['uso_cfdi'] = $validated['uso_cfdi'];
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
            $ventas = Venta::whereIn('id', $validated['ventas_ids'])
                ->lockForUpdate()
                ->get();

            if ($ventas->count() !== count($validated['ventas_ids'])) {
                DB::rollBack();
                return back()->with('error', 'Algunas ventas seleccionadas no existen.');
            }

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

            if (!empty($validated['extra_services'])) {
                $almacenDefault = \App\Models\Almacen::where('empresa_id', $cliente->empresa_id)->first();
                
                $extraSubtotal = 0;
                foreach($validated['extra_services'] as $s) {
                    $extraSubtotal += ($s['precio'] * $s['cantidad']);
                }
                $extraIva = $extraSubtotal * 0.16;
                $extraTotal = $extraSubtotal + $extraIva;

                $nuevaVenta = Venta::create([
                    'empresa_id' => $cliente->empresa_id,
                    'cliente_id' => $cliente->id,
                    'fecha' => now(),
                    'subtotal' => $extraSubtotal,
                    'iva' => $extraIva,
                    'total' => $extraTotal,
                    'estado' => 'borrador',
                    'vendedor_id' => auth()->id(),
                    'almacen_id' => $almacenDefault ? $almacenDefault->id : null,
                    'metodo_pago' => $validated['metodo_pago'] === 'PUE' ? 'efectivo' : 'credito',
                    'notas' => 'Venta generada automáticamente desde facturación para servicios adicionales.'
                ]);

                foreach($validated['extra_services'] as $s) {
                    $servicio = \App\Models\Servicio::find($s['id']);
                    $nuevaVenta->items()->create([
                        'empresa_id' => $cliente->empresa_id,
                        'ventable_id' => $servicio->id,
                        'ventable_type' => 'App\Models\Servicio',
                        'cantidad' => $s['cantidad'],
                        'precio' => $s['precio'],
                        'subtotal' => $s['precio'] * $s['cantidad']
                    ]);
                }

                $ventas->push($nuevaVenta);
            }

            $subtotal = $ventas->sum('subtotal');
            $iva = $ventas->sum('iva');
            $total = $ventas->sum('total');

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

            foreach ($ventas as $venta) {
                $venta->factura_id = $factura->id;
                $venta->forma_pago_sat = $validated['forma_pago'];
                $venta->metodo_pago_sat = $validated['metodo_pago'];
                $venta->save();
            }

            DB::commit();

            return $this->timbrar($factura);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creando factura: " . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Error al crear la factura: ' . $e->getMessage());
        }
    }

    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'ventas.items.ventable', 'cfdi']);

        $catalogos = [
            'regimenes' => \App\Models\SatRegimenFiscal::orderBy('clave')->get(),
            'usosCfdi' => \App\Models\SatUsoCfdi::where('activo', true)->orderBy('clave')->get(),
        ];

        return Inertia::render('Facturas/Show', [
            'factura' => $factura,
            'cfdi' => $factura->cfdi,
            'catalogos' => $catalogos,
        ]);
    }

    /**
     * Timbrar una factura mediante SW Sapien en la nube
     */
    public function timbrar(Factura $factura)
    {
        if ($factura->estado === 'enviada' && $factura->cfdi()->exists()) {
            return redirect()->route('facturas.show', $factura)->with('info', 'La factura ya está timbrada.');
        }

        try {
            $res = $this->cfdiService->facturarDocumento($factura);

            if ($res['success']) {
                return redirect()->route('facturas.show', $factura)->with('success', 'Factura timbrada exitosamente en la nube con SW Sapien.');
            }

            $factura->update(['estado' => 'borrador']);
            return redirect()->route('facturas.show', $factura)->with('stamping_error', $res['message'] ?? 'Error al timbrar la factura.');

        } catch (\Exception $e) {
            Log::error("Error en timbrar factura {$factura->id}: " . $e->getMessage());
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
            if ($cfdi) {
                $resultado = $this->cfdiService->cancelar($cfdi, $validated['motivo'], $validated['uuid_sustitucion'] ?? null);
                if (!$resultado['success']) {
                    throw new \Exception($resultado['message'] ?? 'Error al cancelar en SW Sapien.');
                }
            }

            $factura->update(['estado' => 'cancelada']);
            Venta::where('factura_id', $factura->id)->update(['factura_id' => null]);

            return back()->with('success', 'Factura cancelada exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error cancelando factura {$factura->id}: " . $e->getMessage());
            return back()->with('error', 'Error al cancelar: ' . $e->getMessage());
        }
    }

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
            'Content-Disposition' => 'inline; filename="factura-' . ($factura->numero_factura ?? $cfdi->uuid) . '.xml"'
        ]);
    }

    public function generarPDF($id)
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

        $pdf = $this->pdfService->loadView('factura', [
            'factura' => $factura
        ]);

        return $pdf->stream("factura-{$factura->numero_factura}.pdf");
    }

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

    public function destroy(Factura $factura)
    {
        if ($factura->estado !== 'borrador') {
            return back()->with('error', 'Solo se pueden eliminar facturas en borrador.');
        }

        try {
            DB::transaction(function () use ($factura) {
                Venta::where('factura_id', $factura->id)->update([
                    'factura_id' => null
                ]);

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
