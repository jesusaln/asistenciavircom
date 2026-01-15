<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Blog/Index', [
            'posts' => BlogPost::orderBy('created_at', 'desc')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Blog/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'resumen' => 'nullable|string',
            'contenido' => 'required|string',
            'categoria' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'imagen_portada' => 'nullable|string',
        ]);

        if ($validated['status'] === 'published' && !isset($validated['publicado_at'])) {
            $validated['publicado_at'] = now();
        }

        $post = BlogPost::create($validated);

        if ($post->status === 'published') {
            $this->notifySocialMedia($post);
        }

        return redirect()->route('admin.blog.index')
            ->with('success', 'Post creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlogPost $blog)
    {
        return Inertia::render('Admin/Blog/Edit', [
            'post' => $blog
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BlogPost $blog)
    {
        $wasPublished = $blog->status === 'published';

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'resumen' => 'nullable|string',
            'contenido' => 'required|string',
            'categoria' => 'nullable|string',
            'status' => 'required|in:draft,published',
            'imagen_portada' => 'nullable|string',
        ]);

        if ($validated['status'] === 'published' && !$blog->publicado_at) {
            $validated['publicado_at'] = now();
        }

        $blog->update($validated);

        // If newly published, trigger social media notification
        if (!$wasPublished && $blog->status === 'published') {
            $this->notifySocialMedia($blog);
        }

        return redirect()->route('admin.blog.index')
            ->with('success', 'Post actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogPost $blog)
    {
        $blog->delete();
        return redirect()->route('admin.blog.index')
            ->with('success', 'Post eliminado correctamente.');
    }

    /**
     * Notify social media via n8n webhook
     */
    protected function notifySocialMedia(BlogPost $post)
    {
        $config = EmpresaConfiguracion::getConfig();
        $webhookUrl = $config->n8n_webhook_blog;

        if (!$webhookUrl) {
            Log::info('No n8n webhook configured for blog social sharing.');
            return;
        }

        try {
            Http::post($webhookUrl, [
                'event' => 'blog_published',
                'id' => $post->id,
                'titulo' => $post->titulo,
                'resumen' => $post->resumen,
                'url' => route('public.blog.show', $post->slug),
                'imagen' => $post->imagen_portada,
                'categoria' => $post->categoria
            ]);
        } catch (\Exception $e) {
            Log::error('Error notifying n8n for blog post: ' . $e->getMessage());
        }
    }
}
