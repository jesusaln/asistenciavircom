<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\BlogPost;
use App\Models\PlanPoliza;
use App\Models\Categoria;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index()
    {
        $xml = Cache::remember('sitemap_xml', 3600, function () {
            $baseUrl = config('app.url', 'https://climasdeldesierto.com');

            $urls = [];

            // ============================
            // PÁGINAS ESTÁTICAS (Prioridad Alta)
            // ============================
            $urls[] = [
                'loc' => $baseUrl,
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/tienda',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/contacto',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/blog',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/polizas',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/rentas-equipos',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/asesor-climatizacion',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/agendar',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/soporte-tecnico',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/reparacion-minisplit',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/mantenimiento-preventivo',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/instalacion-minisplit',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/recarga-gas',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/privacidad',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ];
            $urls[] = [
                'loc' => $baseUrl . '/terminos',
                'lastmod' => now()->toW3cString(),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ];

            // ============================
            // CATEGORÍAS (SEO de Estructura)
            // ============================
            $categorias = Categoria::all();
            foreach ($categorias as $categoria) {
                $urls[] = [
                    'loc' => $baseUrl . '/tienda?categoria=' . $categoria->id,
                    'lastmod' => now()->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            // ============================
            // PRODUCTOS (Prioridad Alta - SEO de Catálogo)
            // ============================
            $productos = Producto::where('estado', 'activo')
                ->select('id', 'updated_at')
                ->get();

            foreach ($productos as $producto) {
                $urls[] = [
                    'loc' => $baseUrl . '/producto/' . $producto->id,
                    'lastmod' => $producto->updated_at?->toW3cString() ?? now()->toW3cString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            }

            // ============================
            // BLOG POSTS (Prioridad Media-Alta - SEO de Contenido)
            // ============================
            $posts = BlogPost::where('status', 'published')
                ->where('publicado_at', '<=', now())
                ->select('slug', 'updated_at', 'publicado_at')
                ->get();

            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => $baseUrl . '/blog/' . $post->slug,
                    'lastmod' => ($post->updated_at ?? $post->publicado_at)->toW3cString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ];
            }

            // ============================
            // GENERAR XML
            // ============================
            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($urls as $url) {
                $xmlContent .= "  <url>\n";
                $xmlContent .= "    <loc>{$url['loc']}</loc>\n";
                $xmlContent .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
                $xmlContent .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
                $xmlContent .= "    <priority>{$url['priority']}</priority>\n";
                $xmlContent .= "  </url>\n";
            }

            $xmlContent .= '</urlset>';

            return $xmlContent;
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
