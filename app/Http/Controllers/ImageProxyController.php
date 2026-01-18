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
                $u = $request->query('u');
                // Asegurarse de quitar espacios o caracteres raros
                $decoded = base64_decode(trim($u));
                if ($decoded && (str_starts_with($decoded, 'http://') || str_starts_with($decoded, 'https://'))) {
                    $url = $decoded;
                }
            } catch (\Exception $e) {
                \Log::error("Error decoding image proxy URL: " . $e->getMessage());
            }
        }

        if (!$url) {
            \Log::warning("Image Proxy: No valid URL found in request", ['u' => $request->query('u')]);
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

                        // Si es la imagen de "No Disponible" de CVA que sabemos que falla, o si queremos forzar una mejor imagen
                        if (str_contains($realImageUrl, 'na1.gif') || str_contains($url, 'me2.grupocva.com')) {
                            // Intentar resolver por clave de producto si es una URL de CVA
                            $resolved = $this->resolveFallbackCvaImage($url);
                            if ($resolved) {
                                return $resolved;
                            }
                        }

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

                    // Si no hubo suerte con regex, intentar resolver por DB igual
                    $resolved = $this->resolveFallbackCvaImage($url);
                    if ($resolved) {
                        return $resolved;
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

            // Si falló por timeout o error de red, intentar el último recurso: resolver por API V2
            if (str_contains($url, 'grupocva.com')) {
                $resolved = $this->resolveFallbackCvaImage($url);
                if ($resolved) {
                    return response(base64_decode($resolved['content']))
                        ->header('Content-Type', $resolved['mime'])
                        ->header('Cache-Control', 'public, max-age=86400');
                }
            }

            return $fallbackResponse();
        }
    }

    /**
     * Intenta encontrar una imagen alternativa para un URL de CVA
     * buscando el producto en nuestra DB y pidiendo imágenes HD a la API
     */
    private function resolveFallbackCvaImage($url)
    {
        try {
            // Buscar producto que use esta imagen
            $producto = \App\Models\Producto::where('imagen', $url)
                ->whereNotNull('cva_clave')
                ->first();

            if (!$producto) {
                // Intentar extraer el ID fProd de la URL (ej: fProd=10477696)
                if (preg_match('/fProd=(\d+)/', $url, $m)) {
                    $producto = \App\Models\Producto::where('imagen', 'like', "%fProd={$m[1]}%")
                        ->whereNotNull('cva_clave')
                        ->first();
                }
            }

            if ($producto && $producto->cva_clave) {
                $service = app(\App\Services\CVAService::class);
                $images = $service->getHighResImages($producto->cva_clave);

                // Si no hay imágenes "oficiales", intentar adivinar por patrón
                if (empty($images)) {
                    $clave = $producto->cva_clave;
                    $images = [
                        "https://www.grupocva.com/articulos_img/{$clave}.jpg",
                        "https://www.grupocva.com/nuevo/catalogo/product_images/{$clave}.jpg",
                        "https://www.grupocva.com/articulos_img/{$clave}.JPG",
                    ];
                }

                foreach ($images as $realUrl) {
                    try {
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Referer' => 'https://www.grupocva.com/',
                        ])->timeout(8)->get($realUrl);

                        if ($response->successful() && str_contains($response->header('Content-Type'), 'image')) {
                            return [
                                'content' => base64_encode($response->body()),
                                'mime' => $response->header('Content-Type') ?? 'image/jpeg'
                            ];
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::debug("Fallback CVA Resolution failed: " . $e->getMessage());
        }

        return null;
    }
}
