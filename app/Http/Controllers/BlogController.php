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

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                    ->orWhere('resumen', 'like', "%{$search}%")
                    ->orWhere('contenido', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category) {
            $query->where('categoria', $request->category);
        }

        return Inertia::render('Blog/Index', [
            'empresa' => $this->getEmpresaData($config),
            'posts' => $query->paginate(12)->withQueryString(),
            'categories' => BlogPost::published()->distinct()->pluck('categoria')->filter()->values(),
            'filters' => $request->only(['search', 'category']),
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
            'color_principal' => $config->color_principal ?? '#3B82F6',
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
