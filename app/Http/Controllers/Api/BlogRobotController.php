<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogRobotController extends Controller
{
    /**
     * Receive a draft from an external robot (n8n, Make, etc).
     */
    public function storeDraft(Request $request)
    {
        // 1. Verificar configuración
        $config = EmpresaConfiguracion::first();

        if (!$config || !$config->blog_robot_enabled) {
            return response()->json([
                'error' => 'La recepción automática de blogs está deshabilitada en el sistema.'
            ], 403);
        }

        // 2. Verificar Token (Bearer)
        $token = $request->bearerToken();
        if (!$token || $token !== $config->blog_robot_token) {
            return response()->json([
                'error' => 'Token de seguridad inválido o faltante.'
            ], 401);
        }

        // 3. Validar Datos
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'resumen' => 'nullable|string',
            'contenido' => 'required|string',
            'categoria' => 'nullable|string',
            'imagen_portada' => 'nullable|url',
        ]);

        // 4. Crear el Borrador
        // Siempre se crean como 'draft' por seguridad, para revisión humana.
        $post = BlogPost::create([
            'titulo' => $validated['titulo'],
            // El slug se genera automáticamente en el modelo (boot) o manualmente si es necesario
            // 'slug' => Str::slug($validated['titulo']), 
            'resumen' => $validated['resumen'] ?? Str::limit(strip_tags($validated['contenido']), 150),
            'contenido' => $validated['contenido'],
            'categoria' => $validated['categoria'] ?? 'Noticias',
            'status' => 'draft',
            'imagen_portada' => $validated['imagen_portada'] ?? null,
            // 'publicado_at' => null // Al ser draft, no tiene fecha
        ]);

        return response()->json([
            'message' => 'Borrador recibido exitosamente. Requiere revisión de un administrador.',
            'id' => $post->id,
            'titulo' => $post->titulo,
            'admin_url' => route('admin.blog.edit', $post->id)
        ], 201);
    }
}
