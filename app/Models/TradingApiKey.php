<?php

namespace App\Models;
use App\Models\Concerns\BelongsToEmpresa;

use Illuminate\Database\Eloquent\Model;

class TradingApiKey extends Model
{
    use BelongsToEmpresa;

    protected $fillable = [
        'user_id',
        'binance_key_encrypted',
        'binance_secret_encrypted',
        'is_testnet',
        'is_active'
    ];

    protected $casts = [
        'binance_key_encrypted' => 'encrypted',
        'binance_secret_encrypted' => 'encrypted',
        'is_testnet' => 'boolean',
        'is_active' => 'boolean'
    ];
}
