<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use App\Models\Venta;
use App\Services\SwSapienService;
use App\Services\Ventas\VentaCancellationService;
use Illuminate\Support\Facades\Log;

class CfdiCancelService
{
    private SwSapienService $swSapienService;
    private VentaCancellationService $ventaCancellationService;

    public function __construct(
        SwSapienService $swSapienService,
        VentaCancellationService $ventaCancellationService
    ) {
        $this->swSapienService = $swSapienService;
        $this->ventaCancellationService = $ventaCancellationService;
    }

    /**
     * Cancela un CFDI ante el SAT mediante SW Sapien
     */
    public function cancelar(Cfdi $cfdi, string $motivo, ?string $folioSustitucion = null): array
    {
        try {
            if ($motivo === '01' && empty($folioSustitucion)) {
                return ['success' => false, 'message' => 'Para motivo 01 se requiere el folio (UUID) de sustitución.'];
            }

            if (!$cfdi->uuid) {
                return [
                    'success' => false,
                    'message' => 'El documento no contiene el UUID para su cancelación en la nube.'
                ];
            }

            $pacService = $this->getPacService();
            $res = $pacService->cancelarCfdi($cfdi, $motivo, $folioSustitucion);

            if ($res['success']) {
                $cfdi->estatus = Cfdi::ESTATUS_CANCELADO;
                $cfdi->motivo_cancelacion = $motivo;
                $cfdi->folio_sustitucion = $folioSustitucion;
                $cfdi->fecha_cancelacion = now();
                $cfdi->acuse_cancelacion = $res['acuse'] ?? null;
                $cfdi->save();

                // Revertir inventario y pagos asociados a la venta
                $this->cancelarVentaAsociada($cfdi, $motivo);

                $pacName = 'SW Sapien';
                Log::info("CFDI {$cfdi->uuid} cancelado exitosamente en {$pacName}.");
                return [
                    'success' => true,
                    'message' => "Solicitud de cancelación procesada exitosamente en el SAT vía {$pacName}. Venta e inventario revertidos.",
                    'data' => $res
                ];
            }

            $pacName = 'SW Sapien';
            return ['success' => false, 'message' => $res['message'] ?? "Error al cancelar en {$pacName}."];

        } catch (\Exception $e) {
            Log::error("Excepción cancelando CFDI {$cfdi->uuid}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al cancelar: ' . $e->getMessage()];
        }
    }

    /**
     * Libera la venta asociada al CFDI cancelado para permitir que sea re-facturada,
     * sin revertir inventario ni pagos ya que la operación comercial sigue siendo válida.
     */
    protected function cancelarVentaAsociada(Cfdi $cfdi, string $motivo): void
    {
        if (!$cfdi->cfdiable_type || !$cfdi->cfdiable_id) {
            return;
        }

        if ($cfdi->cfdiable_type !== Venta::class && $cfdi->cfdiable_type !== 'App\Models\Venta') {
            return;
        }

        $venta = Venta::find($cfdi->cfdiable_id);

        if (!$venta) {
            return;
        }

        try {
            $changed = false;

            // Desvincular la factura para que pueda volver a ser facturada
            if ($venta->factura_id !== null) {
                $venta->factura_id = null;
                $changed = true;
            }

            // Asegurarnos de que el estado no sea Cancelada (debe permanecer en Aprobada o Pagado)
            if ($venta->estado?->value === \App\Enums\EstadoVenta::Cancelada->value) {
                $venta->estado = $venta->pagado ? \App\Enums\EstadoVenta::Pagado : \App\Enums\EstadoVenta::Aprobada;
                $changed = true;
            }

            if ($changed) {
                $venta->save();
            }

            Log::info("Venta {$venta->numero_venta} liberada y desvinculada del CFDI cancelado {$cfdi->uuid} para re-facturación.");
        } catch (\Exception $e) {
            Log::error("Error al liberar venta {$venta->numero_venta} por CFDI cancelado: " . $e->getMessage());
        }
    }

    /**
     * Obtener el servicio PAC activo (SW Sapiens).
     */
    protected function getPacService()
    {
        return $this->swSapienService;
    }
}
