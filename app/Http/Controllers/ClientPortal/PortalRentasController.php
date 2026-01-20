<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\Renta;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\EmpresaResolver;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PortalRentasController extends Controller
{
    public function index()
    {
        $cliente = Auth::guard('client')->user();

        $rentas = Renta::where('cliente_id', $cliente->id)
            ->with(['equipos'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Portal/Rentas/Index', [
            'rentas' => $rentas,
        ]);
    }

    public function show(Renta $renta)
    {
        if ($renta->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $renta->load(['equipos', 'cliente']);

        return Inertia::render('Portal/Rentas/Show', [
            'renta' => $renta,
        ]);
    }

    public function firmar(Renta $renta)
    {
        if ($renta->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        if ($renta->firma_digital) {
            return redirect()->route('portal.rentas.index')
                ->with('warning', 'Este contrato ya ha sido firmado.');
        }

        $renta->load(['equipos', 'cliente']);

        $empresaId = EmpresaResolver::resolveId();
        $configuracion = [
            'empresa' => EmpresaConfiguracion::where('empresa_id', $empresaId)->first(),
            'colores' => [
                'principal' => '#2b6cb0',
                'secundario' => '#1a365d'
            ]
        ];

        return Inertia::render('Portal/Rentas/Firmar', [
            'renta' => $renta,
            'configuracion' => $configuracion,
        ]);
    }

    public function storeFirma(Request $request, Renta $renta)
    {
        if ($renta->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $request->validate([
            'firma' => 'required|string', // Base64
            'nombre_firmante' => 'required|string|max:255',
            'ine_frontal' => 'nullable|string',
            'ine_trasera' => 'nullable|string',
            'comprobante_domicilio' => 'nullable|string',
            'solicitud_renta' => 'nullable|string',
        ]);

        $firmaHash = hash('sha256', $request->firma . '|' . $renta->id . '|' . now()->toDateTimeString());

        $renta->update([
            'firma_digital' => $request->firma,
            'firmado_at' => now(),
            'firmado_ip' => $request->ip(),
            'firmado_nombre' => $request->nombre_firmante,
            'firma_hash' => $firmaHash,
            'fecha_firma' => now(),
            'estado' => 'activo',
            'ine_frontal' => $request->ine_frontal,
            'ine_trasera' => $request->ine_trasera,
            'comprobante_domicilio' => $request->comprobante_domicilio,
            'solicitud_renta' => $request->solicitud_renta,
        ]);

        // Notificar a los administradores
        try {
            $this->notificarAdminFirma($renta);
        } catch (\Exception $e) {
            Log::error('Error notificando firma de renta: ' . $e->getMessage());
        }

        return redirect()->route('portal.rentas.index')
            ->with('success', 'Contrato de renta firmado digitalmente y documentos recibidos con éxito.');
    }

    public function uploadDocumento(Request $request, Renta $renta)
    {
        if ($renta->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $request->validate([
            'documento' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'tipo' => 'required|string|in:ine_frontal,ine_trasera,comprobante_domicilio,solicitud_renta',
        ]);

        try {
            $file = $request->file('documento');
            $filename = time() . '_' . $request->tipo . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("rentas/{$renta->id}/documentos", $filename, 'public');

            // Devolver la ruta para que Inertia la guarde en el form
            return response()->json([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al subir archivo'], 500);
        }
    }

    public function descargarContrato(Renta $renta)
    {
        if ($renta->cliente_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        $renta->load(['cliente', 'equipos']);

        // Usamos el mismo logic que RentasContratoController
        $empresaId = EmpresaResolver::resolveId();
        $config = EmpresaConfiguracion::where('empresa_id', $empresaId)->first();

        $configuracion = [
            'empresa' => [
                'nombre' => $config->nombre_empresa,
                'razon_social' => $config->nombre_empresa,
                'rfc' => $config->rfc,
                'direccion' => $config->direccion,
                'telefono' => $config->telefono,
                'email' => $config->email,
                'logo_url' => $config->logo_url,
                'banco' => $config->banco_nombre,
                'sucursal' => $config->banco_sucursal,
                'cuenta' => $config->banco_cuenta,
                'clabe' => $config->banco_clabe,
                'titular' => $config->banco_titular,
            ],
            'colores' => [
                'principal' => $config->color_principal ?? '#2b6cb0',
                'secundario' => $config->color_secundario ?? '#1a365d',
            ],
            'logo_path' => $config->logo_url
        ];

        $pdf = Pdf::loadView('rentas.contrato', [
            'renta' => $renta,
            'configuracion' => $configuracion,
        ]);

        $pdf->setPaper('letter', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        $filename = 'contrato-renta-' . ($renta->numero_contrato ?: $renta->id) . '.pdf';
        return $pdf->download($filename);
    }

    private function notificarAdminFirma(Renta $renta)
    {
        UserNotification::createRentaSignatureNotification($renta);
    }
}
