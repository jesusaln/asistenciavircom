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

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $pedidos = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/PedidosOnline/Index', [
            'pedidos' => $pedidos,
            'filters' => $request->only(['search', 'metodo_pago', 'estado'])
        ]);
    }

    public function show($id)
    {
        $pedido = PedidoOnline::with(['cliente', 'bitacora.usuario'])->findOrFail($id);

        return Inertia::render('Admin/PedidosOnline/Show', [
            'pedido' => $pedido
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $pedido = PedidoOnline::findOrFail($id);

        $request->validate([
            'estado' => 'nullable|string',
            'guia_envio' => 'nullable|string',
            'descripcion_bitacora' => 'required|string'
        ]);

        if ($request->has('estado')) {
            $pedido->estado = $request->estado;
            if ($request->estado === 'pagado' && !$pedido->pagado_at) {
                $pedido->pagado_at = now();
            }
        }

        if ($request->has('guia_envio')) {
            $pedido->guia_envio = $request->guia_envio;
        }

        $pedido->save();

        // Registrar en bitácora
        $pedido->registrarEvento(
            'ACTUALIZACION_MANUAL',
            $request->descripcion_bitacora . " (Por: " . auth()->user()->name . ")",
            ['cambios' => $request->only(['estado', 'guia_envio'])]
        );

        return redirect()->back()->with('success', 'Pedido actualizado correctamente.');
    }
}
