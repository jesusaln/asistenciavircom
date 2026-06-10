<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para forzar autenticación web en rutas API.
 * 
 * Este middleware verifica si existe una sesión web activa y autentica
 * al usuario para el guard 'sanctum' usando la misma sesión.
 * 
 * Soluciona el problema de 401 Unauthorized cuando el frontend
 * (Inertia/Vue) hace peticiones a la API mientras el usuario
 * ya está logueado en la sesión web.
 */
class ForceWebAuthForApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionId = $request->hasSession() ? $request->session()->getId() : 'no-session';
        
        $sessionData = $request->hasSession() ? $request->session()->all() : [];
        $cookies = $request->cookies->all();
        
        Log::info('ForceWebAuthForApi: checking request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'sanctum_check' => Auth::guard('sanctum')->check(),
            'web_check' => Auth::guard('web')->check(),
            'session_id' => $sessionId,
            'user_id_web' => Auth::guard('web')->id(),
            'cookies' => array_keys($cookies),
            'session_keys' => array_keys($sessionData),
            'stateful' => \Laravel\Sanctum\Sanctum::currentApplicationUrlWithPort()
        ]);

        // Si ya está autenticado via sanctum, continuar
        if (Auth::guard('sanctum')->check()) {
            return $next($request);
        }

        // Si el usuario está autenticado via sesión web, usar ese usuario para Sanctum
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user) {
                Auth::guard('sanctum')->setUser($user);
                Log::info('ForceWebAuthForApi: Mapped web session to Sanctum', ['user_id' => $user->id]);
            }
        } else {
            // Si web_check falla pero hay cookies de sesión, intentar User::find() sin global scopes
            $loginKey = 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d';
            $userId = $request->session()->get($loginKey);
            if ($userId) {
                $user = \App\Models\User::withoutGlobalScopes()->find($userId);
                if ($user) {
                    Auth::guard('web')->setUser($user);
                    Auth::guard('sanctum')->setUser($user);
                    Log::info('ForceWebAuthForApi: Authenticated via session userId (withoutGlobalScopes)', ['user_id' => $user->id]);
                }
            }
            
            if (!Auth::guard('sanctum')->check()) {
                Log::warning('ForceWebAuthForApi: No web session found for ' . $request->fullUrl() . ' | SessionID: ' . $sessionId);
            }
        }

        return $next($request);
    }
}
