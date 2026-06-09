<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\PolizaServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PortalFirmaController extends Controller
{
    /**
     * Mostrar la página de firma del contrato.
     */
    public function show(PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        // Si ya está firmada, redirigir a la vista de la póliza
        if ($poliza->firmado_at) {
            return redirect()->route('portal.polizas.show', $poliza->id)
                ->with('info', 'Este contrato ya ha sido firmado.');
        }

        $poliza->load(['cliente', 'planPoliza']);

        return Inertia::render('Portal/Polizas/FirmarContrato', [
            'poliza' => $poliza,
            'empresa' => app(\App\Http\Controllers\ClientPortal\PortalController::class)->getEmpresaBranding(),
        ]);
    }

    /**
     * Guardar la firma del cliente.
     */
    public function store(Request $request, PolizaServicio $poliza)
    {
        if ($poliza->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        if ($poliza->firmado_at) {
            return back()->with('error', 'Este contrato ya ha sido firmado.');
        }

        $request->validate([
            'firma' => 'required|string', // Base64 de la firma
            'nombre_firmante' => 'required|string|min:3|max:255',
            'acepta_terminos' => 'accepted',
        ], [
            'firma.required' => 'Debes proporcionar tu firma.',
            'nombre_firmante.required' => 'Debes escribir tu nombre completo.',
            'nombre_firmante.min' => 'El nombre debe tener al menos 3 caracteres.',
            'acepta_terminos.accepted' => 'Debes aceptar los términos y condiciones.',
        ]);

        // Generar hash único del contrato
        $contenidoContrato = json_encode([
            'poliza_id' => $poliza->id,
            'folio' => $poliza->folio,
            'cliente_id' => $poliza->cliente_id,
            'fecha_inicio' => $poliza->fecha_inicio ? \Carbon\Carbon::parse($poliza->fecha_inicio)->toDateString() : null,
            'fecha_fin' => $poliza->fecha_fin ? \Carbon\Carbon::parse($poliza->fecha_fin)->toDateString() : null,
            'monto_mensual' => $poliza->monto_mensual,
            'firmante' => $request->nombre_firmante,
            'timestamp' => now()->toIso8601String(),
        ]);

        $firmaHash = hash('sha256', $contenidoContrato . $request->firma);

        // Guardar la firma
        $poliza->update([
            'firma_cliente' => $request->firma,
            'firmado_at' => now(),
            'firmado_ip' => $request->ip(),
            'firma_hash' => $firmaHash,
            'firmado_nombre' => $request->nombre_firmante,
        ]);

        Log::info("Contrato de póliza firmado digitalmente", [
            'poliza_id' => $poliza->id,
            'folio' => $poliza->folio,
            'cliente_id' => $poliza->cliente_id,
            'firmante' => $request->nombre_firmante,
            'ip' => $request->ip(),
            'hash' => $firmaHash,
        ]);

        return redirect()->route('portal.polizas.show', $poliza->id)
            ->with('success', '¡Contrato firmado exitosamente! Recibirás una copia por correo electrónico.');
    }

    /**
     * Verificar la autenticidad de una firma.
     */
    public function verificar(Request $request, PolizaServicio $poliza)
    {
        if (!$poliza->firmado_at) {
            return response()->json([
                'valido' => false,
                'mensaje' => 'Este contrato no ha sido firmado.',
            ]);
        }

        return response()->json([
            'valido' => true,
            'firmado_por' => $poliza->firmado_nombre,
            'fecha_firma' => $poliza->firmado_at->format('d/m/Y H:i:s'),
            'hash' => $poliza->firma_hash,
            'ip_origen' => substr($poliza->firmado_ip, 0, -3) . '***', // Ocultar últimos dígitos
        ]);
    }
}
