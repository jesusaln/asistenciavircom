<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\TraspasoBancario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TraspasoBancarioController extends Controller
{
    /**
     * Muestra el listado de traspasos bancarios.
     */
    public function index()
    {
        $traspasos = TraspasoBancario::with(['origen', 'destino', 'usuario'])
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return Inertia::render('Finanzas/TraspasosBancarios/Index', [
            'traspasos' => $traspasos,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo traspaso.
     */
    /**
     * Muestra el formulario para crear un nuevo traspaso.
     */
    public function create()
    {
        $cuentas = CuentaBancaria::activas()
            ->orderBy('banco')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'banco', 'numero_cuenta', 'saldo_actual', 'moneda']);

        return Inertia::render('Finanzas/TraspasosBancarios/Create', [
            'cuentas' => $cuentas,
        ]);
    }

    /**
     * Muestra un traspaso o redirige el /index erróneo.
     */
    public function show($id)
    {
        if ($id === 'index') {
            return redirect()->route('traspasos-bancarios.index');
        }

        $traspaso = TraspasoBancario::with(['origen', 'destino', 'usuario'])->findOrFail($id);
        
        return Inertia::render('Finanzas/TraspasosBancarios/Show', [
            'traspaso' => $traspaso,
        ]);
    }

    /**
     * Muestra el formulario para editar un traspaso.
     */
    public function edit($id)
    {
        $traspaso = TraspasoBancario::with(['origen', 'destino'])->findOrFail($id);
        
        return Inertia::render('Finanzas/TraspasosBancarios/Edit', [
            'traspaso' => $traspaso,
        ]);
    }

    /**
     * Actualiza la información descriptiva de un traspaso.
     */
    public function update(Request $request, $id)
    {
        $traspaso = TraspasoBancario::findOrFail($id);
        
        $validated = $request->validate([
            'referencia' => 'nullable|string|max:255',
            'notas' => 'nullable|string|max:500',
        ]);

        $traspaso->update($validated);

        return redirect()->route('traspasos-bancarios.index')->with('success', 'Traspaso actualizado correctamente.');
    }

    /**
     * Elimina un traspaso y revierte los movimientos bancarios.
     */
    public function destroy($id)
    {
        $traspaso = TraspasoBancario::findOrFail($id);

        try {
            DB::transaction(function () use ($traspaso) {
                // 1. Revertir Retiro en Origen (Registrar Depósito Compensatorio)
                $origen = CuentaBancaria::findOrFail($traspaso->cuenta_origen_id);
                $origen->registrarMovimiento(
                    'deposito',
                    $traspaso->monto,
                    "REVERSO TRASPASO #{$traspaso->id} - Devolución de fondos",
                    'traspaso'
                );

                // 2. Revertir Depósito en Destino (Registrar Retiro Compensatorio)
                $destino = CuentaBancaria::findOrFail($traspaso->cuenta_destino_id);
                $destino->registrarMovimiento(
                    'retiro',
                    $traspaso->monto,
                    "REVERSO TRASPASO #{$traspaso->id} - Retiro por eliminación",
                    'traspaso'
                );

                // 3. Eliminar el registro (SoftDelete)
                $traspaso->delete();
            });

            return redirect()->route('traspasos-bancarios.index')->with('success', 'Traspaso eliminado y saldos revertidos correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al revertir traspaso: ' . $e->getMessage()]);
        }
    }

    /**
     * Almacena un traspaso en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cuenta_origen_id' => 'required|exists:cuentas_bancarias,id',
            'cuenta_destino_id' => 'required|exists:cuentas_bancarias,id|different:cuenta_origen_id',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'referencia' => 'nullable|string|max:255',
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // 1. Crear registro maestro de traspaso
                $traspaso = TraspasoBancario::create([
                    'cuenta_origen_id' => $validated['cuenta_origen_id'],
                    'cuenta_destino_id' => $validated['cuenta_destino_id'],
                    'monto' => $validated['monto'],
                    'fecha' => $validated['fecha'],
                    'referencia' => $validated['referencia'],
                    'notas' => $validated['notas'],
                    'user_id' => auth()->id(),
                ]);

                // 2. Registrar Retiro en Origen
                $origen = CuentaBancaria::findOrFail($validated['cuenta_origen_id']);
                $destino = CuentaBancaria::findOrFail($validated['cuenta_destino_id']);
                
                $movimientoSalida = $origen->registrarMovimiento(
                    'retiro',
                    $validated['monto'],
                    "Traspaso a {$destino->banco} - {$destino->nombre} // Ref: {$validated['referencia']}",
                    'traspaso'
                );
                
                // 3. Registrar Depósito en Destino
                $movimientoEntrada = $destino->registrarMovimiento(
                    'deposito',
                    $validated['monto'],
                    "Traspaso recibido de {$origen->banco} - {$origen->nombre}",
                    'traspaso'
                );
            });

            return redirect()->route('traspasos-bancarios.index')->with('success', 'Traspaso realizado correctamente.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar traspaso: ' . $e->getMessage()]);
        }
    }
}
