<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TradingController extends Controller
{
    public function simulacion()
    {
        $balances = app()->environment('local')
            ? ((new \App\Services\BinanceOrderService(1))->getAccountInfo()['balances'] ?? [])
            : \Illuminate\Support\Facades\Cache::get('binance_balances_1', []);

        return Inertia::render('Trading/Simulacion', [
            'initial_balance' => 10000,
            'binance_balances' => $balances,
        ]);
    }

    public function binance()
    {
        return Inertia::render('Trading/Binance', [
            'api_key' => config('services.binance.key'),
        ]);
    }
    public function logPerformance(Request $request)
    {
        $data = $request->all();
        $logPath = storage_path('logs/trading_performance.log');
        
        $content = "[" . now()->toDateTimeString() . "] " . 
                   "BALANCE: $" . number_format($data['balance'] ?? 0, 2) . " | " .
                   "WIN RATE: " . ($data['winRate'] ?? '0') . "% | " .
                   "TRADES: " . ($data['tradesCount'] ?? '0') . "\n";
        
        if (!empty($data['lastTrade'])) {
            $last = $data['lastTrade'];
            $content .= "LAST TRADE: " . strtoupper($last['type'] ?? 'N/A') . " | PNL: " . number_format($last['pnlPercent'] ?? 0, 2) . "% | " . ($last['analysis'] ?? 'N/A') . "\n";
        }
        
        $content .= "--------------------------------------------------\n";
        
        file_put_contents($logPath, $content, FILE_APPEND);
        
        return response()->json(['status' => 'ok']);
    }

    public function saveExperience(Request $request)
    {
        $data = $request->validate([
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'timestamp' => 'required|numeric',
            'open' => 'required|numeric',
            'high' => 'required|numeric',
            'low' => 'required|numeric',
            'close' => 'required|numeric',
            'volume' => 'required|numeric',
            'indicators_state' => 'nullable|array',
            'market_regime' => 'nullable|string',
            'ai_confidence' => 'nullable|numeric',
            'signal' => 'nullable|string',
            'trade_pnl' => 'nullable|numeric',
            'is_win' => 'nullable|boolean',
            'atr_percent' => 'nullable|numeric',
            'atr_value' => 'nullable|numeric',
            'macro_timeframe' => 'nullable|string',
            'macro_trend' => 'nullable|string',
            'stop_loss' => 'nullable|numeric',
            'trailing_stop' => 'nullable|numeric',
            'highest_price' => 'nullable|numeric',
        ]);

        \App\Models\TradingExperience::updateOrCreate(
            ['symbol' => $data['symbol'], 'timeframe' => $data['timeframe'], 'timestamp' => $data['timestamp']],
            $data
        );

        return response()->json(['status' => 'saved']);
    }

    public function bulkSaveExperience(Request $request)
    {
        $token = $request->header('X-Trading-Token');
        if ($token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $experiences = $request->input('experiences', []);
        foreach ($experiences as $data) {
            \App\Models\TradingExperience::updateOrCreate(
                ['symbol' => $data['symbol'], 'timeframe' => $data['timeframe'], 'timestamp' => $data['timestamp']],
                $data
            );
        }
        return response()->json(['status' => 'bulk_saved', 'count' => count($experiences)]);
    }

    public function getHistory(Request $request)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if (!auth()->check() && $token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $symbol = $request->input('symbol', 'BTCUSDT');
        $timeframe = $request->input('timeframe', '15m');
        $limit = $request->input('limit', 1000);

        $records = \App\Models\TradingExperience::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('timestamp', 'desc')
            ->limit($limit)
            ->get();

        // Convertir a formato Binance [timestamp, open, high, low, close, volume]
        $formatted = $records->map(function ($r) {
            return [
                $r->timestamp * 1000,
                (string)$r->open,
                (string)$r->high,
                (string)$r->low,
                (string)$r->close,
                (string)$r->volume
            ];
        })->reverse()->values();

        return response()->json($formatted);
    }

    public function sync(Request $request)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if (!auth()->check() && $token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'symbol' => 'required|string',
            'timeframe' => 'required|string',
            'weights' => 'required|array',
            'accuracy' => 'nullable|numeric'
        ]);

        \App\Models\TradingWeight::updateOrCreate(
            [
                'symbol' => $data['symbol'],
                'timeframe' => $data['timeframe']
            ],
            [
                'weights' => $data['weights'],
                'accuracy' => $data['accuracy'] ?? 0,
                'total_trades' => \App\Models\TradingExperience::where('symbol', $data['symbol'])->count(),
                'status' => 'optimized'
            ]
        );

        return response()->json(['status' => 'synced']);
    }

    public function getWeights(Request $request)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if (!auth()->check() && $token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $symbol = $request->input('symbol', 'BTCUSDT');
        $timeframe = $request->input('timeframe', '15m');

        $weights = \App\Models\TradingWeight::where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->first();

        if (!$weights) {
            // Devolver valores por defecto en lugar de 404
            return response()->json([
                'symbol' => $symbol,
                'timeframe' => $timeframe,
                'weights' => ['ema' => 1.2, 'rsi' => 0.8, 'bb' => 1.0, 'volume' => 1.5, 'macd' => 0.5],
                'accuracy' => 0,
                'total_trades' => 0,
                'status' => 'default'
            ]);
        }

        return response()->json($weights);
    }

    public function getApiKeys(Request $request)
    {
        $keys = \App\Models\TradingApiKey::where('user_id', auth()->id())->first();
        return response()->json([
            'has_keys' => $keys ? true : false,
            'is_testnet' => $keys ? $keys->is_testnet : true
        ]);
    }

    public function saveApiKeys(Request $request)
    {
        $data = $request->validate([
            'binance_key' => 'required|string',
            'binance_secret' => 'required|string',
            'is_testnet' => 'nullable|boolean'
        ]);

        \App\Models\TradingApiKey::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'binance_key_encrypted' => $data['binance_key'],
                'binance_secret_encrypted' => $data['binance_secret'],
                'is_testnet' => $data['is_testnet'] ?? true,
                'is_active' => true
            ]
        );

        return response()->json(['status' => 'saved']);
    }

    public function executeOrder(Request $request)
    {
        $data = $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|string|in:BUY,SELL,buy,sell',
            'amount' => 'required|numeric|min:10'
        ]);

        // Si estamos en ambiente local, ejecutamos DIRECTAMENTE
        if (app()->environment('local')) {
            $service = new \App\Services\BinanceOrderService(1);
            $result = $service->executeMarketOrder($data['symbol'], strtoupper($data['side']), $data['amount']);
            
            $status = (isset($result['error']) || isset($result['msg'])) ? 'error' : 'success';
            
            $orderId = \Illuminate\Support\Facades\DB::table('trading_orders_queue')->insertGetId([
                'symbol' => strtoupper($data['symbol']),
                'side' => strtoupper($data['side']),
                'amount' => $data['amount'],
                'status' => $status,
                'response_log' => json_encode($result),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => $status,
                'order_id' => $orderId,
                'binance_result' => $result
            ]);
        }

        // En producción (VPS), metemos a la cola para el relay
        $orderId = \Illuminate\Support\Facades\DB::table('trading_orders_queue')->insertGetId([
            'symbol' => strtoupper($data['symbol']),
            'side' => strtoupper($data['side']),
            'amount' => $data['amount'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'status' => 'queued',
            'order_id' => $orderId
        ]);
    }

    public function pollOrders(Request $request)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if ($token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orders = \Illuminate\Support\Facades\DB::table('trading_orders_queue')
            ->where('status', 'pending')
            ->get();

        return response()->json($orders);
    }

    public function updateOrder(Request $request, $id)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if ($token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'status' => 'required|string|in:success,error',
            'response_log' => 'nullable|string'
        ]);

        \Illuminate\Support\Facades\DB::table('trading_orders_queue')
            ->where('id', $id)
            ->update([
                'status' => $data['status'],
                'response_log' => $data['response_log'],
                'updated_at' => now()
            ]);

        return response()->json(['status' => 'updated']);
    }

    public function getBinanceBalance()
    {
        // Si estamos en ambiente local, podemos consultar a Binance directamente sin bloqueos
        if (app()->environment('local')) {
            // Forzamos el User ID 1 (Jesus Lopez) para mapear sus API Keys locales
            $service = new \App\Services\BinanceOrderService(1);
            $result = $service->getAccountInfo();
            return response()->json($result);
        }

        // En producción (VPS bloqueado), leemos el balance cacheado por el relay local
        $balances = \Illuminate\Support\Facades\Cache::get('binance_balances_1', []);
        return response()->json(['balances' => $balances]);
    }

    public function updateBalance(Request $request)
    {
        $token = $request->header('X-Trading-Token') ?: $request->input('token');
        if ($token !== config('services.trading.sync_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'balances' => 'required|array'
        ]);

        \Illuminate\Support\Facades\Cache::put('binance_balances_1', $data['balances'], 300); // 5 min TTL

        return response()->json(['status' => 'updated']);
    }

    public function getExecutedOrders()
    {
        $orders = \Illuminate\Support\Facades\DB::table('trading_orders_queue')
            ->where('status', 'success')
            ->orderBy('id', 'desc')
            ->get();

        $trades = $orders->map(function ($o) {
            $log = json_decode($o->response_log, true);
            $price = 0;
            if (isset($log['fills'][0]['price'])) {
                $price = (float)$log['fills'][0]['price'];
            }
            
            return [
                'id' => $o->id,
                'entryTime' => strtotime($o->created_at),
                'type' => strtolower($o->side),
                'entryPrice' => $price,
                'pnl' => 0.0
            ];
        });

        return response()->json($trades);
    }
}
