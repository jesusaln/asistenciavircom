<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\PolizaMantenimiento;
use App\Services\PolizaMantenimientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalMantenimientoController extends Controller
{
    protected $servicio;

    public function __construct(PolizaMantenimientoService $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'mantenimiento_id' => 'required|exists:poliza_mantenimientos,id',
            'fecha_solicitada' => 'required|date|after_or_equal:today',
            'hora_solicitada' => 'required|string', // formato HH:mm
            'notas' => 'nullable|string|max:500',
        ]);

        $mantenimiento = PolizaMantenimiento::findOrFail($validated['mantenimiento_id']);

        // Verificar que la póliza pertenezca al cliente
        if ($mantenimiento->poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403, 'No tienes permiso para solicitar este mantenimiento.');
        }

        // Combinar fecha y hora
        $fechaCompleta = $validated['fecha_solicitada'] . ' ' . $validated['hora_solicitada'];

        try {
            $this->servicio->solicitarMantenimientoManual(
                $mantenimiento,
                $fechaCompleta,
                $validated['notas'] ?? 'Solicitado desde portal cliente'
            );

            return back()->with('success', 'Mantenimiento solicitado correctamente. Un técnico confirmará la fecha.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al solicitar mantenimiento: ' . $e->getMessage());
        }
    }
}
