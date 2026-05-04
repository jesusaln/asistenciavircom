<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $config = EmpresaConfiguracion::getConfig();

        $query = BlogPost::published()->orderBy('publicado_at', 'desc');

        // Filtrar por categoría si viene en la request
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Buscar por título/resumen si viene búsqueda
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function($q) use ($search) {
                $q->where('titulo', 'ilike', "%{$search}%")
                  ->orWhere('resumen', 'ilike', "%{$search}%")
                  ->orWhere('contenido', 'ilike', "%{$search}%");
            });
        }

        // Obtener categorías disponibles
        $categorias = BlogPost::published()
            ->whereNotNull('categoria')
            ->groupBy('categoria')
            ->orderByRaw('COUNT(*) DESC')
            ->pluck('categoria');

        // Posts populares (por visitas)
        $postsPopulares = BlogPost::published()
            ->orderBy('visitas', 'desc')
            ->take(5)
            ->get(['id', 'titulo', 'slug', 'imagen_portada', 'visitas']);

        return Inertia::render('Blog/Index', [
            'empresa' => $this->getEmpresaData($config),
            'posts' => $query->paginate(12),
            'categorias' => $categorias,
            'postsPopulares' => $postsPopulares,
            'filtros' => [
                'categoria' => $request->categoria,
                'q' => $request->q,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $post = BlogPost::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Incrementar visitas
        $post->increment('visitas');

        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('Blog/Show', [
            'empresa' => $this->getEmpresaData($config),
            'post' => $post,
            'relacionados' => BlogPost::published()
                ->where('id', '!=', $post->id)
                ->where('categoria', $post->categoria)
                ->take(3)
                ->get()
        ]);
    }

    /**
     * Helper to get company data for Inertia
     */
    protected function getEmpresaData($config)
    {
        return [
            'nombre' => $config->nombre_empresa,
            'logo_url' => $config->logo_url,
            'color_principal' => $config->color_principal ?? '#FF6B35',
            'color_secundario' => $config->color_secundario ?? '#64748B',
            'telefono' => $config->telefono,
            'email' => $config->email,
            'whatsapp' => $config->whatsapp ?? $config->telefono,
            // Redes Sociales
            'facebook_url' => $config->facebook_url,
            'instagram_url' => $config->instagram_url,
            'twitter_url' => $config->twitter_url,
            'tiktok_url' => $config->tiktok_url,
        ];
    }
}
