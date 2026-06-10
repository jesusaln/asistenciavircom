<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyLoginCode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
        /*
        if (app()->environment('local') || env('LOGIN_CODE_REQUIRED', true) === false) {
            return $next($request);
        }
        */

        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Verificar si el dispositivo es de confianza
        $deviceToken = hash('sha256', $user->id . '|' . $user->email . '|' . config('app.key'));
        if ($request->cookie('trusted_device') === $deviceToken) {
            return $next($request);
        }

        if ($user->login_code && !$user->login_code_verified_at) {
            
            if ($user->login_code_expires_at && $user->login_code_expires_at->isPast()) {
                auth()->logout();
                return redirect()->route('login')->with('error', 'El código de seguridad ha expirado.');
            }

            if (!$request->routeIs('verify.index') && !$request->routeIs('verify.store') && !$request->routeIs('verify.resend') && !$request->routeIs('logout')) {
                return redirect()->route('verify.index');
            }
        }

        return $next($request);
    }
}
