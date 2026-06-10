<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugCorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo debuggear requests de API
        if ($request->is('api/*')) {
            Log::info('=== DEBUG CORS API ===');
            Log::info('URL: ' . $request->fullUrl());
            Log::info('Method: ' . $request->method());
            Log::info('Origin header: ' . $request->header('Origin', 'NO ORIGIN'));
            Log::info('Referer header: ' . $request->header('Referer', 'NO REFERER'));
            Log::info('User-Agent: ' . $request->header('User-Agent', 'NO USER AGENT'));
            Log::info('Host: ' . $request->header('Host'));
            Log::info('======================');
        }

        return $next($request);
    }
}
