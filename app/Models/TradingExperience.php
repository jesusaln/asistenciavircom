<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class TradingExperience extends Model
{
    use BelongsToEmpresa;

    protected $table = 'trading_experience';
    
    protected $fillable = [
        'symbol', 'timeframe', 'timestamp',
        'open', 'high', 'low', 'close', 'volume',
        'indicators_state', 'market_regime', 'ai_confidence',
        'signal', 'trade_pnl', 'is_win',
        'atr_percent', 'atr_value', 'macro_timeframe',
        'macro_trend', 'stop_loss', 'trailing_stop', 'highest_price'
    ];

    protected $casts = [
        'indicators_state' => 'array',
        'is_win' => 'boolean',
        'atr_percent' => 'float',
        'atr_value' => 'float',
        'stop_loss' => 'float',
        'trailing_stop' => 'float',
        'highest_price' => 'float',
    ];
}
