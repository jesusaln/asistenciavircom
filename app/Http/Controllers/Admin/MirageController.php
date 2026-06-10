<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Services\MirageScraperService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MirageController extends Controller
{
    /**
     * Lista de solicitudes importadas de Mirage.
     */
    public function index()
    {
        $solicitudes = Cita::with('cliente')
            ->where('empresa_id', 8)
            ->where('folio', 'LIKE', 'MS-%')
            ->whereIn('estado', [Cita::ESTADO_PENDIENTE, Cita::ESTADO_PROGRAMADO])
            ->orderBy('created_at', 'DESC')
            ->paginate(20);

        return Inertia::render('Admin/Mirage/Index', [
            'solicitudes' => $solicitudes
        ]);
    }

    /**
     * Vista para iniciar la sincronización manual.
     */
    public function syncView()
    {
        return Inertia::render('Admin/Mirage/Sync');
    }

    /**
     * Ejecuta el robot de sincronización y devuelve los resultados sin guardar.
     */
    public function sync(MirageScraperService $scraper)
    {
        $result = $scraper->scrapeAndImport();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Registra manualmente un cliente desde la información de Mirage.
     */
    public function storeClient(Request $request)
    {
        $request->validate([
            'folio' => 'required',
            'nombre' => 'required',
            'telefono' => 'required'
        ]);

        // Limpiar el teléfono de guiones y espacios
        $telefonoLimpio = preg_replace('/[^0-9]/', '', $request->telefono);

        // Parseo rápido de dirección para la tabla de clientes
        $direccion = $request->direccion;
        // Parseo mejorado de dirección
        $direccion = $request->direccion;
        $calle = $direccion; $numExt = ''; $colonia = ''; $cp = ''; $municipio = 'Hermosillo'; $estado = 'Sonora';

        // Intentar extraer el Código Postal (5 dígitos)
        if (preg_match('/(\d{5})/', $direccion, $cpMatch)) {
            $cp = $cpMatch[1];
        }

        // Intentar separar Calle y Número (formato: CALLE 123 - COLONIA)
        if (preg_match('/^(.*?)\s+(\d+)\s*-\s*(.*)$/', $direccion, $matches)) {
            $calle = trim($matches[1]);
            $numExt = trim($matches[2]);
            $colonia = trim(str_replace($cp, '', $matches[3])); // Quitar el CP de la colonia
        }

        $cliente = \App\Models\Cliente::where('empresa_id', 8)
            ->where(function($query) use ($request, $telefonoLimpio) {
                $query->where('telefono', $telefonoLimpio)
                      ->orWhere('nombre_razon_social', $request->nombre);
            })->first();

        if (!$cliente) {
            $cliente = \App\Models\Cliente::create([
                'nombre_razon_social' => $request->nombre,
                'telefono' => $telefonoLimpio,
                'calle' => $calle,
                'numero_exterior' => $numExt,
                'colonia' => $colonia,
                'codigo_postal' => $cp,
                'municipio' => $municipio,
                'estado' => $estado,
                'tipo' => 'particular',
                'empresa_id' => 8,
                'notas' => "Registrado manualmente desde Mirage. Folio: " . $request->folio
            ]);
            $message = "Cliente registrado exitosamente y vinculado a la cita.";
        } else {
            $message = "El cliente ya existía. Se ha vinculado el nuevo folio a su expediente.";
        }

        // Vincular automáticamente a cualquier cita existente con ese folio
        \App\Models\Cita::where('folio', $request->folio)
            ->where('empresa_id', 8)
            ->update(['cliente_id' => $cliente->id]);

        return response()->json([
            'success' => true,
            'cliente_id' => $cliente->id,
            'message' => $message
        ]);
    }
}
