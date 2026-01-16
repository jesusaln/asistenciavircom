<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClienteDocumentoController extends Controller
{
    public function store(Request $request, Cliente $cliente)
    {
        $request->validate([
            'documento' => 'required|file|max:5120', // 5MB max
            'tipo' => 'required|string',
        ]);

        try {
            $file = $request->file('documento');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs("clientes/{$cliente->id}/documentos", $filename, 'public');

            $documento = ClienteDocumento::create([
                'cliente_id' => $cliente->id,
                'tipo' => $request->tipo,
                'nombre_archivo' => $file->getClientOriginalName(),
                'ruta' => $path,
                'extension' => $file->getClientOriginalExtension(),
                'tamano' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return back()->with('success', 'Documento subido correctamente');
        } catch (\Exception $e) {
            Log::error('Error al subir documento de cliente:', [
                'cliente_id' => $cliente->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error al subir el documento');
        }
    }

    public function destroy(ClienteDocumento $documento)
    {
        try {
            // Eliminar archivo físico
            if (Storage::disk('public')->exists($documento->ruta)) {
                Storage::disk('public')->delete($documento->ruta);
            }

            $documento->delete();

            return back()->with('success', 'Documento eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar documento de cliente:', [
                'documento_id' => $documento->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Error al eliminar el documento');
        }
    }
}
