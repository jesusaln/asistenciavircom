<?php

namespace App\Services\Cfdi;

use App\Models\Cfdi;
use App\Models\EmpresaConfiguracion;
use App\Models\Venta;
use App\Services\ContpaqiService;
use App\Services\Cfdi\CertService;
use App\Services\VentaCancellationService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CfdiCancelService
{
    private $contpaqiService;
    private $certService;
    private $ventaCancellationService;

    public function __construct(
        ContpaqiService $contpaqiService,
        CertService $certService,
        VentaCancellationService $ventaCancellationService
    ) {
        $this->contpaqiService = $contpaqiService;
        $this->certService = $certService;
        $this->ventaCancellationService = $ventaCancellationService;
    }

    /**
     * Cancela un CFDI ante el SAT
     * 
     * @param Cfdi $cfdi El modelo CFDI a cancelar
     * @param string $motivo Motivo de cancelación (01, 02, 03, 04)
     * @param string|null $folioSustitucion UUID de sustitución (requerido para motivo 01)
     * @return array ['success' => bool, 'message' => string]
     */
    public function cancelar(Cfdi $cfdi, string $motivo, ?string $folioSustitucion = null): array
    {
        try {
            // 1. Validaciones previas
            if ($motivo === '01' && empty($folioSustitucion)) {
                return ['success' => false, 'message' => 'Para motivo 01 se requiere el folio de sustitución.'];
            }

            // 2. Obtener Credenciales y Certificados usando CertService (mismo que timbrado)
            $config = EmpresaConfiguracion::getConfig();
            if (!$config) {
                return ['success' => false, 'message' => 'No se encontró configuración de empresa.'];
            }

            // ✅ SECURITY FIX: Validate certificate before using
            $certValidation = $this->certService->validateCertificate();
            if (!$certValidation['success']) {
                return ['success' => false, 'message' => $certValidation['message']];
            }

            // Usar CertService para obtener los certificados en el formato correcto
            $keyB64 = $this->certService->getCsdKeyB64();
            $cerB64 = $this->certService->getCsdCerB64();
            $passCsd = $config->csd_password ?? env('CSD_PASSWORD');

            Log::info('CSD usando CertService:', [
                'keyB64_length' => strlen($keyB64 ?? ''),
                'cerB64_length' => strlen($cerB64 ?? ''),
                // ✅ SECURITY: Never log password information
            ]);

            if (!$keyB64 || !$cerB64) {
                return ['success' => false, 'message' => 'No se pudieron cargar los certificados CSD.'];
            }

            // 3. Llamar al servicio activo
            if (config('services.contpaqi.enabled')) {
                $res = $this->contpaqiService->cancelarFactura($cfdi->uuid, $motivo, $folioSustitucion);

                $cfdi->estatus = Cfdi::ESTATUS_CANCELADO;
                $cfdi->motivo_cancelacion = $motivo;
                $cfdi->folio_sustitucion = $folioSustitucion;
                $cfdi->fecha_cancelacion = now();
                $cfdi->save();

                // ✅ Cancelar la venta asociada para revertir inventario y pagos
                $this->cancelarVentaAsociada($cfdi, $motivo);

                Log::info("CFDI {$cfdi->uuid} cancelado exitosamente vía Contpaqi.");
                return [
                    'success' => true,
                    'message' => 'Solicitud de cancelación enviada exitosamente vía Contpaqi Bridge. Venta e inventario revertidos.',
                    'data' => $res
                ];
            }

            throw new \Exception("No hay un servicio de cancelación activo configurado (FacturaLOPlus fue removido).");

        } catch (\Exception $e) {
            Log::error("Excepción cancelando CFDI {$cfdi->uuid}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno al cancelar: ' . $e->getMessage()];
        }
    }

    /**
     * Cancela la venta asociada al CFDI para revertir inventario y pagos
     * 
     * @param Cfdi $cfdi El CFDI cancelado
     * @param string $motivo Motivo de cancelación del SAT
     */
    protected function cancelarVentaAsociada(Cfdi $cfdi, string $motivo): void
    {
        // Encontrar la venta asociada al CFDI
        if (!$cfdi->cfdiable_type || !$cfdi->cfdiable_id) {
            Log::info("CFDI {$cfdi->uuid} no tiene venta asociada (cfdiable), no se revierte inventario.");
            return;
        }

        // Verificar que sea una venta
        if ($cfdi->cfdiable_type !== Venta::class && $cfdi->cfdiable_type !== 'App\Models\Venta') {
            Log::info("CFDI {$cfdi->uuid} está asociado a {$cfdi->cfdiable_type}, no a una Venta. No se revierte inventario automáticamente.");
            return;
        }

        $venta = Venta::find($cfdi->cfdiable_id);

        if (!$venta) {
            Log::warning("CFDI {$cfdi->uuid} referencia venta ID {$cfdi->cfdiable_id} pero no existe.");
            return;
        }

        // Verificar si la venta ya está cancelada
        if ($venta->estado?->value === \App\Enums\EstadoVenta::Cancelada->value) {
            Log::info("Venta {$venta->numero_venta} ya estaba cancelada, no se requiere acción adicional.");
            return;
        }

        try {
            // Cancelar la venta con forceWithPayments=true para revertir todo
            $motivoCancelacion = "Cancelación de CFDI (motivo SAT: {$motivo})";
            $this->ventaCancellationService->cancelVenta($venta, $motivoCancelacion, true);

            Log::info("Venta {$venta->numero_venta} cancelada automáticamente por cancelación de CFDI {$cfdi->uuid}");
        } catch (\Exception $e) {
            Log::error("Error al cancelar venta {$venta->numero_venta} por CFDI: " . $e->getMessage());
            // No lanzamos excepción para no interrumpir el flujo de cancelación del CFDI
            // pero se registra el error para investigación
        }
    }
}
