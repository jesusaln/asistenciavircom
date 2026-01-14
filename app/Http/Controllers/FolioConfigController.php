<?php

namespace App\Http\Controllers;

use App\Models\FolioConfig;
use App\Services\Folio\FolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FolioConfigController extends Controller
{
    protected $folioService;

    public function __construct(FolioService $folioService)
    {
        $this->folioService = $folioService;
    }

    public function index()
    {
        // Return existing configs, creating defaults if missing for standard types
        $types = ['cotizacion', 'pedido', 'venta'];
        $configs = [];

        foreach ($types as $type) {
            $config = FolioConfig::firstOrCreate(
                ['document_type' => $type],
                [
                    'prefix' => strtoupper(substr($type, 0, 1)),
                    'current_number' => 0,
                    'padding' => 4
                ]
            );
            $configs[] = $config;
        }

        return response()->json($configs);
    }

    public function update(Request $request, $id)
    {
        $config = FolioConfig::findOrFail($id);

        $validated = $request->validate([
            'prefix' => 'required|string|max:5|alpha_num',
            'padding' => 'required|integer|min:3|max:10',
        ]);

        // Prevent changing prefix if configured to check for existence (UI logic enforcement)
        // Backend enforcement: If records exist with OLD prefix, maybe warn? 
        // For now, allow change, but user knows it resets sequence visually if not synced.

        $config->update($validated);

        return back()->with('success', 'Configuración de folios actualizada.');
    }

    public function sync(Request $request, $id)
    {
        $config = FolioConfig::findOrFail($id);

        try {
            $maxFound = $this->folioService->analyzeAndRepair($config->document_type);

            return back()->with('success', "Sincronización completada. Último folio encontrado: {$config->prefix}{$maxFound}");
        } catch (\Exception $e) {
            return back()->with('error', "Error al sincronizar: " . $e->getMessage());
        }
    }
}
