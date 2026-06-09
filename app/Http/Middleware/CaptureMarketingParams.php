<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CaptureMarketingParams
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $params = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'gclid',
            'fbclid',
        ];

        $captured = [];

        foreach ($params as $param) {
            if ($request->has($param)) {
                $value = $request->input($param);
                Session::put("mkt_$param", $value);
                $captured[$param] = $value;
            }
        }

        // Si capturamos algo nuevo, también podemos guardar el referer original si no existe
        if (!empty($captured) && !Session::has('mkt_referer')) {
            Session::put('mkt_referer', $request->headers->get('referer'));
        }

        return $next($request);
    }
}
