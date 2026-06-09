<?php

namespace App\Http\Middleware;

use App\Helpers\Utf8Helper;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CleanJsonResponse
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
        $response = $next($request);

        // Solo procesar respuestas JSON
        if ($response instanceof JsonResponse) {
            $data = $response->getData();
            
            // Limpiar los datos de caracteres UTF-8 inválidos
            $cleanedData = Utf8Helper::clean($data);
            
            // Establecer los datos limpios en la respuesta
            $response->setData($cleanedData);
        }

        return $response;
    }
}
