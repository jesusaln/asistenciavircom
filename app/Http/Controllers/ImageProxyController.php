<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageProxyController extends Controller
{
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        // Soporte para URL codificada en base64 (parametro 'u') para evadir bloqueadores de anuncios
        if (!$url && $request->has('u')) {
            try {
                $decoded = base64_decode($request->query('u'));
                if (filter_var($decoded, FILTER_VALIDATE_URL)) {
                    $url = $decoded;
                }
            } catch (\Exception $e) {
            }
        }

        if (!$url) {
            return abort(404);
        }

        // Validar que sea una URL de CVA (seguridad básica)
        if (!str_contains($url, 'grupocva.com')) {
            // return abort(403); 
            // Por ahora permitimos todo para pruebas, pero idealmente restringir
        }

        $placeholderPath = public_path('images/placeholder-product.svg');
        $fallbackResponse = function () use ($placeholderPath) {
            if (file_exists($placeholderPath)) {
                return response(file_get_contents($placeholderPath))
                    ->header('Content-Type', 'image/svg+xml')
                    ->header('Cache-Control', 'public, max-age=86400');
            }
            return response('', 404);
        };

        try {
            $cachedData = \Illuminate\Support\Facades\Cache::remember('img_proxy_' . md5($url), now()->addHours(24), function () use ($url) {
                // Usar HTTP Client de Laravel con User-Agent para evitar bloqueos
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.grupocva.com/',
                ])->timeout(15)->get($url);

                if ($response->failed()) {
                    return null;
                }

                $content = $response->body();
                $mime = $response->header('Content-Type') ?? 'image/jpeg';

                // Si CVA nos regresa HTML (ej. error o fragmento con tag img), intentar extraer la imagen real
                if (str_contains(strtolower($mime), 'html')) {
                    if (preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $content, $matches)) {
                        $realImageUrl = $matches[1];

                        if (str_contains($realImageUrl, 'na1.gif')) {
                            return null;
                        }

                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Referer' => 'https://www.grupocva.com/',
                        ])->timeout(10)->get($realImageUrl);

                        if ($response->successful()) {
                            return [
                                'content' => base64_encode($response->body()),
                                'mime' => $response->header('Content-Type') ?? 'image/jpeg'
                            ];
                        }
                    }
                    return null;
                }

                return [
                    'content' => base64_encode($content),
                    'mime' => $mime
                ];
            });

            if (!$cachedData) {
                return $fallbackResponse();
            }

            return response(base64_decode($cachedData['content']))
                ->header('Content-Type', $cachedData['mime'])
                ->header('Cache-Control', 'public, max-age=86400');

        } catch (\Exception $e) {
            \Log::error("Image Proxy Exception: " . $e->getMessage() . " for URL: " . $url);
            return $fallbackResponse();
        }
    }
}
