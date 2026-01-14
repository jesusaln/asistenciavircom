<?php
// app/Http/Controllers/InventarioController.php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function index()
    {
        $inventarios = Inventario::with('producto')->get();
        return Inertia::render('Inventario/Index', ['inventarios' => $inventarios]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'cantidad' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
        ]);

        $producto = Producto::findOrFail($validated['producto_id']);

        // Si la cantidad es mayor que 0, registrar entrada
        if ($validated['cantidad'] > 0) {
            $this->inventarioService->entrada($producto, $validated['cantidad'], [
                'almacen_id' => $validated['almacen_id'],
                'motivo' => 'Registro inicial de inventario',
            ]);
        }

        // Actualizar stock mínimo si se proporciona
        $inventario = Inventario::firstOrCreate(
            [
                'producto_id' => $validated['producto_id'],
                'almacen_id' => $validated['almacen_id'],
            ],
            ['cantidad' => 0, 'stock_minimo' => 0]
        );

        if (isset($validated['stock_minimo'])) {
            $inventario->update(['stock_minimo' => $validated['stock_minimo']]);
        }

        return redirect()->route('inventario.index')->with('success', 'Inventario actualizado.');
    }

    public function update(Request $request, Inventario $inventario)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:0',
            'stock_minimo' => 'nullable|integer|min:0',
        ]);

        $diferencia = $validated['cantidad'] - $inventario->cantidad;

        if ($diferencia > 0) {
            // Entrada
            $this->inventarioService->entrada($inventario->producto, $diferencia, [
                'almacen_id' => $inventario->almacen_id,
                'motivo' => 'Ajuste de inventario',
            ]);
        } elseif ($diferencia < 0) {
            // Salida
            $this->inventarioService->salida($inventario->producto, abs($diferencia), [
                'almacen_id' => $inventario->almacen_id,
                'motivo' => 'Ajuste de inventario',
            ]);
        }

        // ✅ FIX #1: Actualizar solo stock_minimo, NO cantidad
        // El servicio inventarioService ya actualiza la cantidad correctamente
        if (isset($validated['stock_minimo'])) {
            $inventario->update(['stock_minimo' => $validated['stock_minimo']]);
        }

        return redirect()->route('inventario.index')->with('success', 'Inventario actualizado.');
    }
}
