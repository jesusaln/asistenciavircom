<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class TradingWeight extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'symbol',
        'timeframe',
        'weights',
        'accuracy',
        'total_trades'
    ];

    protected $casts = [
        'weights' => 'array',
        'accuracy' => 'float',
        'total_trades' => 'integer'
    ];
}
