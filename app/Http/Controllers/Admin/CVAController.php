<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CVAService;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CVAController extends Controller
{
    protected $cva;

    public function __construct(CVAService $cva)
    {
        $this->cva = $cva;
    }

    /**
     * Vista de importación de productos CVA
     */
    public function importView()
    {
        return Inertia::render('Admin/CVA/Import', [
            'config' => EmpresaConfiguracion::getConfig()
        ]);
    }

    /**
     * Buscar productos en CVA (Proxy para el Admin)
     */
    public function search(Request $request)
    {
        $filters = $request->only(['desc', 'marca', 'grupo', 'page']);

        $result = $this->cva->getCatalogo($filters);

        if (isset($result['error'])) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * Importar producto a catálogo local
     */
    public function import(Request $request)
    {
        $request->validate([
            'clave' => 'required|string'
        ]);

        try {
            $result = $this->cva->importProduct($request->clave);

            if (isset($result['error'])) {
                return response()->json($result, 422);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error importing CVA product: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al importar el producto'], 500);
        }
    }
}
