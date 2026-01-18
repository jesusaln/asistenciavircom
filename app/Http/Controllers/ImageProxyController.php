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

        return \Illuminate\Support\Facades\Cache::remember('img_proxy_' . md5($url), now()->addHours(24), function () use ($url) {
            try {
                // Usar HTTP Client de Laravel con User-Agent para evitar bloqueos
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.grupocva.com/',
                ])->timeout(15)->get($url);

                if ($response->failed()) {
                    \Log::warning("Image Proxy Failed for URL: {$url}", ['status' => $response->status()]);
                    return abort(404);
                }

                $content = $response->body();
                $mime = $response->header('Content-Type') ?? 'image/jpeg';

                // Si CVA nos regresa HTML (ej. error o fragmento con tag img), intentar extraer la imagen real
                if (str_contains(strtolower($mime), 'html')) {
                    \Log::info("Image Proxy received HTML for {$url}, attempting to extract img src");

                    // Buscar <img src='...'> o <img src="..."> en el contenido HTML
                    if (preg_match("/<img[^>]+src=['\"]([^'\"]+)['\"]/i", $content, $matches)) {
                        $realImageUrl = $matches[1];
                        \Log::info("Extracted real image URL: {$realImageUrl}");

                        // Hacer fetch de la imagen real
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            'Referer' => 'https://www.grupocva.com/',
                        ])->timeout(15)->get($realImageUrl);

                        if ($response->successful()) {
                            $content = $response->body();
                            $mime = $response->header('Content-Type') ?? 'image/jpeg';
                        } else {
                            \Log::error("Failed to fetch real image from extracted URL: {$realImageUrl}");
                            return abort(404);
                        }
                    } else {
                        \Log::error("Image Proxy returned HTML but no img tag found for URL: {$url}");
                        return abort(404);
                    }
                }

                return response($content)
                    ->header('Content-Type', $mime)
                    ->header('Cache-Control', 'public, max-age=86400');
            } catch (\Exception $e) {
                \Log::error("Image Proxy Exception: " . $e->getMessage());
                return abort(404);
            }
        });
    }
}
