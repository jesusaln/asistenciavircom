<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\NewsletterTrack;
use App\Models\Cliente;
use Inertia\Inertia;

class NewsletterStatsController extends Controller
{
    /**
     * Ver detalles de tracking de un post específico
     */
    public function show($id)
    {
        $post = BlogPost::findOrFail($id);

        $tracks = NewsletterTrack::with('cliente')
            ->where('blog_post_id', $id)
            ->orderBy('enviado_at', 'desc')
            ->get()
            ->map(function ($track) {
                return [
                    'cliente_nombre' => $track->cliente->nombre_razon_social ?? 'Desconocido',
                    'cliente_email' => $track->cliente->email ?? 'Sin email',
                    'enviado_at' => $track->enviado_at,
                    'abierto_at' => $track->abierto_at,
                    'clic_at' => $track->clic_at,
                    'interes_at' => $track->interes_at,
                ];
            });

        return Inertia::render('Admin/Blog/Stats', [
            'post' => $post,
            'tracks' => $tracks,
        ]);
    }

    /**
     * Ver lista de personas que se dieron de baja
     */
    public function unsubscribed()
    {
        $bajas = Cliente::where('recibe_newsletter', false)
            ->whereNotNull('newsletter_unsubscribed_at')
            ->orderBy('newsletter_unsubscribed_at', 'desc')
            ->get(['nombre_razon_social', 'email', 'newsletter_unsubscribed_at']);

        return Inertia::render('Admin/Blog/Unsubscribed', [
            'bajas' => $bajas
        ]);
    }
}
