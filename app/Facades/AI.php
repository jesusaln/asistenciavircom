<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array chat(array $messages, array $tools = [], ?string $provider = null)
 * @method static bool isAvailable(?string $provider = null)
 * @method static object provider(?string $provider = null)
 *
 * @see \App\Services\AI\AIManager
 */
class AI extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\AI\AIManager::class;
    }
}
