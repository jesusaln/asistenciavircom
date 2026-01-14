<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers de seguridad para proteger la aplicación
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir ataques de clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevenir sniffing de MIME type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Habilitar filtro XSS del navegador
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Forzar HTTPS (Strict Transport Security)
        if (config('app.env') === 'production') {
            if (config('app.env') === 'production') {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        // Política de referrer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Política de permisos (features del navegador)
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
