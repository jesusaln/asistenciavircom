<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\BinanceOrderService;

class TradingRelay extends Command
{
    protected $signature = 'trading:relay';
    protected $description = 'Poles pending orders from VPS and executes them on Binance via local unblocked IP';

    public function handle()
    {
        $this->info("🚀 Local Binance Relay Bridge STARTED.");
        $this->info("Polling pending orders from climasdeldesierto.com...");

        $token = config('services.trading.sync_token', 'cdd_ia_master_2026');
        $lastBalanceSync = 0;

        while (true) {
            try {
                if (time() - $lastBalanceSync >= 10) {
                    $service = new BinanceOrderService(1);
                    $accInfo = $service->getAccountInfo();
                    if (isset($accInfo['balances'])) {
                        Http::withHeaders([
                            'Accept' => 'application/json',
                            'X-Trading-Token' => $token
                        ])->post('https://climasdeldesierto.com/trading/update-balance', [
                            'balances' => $accInfo['balances']
                        ]);
                        $lastBalanceSync = time();
                        $this->info("🔄 Balance sincronizado con el VPS.");
                    }
                }

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'X-Trading-Token' => $token
                ])->get('https://climasdeldesierto.com/trading/poll-orders');

                if ($response->successful()) {
                    $orders = $response->json();
                    
                    if (is_array($orders) && count($orders) > 0) {
                        $this->info("📦 Encontradas " . count($orders) . " órdenes pendientes.");

                        foreach ($orders as $order) {
                            $this->warn("⚠️ Procesando Orden #{$order['id']}: {$order['side']} {$order['amount']} {$order['symbol']}");
                            
                            // Usar el servicio de Binance localmente (que NO está bloqueado)
                            // Forzamos el User ID 1 (Jesus Lopez) para mapear sus API Keys
                            $service = new BinanceOrderService(1);
                            $result = $service->executeMarketOrder($order['symbol'], $order['side'], $order['amount']);

                            $this->info("Resultado Binance: " . json_encode($result));

                            $status = (isset($result['error']) || isset($result['msg'])) ? 'error' : 'success';
                            
                            // Notificar de vuelta al VPS
                            $updateRes = Http::withHeaders([
                                'Accept' => 'application/json',
                                'X-Trading-Token' => $token
                            ])->post("https://climasdeldesierto.com/trading/update-order/{$order['id']}", [
                                'status' => $status,
                                'response_log' => json_encode($result)
                            ]);

                            if ($updateRes->successful()) {
                                $this->info("✅ Orden #{$order['id']} marcada como {$status} en el VPS.");
                            } else {
                                $this->error("❌ Error al actualizar orden #{$order['id']} en el VPS.");
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Relay Exception: " . $e->getMessage());
            }

            sleep(2); // Esperar 2 segundos antes de volver a pedir órdenes
        }
    }
}
