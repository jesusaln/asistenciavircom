<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CVAService;
use App\Models\PedidoOnline;
use Illuminate\Support\Facades\Log;

class SyncCvaOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cva:sync-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar estatus y guías de envío de pedidos CVA';

    /**
     * Execute the console command.
     */
    public function handle(CVAService $cvaService)
    {
        $this->info('Iniciando sincronización de pedidos CVA...');

        // Solo procesar pedidos pagados que tengan ID de CVA y que NO estén ya entregados/cancelados
        $pedidos = PedidoOnline::where('estado', PedidoOnline::ESTADO_PAGADO)
            ->whereNotNull('cva_pedido_id')
            ->whereNull('guia_envio') // Si ya tiene guía, asumimos que ya lo procesamos, aunque podríamos actualizar estatus de entrega luego
            ->get();

        $count = $pedidos->count();
        $this->info("Se encontraron {$count} pedidos pendientes de guía.");

        foreach ($pedidos as $pedido) {
            $this->processOrder($pedido, $cvaService);
        }

        $this->info('Sincronización finalizada.');
    }

    protected function processOrder(PedidoOnline $pedido, CVAService $cvaService)
    {
        $this->line("Procesando pedido local: {$pedido->numero_pedido} (CVA: {$pedido->cva_pedido_id})");

        try {
            $detalle = $cvaService->getOrderDetails($pedido->cva_pedido_id);

            if (!$detalle) {
                $this->error("No se pudo obtener detalle del pedido CVA {$pedido->cva_pedido_id}");
                return;
            }

            // Analizar estatus y guías
            // Estructura esperada:
            // $detalle puede tener 'estatus', 'guias' (array), 'facturas', etc.

            $estatusCva = strtoupper($detalle['estatus'] ?? '');

            // Si tiene guías de rastreo
            if (!empty($detalle['guias']) && is_array($detalle['guias'])) {
                $guia = $detalle['guias'][0]['guia'] ?? null;
                $paqueteria = $detalle['guias'][0]['paqueteria'] ?? null;

                if ($guia) {
                    $pedido->update([
                        'guia_envio' => $guia,
                        'paqueteria' => $paqueteria,
                        'estado' => PedidoOnline::ESTADO_ENVIADO,
                        'enviado_at' => now(),
                    ]);

                    $this->info("Pedido actualizado: Enviado con guía {$guia}");

                    $pedido->registrarEvento(
                        'GUIA_ASIGNADA',
                        "Guía asignada automáticamente: {$paqueteria} - {$guia}",
                        ['guia' => $guia, 'paqueteria' => $paqueteria, 'origen' => 'CVA']
                    );

                    // Aquí se podría enviar notificación al cliente
                    // Notification::route('mail', $pedido->email)->notify(new OrderShipped($pedido));
                }
            } elseif ($estatusCva === 'FACTURADO' || $estatusCva === 'SURTIDO') {
                // Si ya está facturado pero aún sin guía, podríamos actualizar notas
                $this->line("Pedido CVA está en estatus: {$estatusCva}. Esperando guía.");
            } elseif ($estatusCva === 'CANCELADO') {
                // Cuidado con cancelar automáticamente, mejor notificar al admin
                $this->warn("¡Alerta! El pedido CVA informa estar CANCELADO.");
                $pedido->registrarEvento('ALERTA_CVA', "CVA reporta pedido CANCELADO. Revisar manualmente.", $detalle);
            }

        } catch (\Exception $e) {
            Log::error("Error sincronizando pedido {$pedido->numero_pedido}: " . $e->getMessage());
            $this->error("Excepción: " . $e->getMessage());
        }
    }
}
