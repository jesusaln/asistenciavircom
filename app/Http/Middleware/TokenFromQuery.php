<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TokenFromQuery
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
        // Si no hay Bearer token pero hay un parámetro 'token' en la URL
        if (!$request->bearerToken() && $request->has('token')) {
            $token = $request->query('token');
            // Formatear como Bearer token para que Sanctum lo reconozca
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
