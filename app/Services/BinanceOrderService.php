<?php

namespace App\Services;

use App\Models\TradingApiKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceOrderService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct($userId = null)
    {
        try {
            $userId = $userId ?: auth()->id();
            $keys = TradingApiKey::where('user_id', $userId)->where('is_active', true)->first();

            if ($keys) {
                $this->apiKey = $keys->binance_key_encrypted;
                $this->apiSecret = $keys->binance_secret_encrypted;
                
                if ($keys->is_testnet) {
                    $this->baseUrl = 'https://testnet.binance.vision';
                } else {
                    $this->baseUrl = 'https://api.binance.com';
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Error loading or decrypting Binance API Keys: " . $e->getMessage());
            $this->apiKey = null;
            $this->apiSecret = null;
        }
    }

    public function isConfigured()
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    public function getAccountInfo()
    {
        if (!$this->isConfigured()) {
            return ['error' => 'API Keys no configuradas o inactivas.'];
        }

        return $this->sendRequest('GET', '/api/v3/account');
    }

    public function executeMarketOrder($symbol, $side, $amountUsdt)
    {
        if (!$this->isConfigured()) {
            return ['error' => 'API Keys no configuradas.'];
        }

        $params = [
            'symbol' => strtoupper($symbol),
            'side' => strtoupper($side),
            'type' => 'MARKET',
        ];

        if (strtoupper($side) === 'BUY') {
            // quoteOrderQty define cuánto USDT gastar en la compra
            $params['quoteOrderQty'] = number_format($amountUsdt, 2, '.', '');
        } else {
            // Vender balance del símbolo
            $baseAsset = str_replace('USDT', '', strtoupper($symbol));
            $account = $this->getAccountInfo();
            
            if (isset($account['balances'])) {
                foreach ($account['balances'] as $balance) {
                    if ($balance['asset'] === $baseAsset) {
                        $precision = $this->getSymbolStepSize($symbol);
                        // Floor manual para evitar redondear hacia arriba y fallar por falta de fondos
                        $qty = (float)$balance['free'];
                        $factor = pow(10, $precision);
                        $qty = floor($qty * $factor) / $factor;
                        
                        $params['quantity'] = number_format($qty, $precision, '.', '');
                        break;
                    }
                }
            }
            
            if (!isset($params['quantity']) || (float)$params['quantity'] <= 0) {
                return ['error' => 'No hay balance suficiente de ' . $baseAsset . ' para vender.'];
            }
        }

        return $this->sendRequest('POST', '/api/v3/order', $params);
    }

    /**
     * Obtenemos la precisión permitida para un símbolo (LOT_SIZE)
     */
    protected function getSymbolStepSize($symbol)
    {
        return cache()->remember("binance_step_size_{$symbol}", 3600, function () use ($symbol) {
            try {
                $response = Http::get($this->baseUrl . '/api/v3/exchangeInfo', ['symbol' => strtoupper($symbol)]);
                if ($response->successful()) {
                    $info = $response->json();
                    $filters = $info['symbols'][0]['filters'] ?? [];
                    foreach ($filters as $filter) {
                        if ($filter['filterType'] === 'LOT_SIZE') {
                            $stepSize = (float)$filter['stepSize'];
                            // Calcular cuántos decimales representa el stepSize (ej: 0.001 -> 3)
                            return strlen(substr(strrchr(rtrim(sprintf('%.8f', $stepSize), '0'), '.'), 1));
                        }
                    }
                }
            } catch (\Exception $e) {}
            return 2; // Default seguro
        });
    }

    protected function sendRequest($method, $endpoint, $params = [])
    {
        $params['timestamp'] = round(microtime(true) * 1000);
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->apiSecret);
        
        $url = $this->baseUrl . $endpoint . '?' . $queryString . '&signature=' . $signature;

        try {
            // Reintento directo (3 veces) con timeout mayor para mayor estabilidad
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->apiKey
            ])->timeout(10)->retry(3, 100)->send($method, $url);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Error en petición a Binance: " . $e->getMessage());
            return [
                'error' => 'Error de conexión con Binance. ' . 
                          (app()->environment('production') ? 'El servidor (VPS) podría estar bloqueado por Binance. Usa el relay local.' : $e->getMessage())
            ];
        }
    }
}
