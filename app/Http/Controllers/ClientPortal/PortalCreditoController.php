<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ClienteDocumento;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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
            'tipo' => 'required|string|in:ine_frontal,ine_trasera,comprobante_domicilio,otro',
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
}
