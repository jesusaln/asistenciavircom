<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\Contab\CuentaContable;
use App\Models\Contab\PolizaContable;
use App\Services\Contab\ContabilidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContabilidadApiController extends Controller
{
    use ApiResponse;

    protected ContabilidadService $service;

    public function __construct(ContabilidadService $service)
    {
        $this->service = $service;
    }

    /**
     * Subir XML y generar póliza automáticamente
     */
    public function uploadXml(Request $request)
    {
        $request->validate([
            'xml' => 'required|file|mimes:xml,txt',
        ]);

        try {
            $user = $request->user();
            $file = $request->file('xml');
            $xmlContent = file_get_contents($file->getRealPath());

            $poliza = $this->service->generarPolizaDesdeXml(
                $xmlContent,
                $user->empresa_id,
                $user->id
            );

            return $this->success([
                'poliza_id' => $poliza->id,
                'numero' => $poliza->numero,
                'total' => $poliza->total,
                'concepto' => $poliza->concepto,
                'message' => 'Póliza generada correctamente en borrador.',
            ]);

        } catch (\Exception $e) {
            Log::error("Error al cargar XML contable: " . $e->getMessage());
            return $this->error("Error: " . $e->getMessage(), 422);
        }
    }

    /**
     * Listado de pólizas
     */
    public function indexPolizas(Request $request)
    {
        $user = $request->user();
        $query = PolizaContable::where('empresa_id', $user->empresa_id)
            ->with(['asientos.cuenta'])
            ->orderBy('fecha', 'desc')
            ->orderBy('numero', 'desc');

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return $this->success($query->paginate($request->per_page ?? 20));
    }

    /**
     * Catálogo de cuentas
     */
    public function getCatalog(Request $request)
    {
        $user = $request->user();
        $cuentas = CuentaContable::where('empresa_id', $user->empresa_id)
            ->orderBy('codigo')
            ->get();

        return $this->success($cuentas);
    }
}
