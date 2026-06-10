<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\SocialPost;
use App\Services\MetaSocialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SocialPostController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $posts = SocialPost::where('social_posts.empresa_id', $empresaId)
            ->with('producto')
            ->latest()
            ->paginate(20);

        return Inertia::render('Marketing/SocialPosts/Index', [
            'posts' => $posts,
        ]);
    }

    public function publish(Request $request, MetaSocialService $meta)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'plataforma' => 'required|in:facebook,instagram',
            'mensaje' => 'nullable|string|max:5000',
        ]);

        $empresaId = Auth::user()->empresa_id;
        $producto = Producto::where('empresa_id', $empresaId)
            ->findOrFail($request->producto_id);

        $mensaje = $request->mensaje ?: '🔥 ¡Nuevo producto disponible!';

        $result = $request->plataforma === 'facebook'
            ? $meta->postToFacebook($producto, $mensaje)
            : $meta->postToInstagram($producto, $mensaje);

        $post = SocialPost::create([
            'empresa_id' => $empresaId,
            'producto_id' => $producto->id,
            'user_id' => Auth::id(),
            'plataforma' => $request->plataforma,
            'post_id' => $result['success'] ? $result['post_id'] : null,
            'estado' => $result['success'] ? 'publicado' : 'error',
            'mensaje' => $mensaje,
            'imagen_url' => $producto->imagen ? url('storage/' . $producto->imagen) : null,
            'error_message' => $result['error'] ?? null,
            'published_at' => $result['success'] ? now() : null,
        ]);

        if ($result['success']) {
            return response()->json(['success' => true, 'post' => $post]);
        }

        return response()->json(['success' => false, 'error' => $result['error']], 422);
    }

    public function deletePost(int $id, MetaSocialService $meta)
    {
        $empresaId = Auth::user()->empresa_id;
        $post = SocialPost::where('empresa_id', $empresaId)->findOrFail($id);

        if ($post->post_id) {
            $meta->deletePost($post->post_id, $post->plataforma);
        }

        $post->delete();

        return response()->json(['success' => true]);
    }

    public function productos(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $cols = ['id', 'nombre', 'imagen', 'tipo_producto', 'precio_venta', 'incluye_iva'];

        $productos = Producto::where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->where('catalogo_web', true)
            ->where('tipo_producto', '!=', 'kit')
            ->orderBy('nombre')
            ->get($cols);

        $kits = Producto::where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->where('catalogo_web', true)
            ->where('tipo_producto', 'kit')
            ->orderBy('nombre')
            ->get($cols);

        return response()->json([
            'productos' => $productos,
            'kits' => $kits,
        ]);
    }

    public function status()
    {
        $meta = app(MetaSocialService::class);

        return response()->json([
            'facebook_ready' => $meta->ready(),
            'instagram_ready' => $meta->readyForInstagram(),
        ]);
    }
}
