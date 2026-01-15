<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PedidoOnline;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PedidoOnlineController extends Controller
{
    public function index(Request $request)
    {
        $query = PedidoOnline::with(['cliente'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estatus_pago')) {
            $query->where('estatus_pago', $request->estatus_pago);
        }

        if ($request->filled('estatus_pedido')) {
            $query->where('estatus_pedido', $request->estatus_pedido);
        }

        $pedidos = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/PedidosOnline/Index', [
            'pedidos' => $pedidos,
            'filters' => $request->only(['search', 'estatus_pago', 'estatus_pedido'])
        ]);
    }

    public function show($id)
    {
        $pedido = PedidoOnline::with(['cliente', 'items', 'bitacora.usuario'])->findOrFail($id);

        return Inertia::render('Admin/PedidosOnline/Show', [
            'pedido' => $pedido
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $pedido = PedidoOnline::findOrFail($id);

        $request->validate([
            'estatus_pago' => 'nullable|string',
            'estatus_pedido' => 'nullable|string',
            'guia_envio' => 'nullable|string',
            'descripcion_bitacora' => 'required|string'
        ]);

        if ($request->has('estatus_pago')) {
            $pedido->estatus_pago = $request->estatus_pago;
            if ($request->estatus_pago === 'pagado' && !$pedido->fecha_pago) {
                $pedido->fecha_pago = now();
            }
        }

        if ($request->has('estatus_pedido')) {
            $pedido->estatus_pedido = $request->estatus_pedido;
        }

        if ($request->has('guia_envio')) {
            $pedido->guia_envio = $request->guia_envio;
        }

        $pedido->save();

        // Registrar en bitácora
        $pedido->registrarEvento(
            'ACTUALIZACION_MANUAL',
            $request->descripcion_bitacora . " (Por: " . auth()->user()->name . ")",
            ['cambios' => $request->only(['estatus_pago', 'estatus_pedido', 'guia_envio'])]
        );

        return redirect()->back()->with('success', 'Pedido actualizado correctamente.');
    }
}
