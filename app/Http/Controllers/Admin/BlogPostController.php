<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        $posts = BlogPost::orderBy('created_at', 'desc')->get()->map(function ($post) {
            // Stats de Newsletter
            $post->newsletter_stats = [
                'enviados' => \App\Models\NewsletterTrack::where('blog_post_id', $post->id)->count(),
                'abiertos' => \App\Models\NewsletterTrack::where('blog_post_id', $post->id)->whereNotNull('abierto_at')->count(),
                'clics' => \App\Models\NewsletterTrack::where('blog_post_id', $post->id)->whereNotNull('clic_at')->count(),
                'interes' => \App\Models\NewsletterTrack::where('blog_post_id', $post->id)->whereNotNull('interes_at')->count(),
            ];
            return $post;
        });

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts,
            'totalUnsubscribed' => \App\Models\Cliente::where('recibe_newsletter', false)->whereNotNull('newsletter_unsubscribed_at')->count(),
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
            'imagen_archivo' => 'nullable|image|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('imagen_archivo')) {
            $path = $request->file('imagen_archivo')->store('blog-covers', 'public');
            $validated['imagen_portada'] = $path; // Override string with path
        }

        if ($validated['status'] === 'published' && !isset($validated['publicado_at'])) {
            $validated['publicado_at'] = now();
        }

        // Remove imagen_archivo from array cause it's not in db column
        unset($validated['imagen_archivo']);

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
            'imagen_portada' => 'nullable|string', // Keep this if user wants to revert to URL
            'imagen_archivo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('imagen_archivo')) {
            $path = $request->file('imagen_archivo')->store('blog-covers', 'public');
            $validated['imagen_portada'] = $path;
        }

        if ($validated['status'] === 'published' && !$blog->publicado_at) {
            $validated['publicado_at'] = now();
        }

        unset($validated['imagen_archivo']);

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

    /**
     * Send newsletter manually for this post
     */
    public function sendNewsletter(BlogPost $blog)
    {
        if ($blog->status !== 'published') {
            return redirect()->back()->with('error', 'El post debe estar publicado para enviarlo como newsletter.');
        }

        try {
            Artisan::call('newsletter:send', ['post_id' => $blog->id]);

            $blog->update(['newsletter_enviado_at' => now()]);

            return redirect()->back()->with('success', 'El proceso de envío de newsletter ha iniciado para ' . $blog->titulo);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al iniciar el envío: ' . $e->getMessage());
        }
    }

    /**
     * Send a test newsletter to the admin
     */
    public function sendTestNewsletter(BlogPost $blog)
    {
        $userEmail = auth()->user()->email;
        $testEmails = [$userEmail];

        // Creamos un cliente "ficticio" para la prueba
        $fakeCliente = new \App\Models\Cliente();
        $fakeCliente->nombre_razon_social = 'Usuario de Prueba (Admin: ' . auth()->user()->name . ')';

        try {
            foreach ($testEmails as $email) {
                $fakeCliente->email = $email;
                \Illuminate\Support\Facades\Mail::mailer('newsletter')
                    ->to($email)
                    ->send(new \App\Mail\WeeklyNewsletter($blog, $fakeCliente));
            }

            return redirect()->back()->with('success', 'Correo de prueba enviado a ' . $userEmail);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al enviar prueba: ' . $e->getMessage());
        }
    }
}
