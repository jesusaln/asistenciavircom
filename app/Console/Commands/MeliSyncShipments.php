<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;
use App\Models\PedidoOnline;
use Illuminate\Support\Facades\Log;

class MeliSyncShipments extends Command
{
    protected $signature = 'meli:sync-shipments';
    protected $description = 'Sincronizar guías de envío de CVA → MercadoLibre';

    public function handle(MeliService $meli)
    {
        if (!$meli->isConfigured()) {
            $this->error('MercadoLibre no está configurado.');
            return 1;
        }

        $this->info('Sincronizando guías de envío con MercadoLibre...');

        // Buscar pedidos de ML que ya tienen guía de CVA pero no se ha notificado a ML
        $pedidos = PedidoOnline::where('metodo_pago', 'mercadolibre')
            ->whereNotNull('meli_order_id')
            ->whereNotNull('guia_envio')
            ->where('meli_tracking_notified', false)
            ->whereIn('estado', [PedidoOnline::ESTADO_ENVIADO, PedidoOnline::ESTADO_PAGADO])
            ->get();

        $count = $pedidos->count();
        $this->info("Se encontraron {$count} pedidos con guía pendiente de notificar a ML.");

        if ($count === 0) {
            return 0;
        }

        $notified = 0;
        $errors = 0;

        foreach ($pedidos as $pedido) {
            try {
                $this->processShipment($pedido, $meli);
                $notified++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("MeliSyncShipments: Error notificando guía", [
                    'pedido' => $pedido->numero_pedido,
                    'meli_order_id' => $pedido->meli_order_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Error en pedido {$pedido->numero_pedido}: {$e->getMessage()}");
            }
        }

        $this->info("Notificados a ML: {$notified}, Errores: {$errors}");
        return 0;
    }

    protected function processShipment(PedidoOnline $pedido, MeliService $meli): void
    {
        $shipmentId = $pedido->meli_shipment_id;

        if (!$shipmentId) {
            // Intentar obtener el shipment_id desde la orden de ML
            $orderDetail = $meli->getOrder($pedido->meli_order_id);
            $shipmentId = $orderDetail['shipping']['id'] ?? null;

            if ($shipmentId) {
                $pedido->update(['meli_shipment_id' => $shipmentId]);
            }
        }

        if (!$shipmentId) {
            $this->warn("  Pedido {$pedido->numero_pedido}: No tiene shipment_id de ML, saltando.");
            return;
        }

        // Obtener estado actual del envío en ML
        $shipment = $meli->getShipment($shipmentId);
        $currentStatus = $shipment['status'] ?? 'unknown';

        // Si ML ya lo tiene como shipped/delivered, solo marcar como notificado
        if (in_array($currentStatus, ['shipped', 'delivered'])) {
            $pedido->update(['meli_tracking_notified' => true]);
            $this->line("  Pedido {$pedido->numero_pedido}: ML ya tiene tracking ({$currentStatus}).");
            return;
        }

        // Informar tracking a ML actualizando el envío con datos de guía
        $trackingData = [
            'tracking_number' => $pedido->guia_envio,
        ];

        // Mapear paquetería a carrier ID si es posible
        if ($pedido->paqueteria) {
            $trackingData['tracking_method'] = $this->mapCarrier($pedido->paqueteria);
        }

        $result = $meli->put("/shipments/{$shipmentId}", $trackingData);

        if (isset($result['error'])) {
            // Si el error es porque ML ya gestiona el envío (MercadoEnvíos), no es un error real
            $errorMsg = $result['message'] ?? $result['error'] ?? '';
            if (str_contains(strtolower($errorMsg), 'fulfillment') || str_contains(strtolower($errorMsg), 'mercado envios')) {
                $pedido->update(['meli_tracking_notified' => true]);
                $this->line("  Pedido {$pedido->numero_pedido}: Envío gestionado por MercadoEnvíos.");
                return;
            }

            Log::warning("MeliSyncShipments: Error al actualizar tracking en ML", [
                'pedido' => $pedido->numero_pedido,
                'shipment_id' => $shipmentId,
                'error' => $result,
            ]);
            return;
        }

        // Marcar como notificado
        $pedido->update([
            'meli_tracking_notified' => true,
            'estado' => PedidoOnline::ESTADO_ENVIADO,
        ]);

        $pedido->registrarEvento(
            'MELI_TRACKING_NOTIFICADO',
            "Guía {$pedido->guia_envio} ({$pedido->paqueteria}) notificada a MercadoLibre",
            ['shipment_id' => $shipmentId, 'tracking' => $pedido->guia_envio]
        );

        $this->line("  ✅ Pedido {$pedido->numero_pedido}: Guía {$pedido->guia_envio} notificada a ML.");
    }

    /**
     * Mapear nombre de paquetería a método de tracking reconocido por ML
     */
    protected function mapCarrier(?string $paqueteria): string
    {
        if (!$paqueteria) {
            return 'other';
        }

        $map = [
            'PAQUETEXPRESS' => 'Paquetexpress',
            'DHL' => 'DHL',
            'FEDEX' => 'FedEx',
            'ESTAFETA' => 'Estafeta',
            'UPS' => 'UPS',
            'REDPACK' => 'Redpack',
            'SENDEX' => 'Sendex',
            '99MINUTOS' => '99 Minutos',
        ];

        $key = strtoupper(trim($paqueteria));
        return $map[$key] ?? $paqueteria;
    }
}
