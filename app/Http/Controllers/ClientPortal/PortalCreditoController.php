<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClienteDocumento;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use App\Models\Cliente;
use App\Models\User;
use App\Models\UserNotification;
use App\Mail\CreditSignatureMail;
use App\Models\PolizaServicio;

class PortalCreditoController extends Controller
{
    private function getEmpresaBranding()
    {
        $empresaId = EmpresaResolver::resolveId();
        $empresaModel = Empresa::find($empresaId);
        $configuracion = EmpresaConfiguracion::getConfig($empresaId);

        return $empresaModel ? array_merge($empresaModel->toArray(), [
            'color_principal' => $configuracion->color_principal,
            'color_secundario' => $configuracion->color_secundario,
            'color_terciario' => $configuracion->color_terciario,
            'logo_url' => $configuracion->logo_url,
            'favicon_url' => $configuracion->favicon_url,
            'nombre_comercial_config' => $configuracion->nombre_empresa,
        ]) : null;
    }

    public function index()
    {
        $cliente = Auth::guard('client')->user();
        $cliente->load('documentos');

        // Formatear para Inertia
        $clienteData = [
            'id' => $cliente->id,
            'nombre_razon_social' => $cliente->nombre_razon_social,
            'credito_activo' => $cliente->credito_activo,
            'limite_credito' => $cliente->limite_credito,
            'dias_credito' => $cliente->dias_credito,
            'estado_credito' => $cliente->estado_credito,
            'saldo_pendiente' => $cliente->saldo_pendiente,
            'credito_disponible' => $cliente->credito_disponible,
            'documentos' => $cliente->documentos,
            'credito_firma' => $cliente->credito_firma,
            'credito_firmado_at' => $cliente->credito_firmado_at,
            'credito_solicitado_monto' => $cliente->credito_solicitado_monto,
            'credito_solicitado_dias' => $cliente->credito_solicitado_dias,
            'rfc' => $cliente->rfc,
        ];

        return Inertia::render('Portal/Credito/Index', [
            'cliente' => $clienteData,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function storeDocumento(Request $request)
    {
        $cliente = Auth::guard('client')->user();

        $request->validate([
            'documento' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'tipo' => 'required|string|in:ine_frontal,ine_trasera,comprobante_domicilio,solicitud_credito,otro',
        ]);

        try {
            $file = $request->file('documento');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("clientes/{$cliente->id}/documentos", $filename, 'public');

            ClienteDocumento::create([
                'cliente_id' => $cliente->id,
                'tipo' => $request->tipo,
                'nombre_archivo' => $file->getClientOriginalName(),
                'ruta' => $path,
                'extension' => $file->getClientOriginalExtension(),
                'tamano' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return back()->with('success', 'Documento enviado correctamente. Nuestro equipo lo revisará a la brevedad.');
        } catch (\Exception $e) {
            Log::error('Error al subir documento desde portal:', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error al subir el documento. Intenta más tarde.');
        }
    }

    public function destroyDocumento(ClienteDocumento $documento)
    {
        $cliente = Auth::guard('client')->user();

        // Verificar pertenencia
        if ($documento->cliente_id !== $cliente->id) {
            abort(403);
        }

        // Solo permitir eliminar si no está autorizado ya? 
        // Por ahora dejamos libre, pero idealmente solo si en_revision o sin_credito
        if ($cliente->estado_credito === 'autorizado') {
            return back()->with('error', 'No puedes eliminar documentos de un crédito ya autorizado. Contacta a soporte si necesitas actualizarlos.');
        }

        try {
            if (Storage::disk('public')->exists($documento->ruta)) {
                Storage::disk('public')->delete($documento->ruta);
            }
            $documento->delete();
            return back()->with('success', 'Documento eliminado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar.');
        }
    }

    public function descargarSolicitud()
    {
        $cliente = Auth::guard('client')->user();
        $empresaId = EmpresaResolver::resolveId();
        $empresa = EmpresaConfiguracion::getConfig($empresaId);

        $logo = $empresa->logo_url ?? asset('images/logo.png');

        return view('portal.impresion.solicitud_credito', [
            'cliente' => $cliente,
            'empresa' => $empresa,
            'logo' => $logo,
            'fecha' => $cliente->credito_firmado_at ? $cliente->credito_firmado_at->format('d/m/Y') : now()->format('d/m/Y')
        ]);
    }

    public function firmarSolicitud()
    {
        $cliente = Auth::guard('client')->user();

        // Si ya está autorizado, no permitimos volver a firmar
        if ($cliente->estado_credito === 'autorizado') {
            return redirect()->route('portal.credito.index')
                ->with('info', 'Tu crédito ya ha sido autorizado.');
        }

        return Inertia::render('Portal/Credito/FirmarSolicitud', [
            'cliente' => $cliente,
            'empresa' => $this->getEmpresaBranding(),
        ]);
    }

    public function storeFirmaSolicitud(Request $request)
    {
        /** @var \App\Models\Cliente $cliente */
        $cliente = Auth::guard('client')->user();

        if ($cliente->estado_credito === 'autorizado') {
            return back()->with('error', 'Tu crédito ya ha sido autorizado.');
        }

        $request->validate([
            'firma' => 'required|string', // Base64
            'nombre_firmante' => 'required|string|min:3|max:255',
            'acepta_terminos' => 'accepted',
            'limite_solicitado' => 'required|numeric|min:0',
            'dias_credito_solicitados' => 'required|integer|min:0',
        ]);

        // Generar hash único
        $contenidoSolicitud = json_encode([
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre_razon_social,
            'rfc' => $cliente->rfc,
            'limite' => $request->limite_solicitado,
            'dias' => $request->dias_credito_solicitados,
            'firmante' => $request->nombre_firmante,
            'timestamp' => now()->toIso8601String(),
        ]);

        $firmaHash = hash('sha256', $contenidoSolicitud . $request->firma);

        // Guardar la firma en el modelo Cliente
        $cliente->update([
            'credito_firma' => $request->firma,
            'credito_firmado_at' => now(),
            'credito_firmado_ip' => $request->ip(),
            'credito_firmado_nombre' => $request->nombre_firmante,
            'credito_firma_hash' => $firmaHash,
            'credito_solicitado_monto' => $request->limite_solicitado,
            'credito_solicitado_dias' => $request->dias_credito_solicitados,
            'estado_credito' => 'en_revision', // Cambiamos estado automáticamente
        ]);

        Log::info("Solicitud de crédito firmada digitalmente", [
            'cliente_id' => $cliente->id,
            'firmante' => $request->nombre_firmante,
            'ip' => $request->ip(),
            'hash' => $firmaHash,
        ]);

        // --- NOTIFICACIONES ---
        try {
            // 1. Notificación en la campanita para todos los admins
            UserNotification::createCreditSignatureNotification($cliente);

            // 2. Correo electrónico a todos los admins
            $admins = User::role(['admin', 'super-admin'])->get();
            if ($admins->count() > 0) {
                Mail::to($admins)->send(new CreditSignatureMail($cliente));
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar notificaciones de firma de crédito: ' . $e->getMessage());
            // No detenemos el flujo si fallan las notificaciones
        }

        return redirect()->route('portal.credito.index')
            ->with('success', '¡Solicitud firmada y enviada correctamente! Estaremos revisándola a la brevedad.');
    }
}
