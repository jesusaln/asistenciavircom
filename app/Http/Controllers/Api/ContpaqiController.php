<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\ContpaqiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContpaqiController extends Controller
{
    public function __construct(
        protected ContpaqiService $contpaqiService
    ) {
    }

    /**
     * Timbrar una venta existente enviándola a CONTPAQi
     */
    public function timbrarVenta(Request $request, $id)
    {
        try {
            $venta = Venta::with(['cliente', 'items.ventable'])->findOrFail($id);

            // Validar estado previo si es necesario
            if ($venta->cfdis()->where('estatus', 'vigente')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta venta ya tiene una factura vigente asociada.'
                ], 400);
            }

            // Procesar con el servicio
            $resultado = $this->contpaqiService->procesarVenta($venta);

            if ($resultado['success']) {
                // Guardar referencia local del folio/uuid si es necesario
                // Por ahora solo logueamos
                Log::info("Venta {$id} timbrada en CONTPAQi: " . json_encode($resultado));

                // Opcional: Crear registro en tabla 'facturas' o 'cfdis' local
                // $venta->cfdis()->create([...]);
            }

            return response()->json($resultado, $resultado['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("Error timbrando venta {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno al timbrar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una factura via CONTPAQi
     * 
     * Motivos de cancelación SAT:
     * 01 - Comprobantes emitidos con errores con relación (requiere folioSustitucion)
     * 02 - Comprobantes emitidos con errores sin relación
     * 03 - No se llevó a cabo la operación
     * 04 - Operación nominativa relacionada en factura global
     */
    public function cancelarFactura(Request $request, $uuid)
    {
        $request->validate([
            'motivo' => 'required|in:01,02,03,04',
            'folio_sustitucion' => 'nullable|string|uuid',
        ]);

        try {
            $cfdi = \App\Models\Cfdi::where('uuid', $uuid)->first();

            if (!$cfdi) {
                return response()->json([
                    'success' => false,
                    'message' => 'CFDI no encontrado en el sistema local.'
                ], 404);
            }

            if ($cfdi->estatus === 'cancelado' || $cfdi->estatus === 'cancelado_con_acuse') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta factura ya está cancelada.'
                ], 400);
            }

            $motivo = $request->input('motivo', '02');
            $folioSustitucion = $request->input('folio_sustitucion');

            // Llamar al servicio de CONTPAQi
            $resultado = $this->contpaqiService->cancelarFactura($uuid, $motivo, $folioSustitucion);

            // Si fue exitoso, actualizar el registro local
            if (isset($resultado['success']) && $resultado['success']) {
                $cfdi->update([
                    'estatus' => 'cancelado',
                    'fecha_cancelacion' => now(),
                    'motivo_cancelacion' => $motivo,
                    'folio_sustitucion' => $folioSustitucion,
                    'acuse_cancelacion' => $resultado['acuse'] ?? null,
                ]);

                Log::info("CFDI {$uuid} cancelado exitosamente via CONTPAQi");
            }

            return response()->json($resultado, $resultado['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("Error cancelando CFDI {$uuid}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado de conexión con el Bridge
     */
    public function status()
    {
        return response()->json($this->contpaqiService->checkStatus());
    }
}
