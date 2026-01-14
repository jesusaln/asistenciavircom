<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBaseArticle;
use App\Models\TicketCategory;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = EmpresaResolver::resolveId();

        $query = KnowledgeBaseArticle::with(['categoria', 'autor'])
            ->where('empresa_id', $empresaId);

        // Solo publicados para usuarios normales, todos para admins
        if (!auth()->user()->hasRole(['admin', 'super-admin'])) {
            $query->publicados();
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->buscar);
        }

        $articulos = $query->orderByDesc('destacado')
            ->orderByDesc('vistas')
            ->paginate(12)
            ->appends($request->all());

        return Inertia::render('Soporte/KnowledgeBase/Index', [
            'articulos' => $articulos,
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->get(),
            'filtros' => $request->only(['categoria_id', 'buscar']),
        ]);
    }

    public function create()
    {
        $empresaId = EmpresaResolver::resolveId();

        return Inertia::render('Soporte/KnowledgeBase/Create', [
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'resumen' => 'nullable|string|max:500',
            'categoria_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'destacado' => 'boolean',
            'publicado' => 'boolean',
        ]);

        $validated['empresa_id'] = EmpresaResolver::resolveId();
        $validated['user_id'] = auth()->id();

        $articulo = KnowledgeBaseArticle::create($validated);

        return redirect()->route('soporte.kb.show', $articulo)
            ->with('success', 'Artículo creado');
    }

    public function show(KnowledgeBaseArticle $articulo)
    {
        $articulo->incrementarVistas();
        $articulo->load(['categoria', 'autor']);

        // Artículos relacionados
        $relacionados = KnowledgeBaseArticle::where('empresa_id', $articulo->empresa_id)
            ->where('id', '!=', $articulo->id)
            ->when($articulo->categoria_id, fn($q) => $q->where('categoria_id', $articulo->categoria_id))
            ->publicados()
            ->limit(4)
            ->get();

        return Inertia::render('Soporte/KnowledgeBase/Show', [
            'articulo' => $articulo,
            'relacionados' => $relacionados,
        ]);
    }

    public function edit(KnowledgeBaseArticle $articulo)
    {
        $empresaId = EmpresaResolver::resolveId();

        return Inertia::render('Soporte/KnowledgeBase/Edit', [
            'articulo' => $articulo,
            'categorias' => TicketCategory::where('empresa_id', $empresaId)->activas()->get(),
        ]);
    }

    public function update(Request $request, KnowledgeBaseArticle $articulo)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'resumen' => 'nullable|string|max:500',
            'categoria_id' => 'nullable|exists:ticket_categories,id',
            'tags' => 'nullable|array',
            'destacado' => 'boolean',
            'publicado' => 'boolean',
        ]);

        $articulo->update($validated);

        return redirect()->route('soporte.kb.show', $articulo)
            ->with('success', 'Artículo actualizado');
    }

    public function votar(Request $request, KnowledgeBaseArticle $articulo)
    {
        $util = $request->boolean('util');
        $articulo->marcarUtil($util);

        return response()->json(['success' => true]);
    }

    public function destroy(KnowledgeBaseArticle $articulo)
    {
        $articulo->delete();
        return redirect()->route('soporte.kb.index')->with('success', 'Artículo eliminado');
    }
}
