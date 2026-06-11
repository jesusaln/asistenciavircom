<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MeliService;
use App\Services\CVAService;
use App\Models\PedidoOnline;
use App\Models\MercadoLibreListing;
use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MeliSyncOrders extends Command
{
    protected $signature = 'meli:sync-orders
                            {--hours=2 : Buscar órdenes de las últimas N horas}
                            {--dry-run : Solo mostrar qué se procesaría sin hacer cambios}';

    protected $description = 'Procesar órdenes pagadas de MercadoLibre → crear PedidoOnline + enviar a CVA';

    public function handle(MeliService $meli, CVAService $cvaService)
    {
        if (!$meli->isConfigured()) {
            $this->error('MercadoLibre no está configurado.');
            return 1;
        }

        $config = EmpresaConfiguracion::getConfig();
        if (!$config || !$config->meli_user_id) {
            $this->error('No se ha completado la autenticación con MercadoLibre (falta user_id).');
            return 1;
        }

        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $created = 0;
        $skipped = 0;
        $errors = 0;

        $this->info("Buscando órdenes pagadas de las últimas {$hours} horas...");

        // Consultar órdenes pagadas al seller
        $dateFrom = now()->subHours($hours)->toIso8601String();
        $response = $meli->getOrders([
            'seller' => $config->meli_user_id,
            'order.status' => 'paid',
            'order.date_created.from' => $dateFrom,
            'sort' => 'date_desc',
        ]);

        if (isset($response['error'])) {
            $this->error('Error al consultar órdenes: ' . ($response['message'] ?? $response['error']));
            return 1;
        }

        $orders = $response['results'] ?? [];
        $total = count($orders);
        $this->info("Se encontraron {$total} órdenes pagadas.");

        if ($total === 0) {
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($orders as $order) {
            try {
                $meliOrderId = $order['id'] ?? null;

                if (!$meliOrderId) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Verificar si ya existe un pedido con este meli_order_id
                if (PedidoOnline::where('meli_order_id', $meliOrderId)->exists()) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if ($dryRun) {
                    $buyerName = $order['buyer']['nickname'] ?? 'N/A';
                    $total = $order['total_amount'] ?? 0;
                    $this->newLine();
                    $this->line("  Procesaría orden ML #{$meliOrderId} - Comprador: {$buyerName} - Total: \${$total}");
                    $created++;
                    $bar->advance();
                    continue;
                }

                $this->processOrder($order, $meli, $cvaService, $config);
                $created++;
            } catch (\Exception $e) {
                $errors++;
                Log::error("MeliSyncOrders: Error procesando orden", [
                    'meli_order_id' => $order['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $this->newLine();
                $this->error("  Error en orden #{$order['id']}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Creados: {$created}, Saltados (ya existían): {$skipped}, Errores: {$errors}");

        return 0;
    }

    protected function processOrder(array $order, MeliService $meli, CVAService $cvaService, $config): void
    {
        $meliOrderId = $order['id'];

        // Extraer datos del comprador
        $buyer = $order['buyer'] ?? [];
        $shipping = $order['shipping'] ?? [];

        // Obtener detalle del envío para dirección
        $shippingData = [];
        $meliShipmentId = $shipping['id'] ?? null;
        if ($meliShipmentId) {
            $shipmentDetail = $meli->getShipment($meliShipmentId);
            $receiverAddress = $shipmentDetail['receiver_address'] ?? [];
            $shippingData = [
                'calle' => ($receiverAddress['street_name'] ?? '') . ' ' . ($receiverAddress['street_number'] ?? ''),
                'colonia' => $receiverAddress['neighborhood']['name'] ?? '',
                'ciudad' => $receiverAddress['city']['name'] ?? '',
                'estado' => $receiverAddress['state']['name'] ?? '',
                'cp' => $receiverAddress['zip_code'] ?? '',
                'pais' => $receiverAddress['country']['name'] ?? 'México',
                'referencia' => $receiverAddress['comment'] ?? '',
            ];
        }

        // Mapear items de ML a productos locales
        $orderItems = $order['order_items'] ?? [];
        $itemsForPedido = [];
        $productosForCva = [];
        $subtotal = 0;

        foreach ($orderItems as $orderItem) {
            $listingId = $orderItem['item']['id'] ?? null;
            $quantity = $orderItem['quantity'] ?? 1;
            $unitPrice = $orderItem['unit_price'] ?? 0;

            // Buscar listing local
            $listing = MercadoLibreListing::where('listing_id', $listingId)->first();
            $producto = $listing ? $listing->producto : null;

            $itemsForPedido[] = [
                'producto_id' => $producto?->id,
                'nombre' => $orderItem['item']['title'] ?? 'Producto ML',
                'cantidad' => $quantity,
                'precio_unitario' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
                'meli_listing_id' => $listingId,
                'origen' => $producto?->origen ?? 'ML',
                'codigo' => $producto?->codigo ?? $listingId,
            ];

            $subtotal += $unitPrice * $quantity;

            // Si el producto es de CVA, preparar para el pedido CVA
            if ($producto && $producto->origen === 'CVA') {
                $productosForCva[] = [
                    'id' => $producto->id,
                    'clave' => $producto->codigo,
                    'cantidad' => $quantity,
                ];
            }
        }

        $costoEnvio = $order['shipping_cost'] ?? $shipping['shipping_option']['cost'] ?? 0;
        $totalOrder = $order['total_amount'] ?? ($subtotal + $costoEnvio);

        // Crear PedidoOnline dentro de una transacción
        DB::transaction(function () use (
            $config, $meliOrderId, $meliShipmentId, $buyer, $shippingData,
            $itemsForPedido, $subtotal, $costoEnvio, $totalOrder,
            $productosForCva, $cvaService
        ) {
            $pedido = PedidoOnline::create([
                'empresa_id' => $config->empresa_id,
                'numero_pedido' => PedidoOnline::generarNumeroPedido(),
                'meli_order_id' => $meliOrderId,
                'meli_shipment_id' => $meliShipmentId,
                'email' => $buyer['email'] ?? null,
                'nombre' => trim(($buyer['first_name'] ?? '') . ' ' . ($buyer['last_name'] ?? '')),
                'telefono' => $buyer['phone']['number'] ?? null,
                'direccion_envio' => $shippingData,
                'items' => $itemsForPedido,
                'subtotal' => $subtotal,
                'costo_envio' => $costoEnvio,
                'total' => $totalOrder,
                'metodo_pago' => 'mercadolibre',
                'estado' => PedidoOnline::ESTADO_PAGADO,
                'payment_id' => (string) $meliOrderId,
                'payment_status' => 'approved',
                'pagado_at' => now(),
            ]);

            $pedido->registrarEvento(
                'MELI_ORDEN_RECIBIDA',
                "Orden recibida desde MercadoLibre #{$meliOrderId}",
                ['meli_order_id' => $meliOrderId, 'buyer' => $buyer['nickname'] ?? 'N/A']
            );

            // Si hay productos CVA, crear pedido en CVA con dirección del comprador
            if (!empty($productosForCva) && !empty($shippingData)) {
                $this->createCvaOrder($pedido, $productosForCva, $shippingData, $cvaService);
            }
        });

        $this->newLine();
        $this->line("  ✅ Orden ML #{$meliOrderId} → PedidoOnline creado");
    }

    protected function createCvaOrder(PedidoOnline $pedido, array $productos, array $direccion, CVAService $cvaService): void
    {
        try {
            $orderData = [
                'productos' => array_map(fn($p) => [
                    'id' => $p['id'],
                    'cantidad' => $p['cantidad'],
                ], $productos),
                'tipo_flete' => 'FF', // Flete facturado por CVA
                'flete' => [
                    'calle' => $direccion['calle'] ?? '',
                    'numero' => 'SN',
                    'colonia' => $direccion['colonia'] ?? '',
                    'cp' => $direccion['cp'] ?? '',
                    'estado' => $direccion['estado'] ?? '',
                    'ciudad' => $direccion['ciudad'] ?? '',
                ],
            ];

            $result = $cvaService->createOrder($orderData);

            if ($result['success']) {
                $pedido->update([
                    'cva_pedido_id' => $result['data']['pedido'] ?? null,
                    'notas' => ($pedido->notas ? $pedido->notas . ' ' : '') .
                        '[PEDIDO CVA: ' . ($result['data']['pedido'] ?? 'ORDEN CREADA') . '] [ORIGEN: MERCADOLIBRE]',
                ]);

                $pedido->registrarEvento(
                    'ENVIO_CVA',
                    "Pedido enviado a CVA (dropshipping ML). ID: " . ($result['data']['pedido'] ?? 'N/A'),
                    $result['data'] ?? []
                );

                Log::info("MeliSyncOrders: Pedido CVA creado para ML order", [
                    'pedido' => $pedido->numero_pedido,
                    'cva_pedido_id' => $result['data']['pedido'] ?? null,
                ]);
            } else {
                $pedido->registrarEvento(
                    'ERROR_CVA',
                    "Error al crear pedido CVA desde ML: " . ($result['error'] ?? 'Desconocido'),
                    $result
                );

                Log::error("MeliSyncOrders: Error creando pedido CVA", [
                    'pedido' => $pedido->numero_pedido,
                    'error' => $result['error'] ?? 'unknown',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("MeliSyncOrders: Excepción al crear pedido CVA", [
                'pedido' => $pedido->numero_pedido,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
