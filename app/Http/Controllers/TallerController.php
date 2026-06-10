<?php

namespace App\Http\Controllers;

use App\Models\TallerOrden;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Servicio;
use App\Models\Almacen;
use App\Models\Venta;
use App\Services\EmpresaConfiguracionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TallerController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $query = TallerOrden::with(['cliente', 'recepcionista', 'tecnico'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('folio', 'ILIKE', "%$s%")
                  ->orWhere('equipo_marca', 'ILIKE', "%$s%")
                  ->orWhere('nombre_cliente', 'ILIKE', "%$s%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $pendientes = TallerOrden::whereNotIn('estado', ['entregado', 'cancelado'])->count();

        return Inertia::render('Taller/Index', [
            'ordenes' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'estado', 'cliente_id']),
            'pendientes_count' => $pendientes,
        ]);
    }

    public function create()
    {
        return Inertia::render('Taller/Create', [
            'clientes' => Cliente::all(),
            'marcas' => \App\Models\Marca::activas()->get(['id', 'nombre']),
            'catalogs' => [
                'regimenesFiscales' => \App\Models\SatRegimenFiscal::all(),
                'usosCFDI' => \App\Models\SatUsoCfdi::all(),
                'estados' => \App\Models\SatEstado::all(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id,empresa_id,' . $empresaId,
            'equipo_marca' => 'required|string',
            'equipo_modelo' => 'required|string',
            'equipo_serie' => 'nullable|string',
            'problema_reportado' => 'required|string',
            'fecha_compromiso' => 'nullable|date',
            'accesorios' => 'nullable|string',
            'estado_fisico' => 'nullable|string',
        ]);

        if (!empty($validated['accesorios']) && is_string($validated['accesorios'])) {
            $validated['accesorios'] = array_filter(array_map('trim', explode("\n", $validated['accesorios'])));
        }

        $validated['empresa_id'] = $empresaId;
        $validated['user_id'] = auth()->id();
        $validated['estado'] = 'recepcionado';
        $validated['fecha_recepcion'] = now();

        $cliente = Cliente::find($validated['cliente_id']);
        $validated['nombre_cliente'] = $cliente->nombre_razon_social ?? $cliente->nombre;
        $validated['telefono_cliente'] = $cliente->telefono;

        $orden = TallerOrden::create($validated);

        if ($request->filled('firma_recepcion') && Str::startsWith($request->firma_recepcion, 'data:image/png;base64,')) {
            $imageData = $request->firma_recepcion;
            $fileName = 'recepcion_' . $orden->id . '_' . time() . '.png';
            $image = str_replace('data:image/png;base64,', '', $imageData);
            $image = str_replace(' ', '+', $image);
            Storage::disk('public')->put('taller/firmas/' . $fileName, base64_decode($image));
            $orden->update(['firma_recepcion' => $fileName]);
        }

        return redirect()->route('taller.index')
            ->with('success', 'Orden de taller creada correctamente.');
    }

    public function show(TallerOrden $taller)
    {
        return Inertia::render('Taller/Show', [
            'orden' => $taller->load(['cliente', 'recepcionista', 'tecnico', 'venta']),
            'tecnicos' => User::role('tecnico')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, TallerOrden $taller)
    {
        $validated = $request->validate([
            'estado' => 'sometimes|string',
            'tecnico_id' => 'nullable|exists:users,id',
            'diagnostico' => 'nullable|string',
            'trabajo_realizado' => 'nullable|string',
            'costo_final' => 'nullable|numeric',
            'fecha_compromiso' => 'nullable|date',
        ]);

        if ($request->filled('firma_entrega') && Str::startsWith($request->firma_entrega, 'data:image/png;base64,')) {
            $imageData = $request->firma_entrega;
            $fileName = 'entrega_' . $taller->id . '_' . time() . '.png';
            $image = str_replace('data:image/png;base64,', '', $imageData);
            $image = str_replace(' ', '+', $image);
            Storage::disk('public')->put('taller/firmas/' . $fileName, base64_decode($image));
            $validated['firma_entrega'] = $fileName;
            $validated['fecha_entrega'] = now();
        }

        $taller->update($validated);

        return back()->with('success', 'Orden actualizada.');
    }

    public function reporte(TallerOrden $taller)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('taller.reporte_recepcion', [
            'orden' => $taller->load(['cliente', 'recepcionista', 'tecnico', 'venta']),
            'empresa' => \App\Models\Empresa::find($taller->empresa_id),
            'qr_url' => route('taller.show', $taller->id),
        ])->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ]);

        return $pdf->stream('recepcion_' . $taller->folio . '.pdf');
    }

    public function destroy(TallerOrden $taller)
    {
        $taller->delete();
        return redirect()->route('taller.index')->with('success', 'Orden eliminada.');
    }

    public function facturar(TallerOrden $taller)
    {
        if ($taller->costo_final <= 0) {
            return back()->with('error', 'La orden debe tener un costo final para facturar.');
        }

        if ($taller->venta) {
            return back()->with('error', 'Esta orden ya tiene una venta vinculada.');
        }

        $ivaPorcentaje = EmpresaConfiguracionService::getIvaPorcentaje();
        $ivaRate = $ivaPorcentaje / 100;
        $subtotal = $taller->costo_final / (1 + $ivaRate);
        $iva = $taller->costo_final - $subtotal;

        $servicioTaller = Servicio::active()
            ->where(function ($q) {
                $q->where('nombre', 'ILIKE', '%taller%')
                  ->orWhere('nombre', 'ILIKE', '%reparacion%')
                  ->orWhere('nombre', 'ILIKE', '%servicio t%');
            })
            ->first();

        if (!$servicioTaller) {
            $servicioTaller = Servicio::active()->first();
        }

        if (!$servicioTaller) {
            return back()->with('error', 'No hay servicios activos configurados. Crea un servicio de taller primero.');
        }

        $empresaId = $taller->empresa_id;

        $venta = Venta::create([
            'empresa_id' => $empresaId,
            'cliente_id' => $taller->cliente_id,
            'taller_orden_id' => $taller->id,
            'fecha' => now(),
            'subtotal' => round($subtotal, 2),
            'iva' => round($iva, 2),
            'total' => $taller->costo_final,
            'estado' => 'borrador',
            'vendedor_id' => $taller->tecnico_id ?? auth()->id(),
            'almacen_id' => Almacen::where('empresa_id', $empresaId)->first()?->id,
            'metodo_pago' => 'credito',
            'notas' => "Venta generada desde Orden de Taller: {$taller->folio}. " . ($taller->trabajo_realizado ?? ''),
        ]);

        $venta->items()->create([
            'empresa_id' => $empresaId,
            'ventable_id' => $servicioTaller->id,
            'ventable_type' => 'App\Models\Servicio',
            'cantidad' => 1,
            'precio' => round($subtotal, 2),
            'subtotal' => round($subtotal, 2),
        ]);

        return redirect()->route('facturas.create', [
            'cliente_id' => $taller->cliente_id,
            'venta_id' => $venta->id,
        ]);
    }
}
