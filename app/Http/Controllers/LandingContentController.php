<?php

namespace App\Http\Controllers;

use App\Models\LandingFaq;
use App\Models\LandingTestimonio;
use App\Models\LandingLogoCliente;
use App\Models\LandingMarcaAutorizada;
use App\Models\LandingProceso;
use App\Models\LandingOferta;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Support\SafeStorage;
use Illuminate\Support\Facades\Log;

class LandingContentController extends Controller
{
    /**
     * Vista principal de administración de contenido de landing
     */
    /**
     * Vista principal de administración de contenido de landing
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $empresaId = $user->empresa_id;
        $maxRows = 200;

        // If super-admin without empresa_id, maybe fallback to first empresa or null
        if (is_null($empresaId) && ($user->hasRole('admin') || $user->hasRole('super-admin'))) {
            // Try to get first empresa or handle gracefully
            $firstEmpresa = \App\Models\Empresa::first();
            $empresaId = $firstEmpresa ? $firstEmpresa->id : null;
        }

        if (!$empresaId) {
            // Return empty state if no empresa context
            return Inertia::render('Admin/LandingContent/Index', [
                'faqs' => [],
                'testimonios' => [],
                'logos' => [],
                'marcas' => [],
                'procesos' => [],
                'ofertas' => [],
                'config' => null,
                'truncated' => [],
            ]);
        }

        $config = \App\Models\EmpresaConfiguracion::getConfig($empresaId);

        $faqsQuery = LandingFaq::where('empresa_id', $empresaId)->ordenado();
        $testimoniosQuery = LandingTestimonio::where('empresa_id', $empresaId)->ordenado();
        $logosQuery = LandingLogoCliente::where('empresa_id', $empresaId)->ordenado();
        $marcasQuery = LandingMarcaAutorizada::where('empresa_id', $empresaId)->ordenado();
        $procesosQuery = LandingProceso::where('empresa_id', $empresaId)->ordenado();
        $ofertasQuery = LandingOferta::where('empresa_id', $empresaId)->ordenado();

        $faqsCount = (clone $faqsQuery)->count();
        $testimoniosCount = (clone $testimoniosQuery)->count();
        $logosCount = (clone $logosQuery)->count();
        $marcasCount = (clone $marcasQuery)->count();
        $procesosCount = (clone $procesosQuery)->count();
        $ofertasCount = (clone $ofertasQuery)->count();

        return Inertia::render('Admin/LandingContent/Index', [
            'faqs' => $faqsQuery->limit($maxRows)->get(),
            'testimonios' => $testimoniosQuery->limit($maxRows)->get(),
            'logos' => $logosQuery->limit($maxRows)->get(),
            'marcas' => $marcasQuery->limit($maxRows)->get(),
            'procesos' => $procesosQuery->limit($maxRows)->get(),
            'ofertas' => $ofertasQuery->limit($maxRows)->get(),
            'config' => $config,
            'truncated' => [
                'faqs' => $faqsCount > $maxRows,
                'testimonios' => $testimoniosCount > $maxRows,
                'logos' => $logosCount > $maxRows,
                'marcas' => $marcasCount > $maxRows,
                'procesos' => $procesosCount > $maxRows,
                'ofertas' => $ofertasCount > $maxRows,
            ],
        ]);
    }

    /**
     * Actualizar configuración del Hero
     */
    public function updateHero(Request $request)
    {
        $config = \App\Models\EmpresaConfiguracion::getConfig(auth()->user()->empresa_id);

        $validated = $request->validate([
            'hero_titulo' => 'nullable|string|max:500',
            'hero_subtitulo' => 'nullable|string|max:500',
            'hero_descripcion' => 'nullable|string',
            'hero_cta_primario' => 'nullable|string|max:255',
            'hero_cta_secundario' => 'nullable|string|max:255',
            'hero_badge_texto' => 'nullable|string|max:255',
        ]);

        $config->update($validated);
        Log::info('Landing Hero actualizado', [
            'empresa_id' => auth()->user()->empresa_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Configuración de Hero actualizada');
    }

    // ==================== FAQs ====================

    public function storeFaq(Request $request)
    {
        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        LandingFaq::create($validated);
        Log::info('Landing FAQ creado', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Pregunta frecuente agregada');
    }

    public function updateFaq(Request $request, LandingFaq $faq)
    {
        if ($faq->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $faq->update($validated);
        Log::info('Landing FAQ actualizado', [
            'faq_id' => $faq->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Pregunta frecuente actualizada');
    }

    public function destroyFaq(LandingFaq $faq)
    {
        if ($faq->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando FAQ', [
            'id' => $faq->id,
            'pregunta' => $faq->pregunta,
            'usuario_id' => auth()->id(),
            'empresa_id' => auth()->user()->empresa_id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        $faq->delete();
        return back()->with('success', 'Pregunta frecuente eliminada');
    }

    // ==================== TESTIMONIOS ====================

    public function storeTestimonio(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'empresa_cliente' => 'nullable|string|max:255',
            'comentario' => 'required|string',
            'calificacion' => 'required|integer|min:1|max:5',
            'foto' => 'nullable|image|max:2048',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('landing/testimonios', 'public');
        }

        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        LandingTestimonio::create($validated);
        Log::info('Landing Testimonio creado', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Testimonio agregado');
    }

    public function updateTestimonio(Request $request, LandingTestimonio $testimonio)
    {
        if ($testimonio->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'empresa_cliente' => 'nullable|string|max:255',
            'comentario' => 'required|string',
            'calificacion' => 'required|integer|min:1|max:5',
            'foto' => 'nullable|image|max:2048',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        if ($request->hasFile('foto')) {
            // Eliminar foto anterior
            if ($testimonio->foto) {
                SafeStorage::deletePublic($testimonio->foto);
            }
            $validated['foto'] = $request->file('foto')->store('landing/testimonios', 'public');
        }

        $testimonio->update($validated);
        Log::info('Landing Testimonio actualizado', [
            'testimonio_id' => $testimonio->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Testimonio actualizado');
    }

    public function destroyTestimonio(LandingTestimonio $testimonio)
    {
        if ($testimonio->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando Testimonio', [
            'id' => $testimonio->id,
            'nombre' => $testimonio->nombre,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        if ($testimonio->foto) {
            SafeStorage::deletePublic($testimonio->foto);
        }
        $testimonio->delete();
        return back()->with('success', 'Testimonio eliminado');
    }

    // ==================== LOGOS CLIENTES ====================

    public function storeLogo(Request $request)
    {
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'logo' => 'required|image|max:2048',
            'url' => 'nullable|url|max:500',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $validated['logo'] = $request->file('logo')->store('landing/logos', 'public');
        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        LandingLogoCliente::create($validated);
        Log::info('Landing Logo creado', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Logo de cliente agregado');
    }

    public function updateLogo(Request $request, LandingLogoCliente $logo)
    {
        if ($logo->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'url' => 'nullable|url|max:500',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior
            if ($logo->logo) {
                SafeStorage::deletePublic($logo->logo);
            }
            $validated['logo'] = $request->file('logo')->store('landing/logos', 'public');
        }

        $logo->update($validated);
        Log::info('Landing Logo actualizado', [
            'logo_id' => $logo->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Logo de cliente actualizado');
    }

    public function destroyLogo(LandingLogoCliente $logo)
    {
        if ($logo->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando Logo Cliente', [
            'id' => $logo->id,
            'nombre_empresa' => $logo->nombre_empresa,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        if ($logo->logo) {
            SafeStorage::deletePublic($logo->logo);
        }
        $logo->delete();
        return back()->with('success', 'Logo de cliente eliminado');
    }

    // ==================== MARCAS AUTORIZADAS (CSAM) ====================

    public function storeMarca(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'logo' => 'required|image|max:2048',
            'tipo' => 'required|string|in:master,oficial,autorizada',
            'texto_auxiliar' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:500',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $validated['logo'] = $request->file('logo')->store('landing/marcas', 'public');
        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        LandingMarcaAutorizada::create($validated);
        Log::info('Landing Marca creada', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Marca autorizada agregada');
    }

    public function updateMarca(Request $request, LandingMarcaAutorizada $marca)
    {
        if ($marca->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'tipo' => 'required|string|in:master,oficial,autorizada',
            'texto_auxiliar' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:500',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($marca->logo) {
                SafeStorage::deletePublic($marca->logo);
            }
            $validated['logo'] = $request->file('logo')->store('landing/marcas', 'public');
        }

        $marca->update($validated);
        Log::info('Landing Marca actualizada', [
            'marca_id' => $marca->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Marca autorizada actualizada');
    }

    public function destroyMarca(LandingMarcaAutorizada $marca)
    {
        if ($marca->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando Marca Autorizada', [
            'id' => $marca->id,
            'nombre' => $marca->nombre,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        if ($marca->logo) {
            SafeStorage::deletePublic($marca->logo);
        }
        $marca->delete();
        return back()->with('success', 'Marca autorizada eliminada');
    }

    // ==================== PROCESOS ====================

    public function storeProceso(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:50',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        LandingProceso::create($validated);
        Log::info('Landing Proceso creado', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Paso del proceso agregado');
    }

    public function updateProceso(Request $request, LandingProceso $proceso)
    {
        if ($proceso->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|max:50',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $proceso->update($validated);
        Log::info('Landing Proceso actualizado', [
            'proceso_id' => $proceso->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Paso del proceso actualizado');
    }

    public function destroyProceso(LandingProceso $proceso)
    {
        if ($proceso->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando Paso de Proceso', [
            'id' => $proceso->id,
            'titulo' => $proceso->titulo,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        $proceso->delete();
        return back()->with('success', 'Paso del proceso eliminado');
    }

    // ==================== OFERTAS ====================

    public function storeOferta(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'descuento_porcentaje' => 'required|integer|min:1|max:99',
            'precio_original' => 'required|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'caracteristica_1' => 'nullable|string|max:255',
            'caracteristica_2' => 'nullable|string|max:255',
            'caracteristica_3' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['activo'] = $validated['activo'] ?? true;

        // Calcular precio de oferta si no se proporcionó
        if (empty($validated['precio_oferta']) && !empty($validated['precio_original']) && !empty($validated['descuento_porcentaje'])) {
            $validated['precio_oferta'] = round($validated['precio_original'] * (1 - $validated['descuento_porcentaje'] / 100), 2);
        }

        LandingOferta::create($validated);
        Log::info('Landing Oferta creada', [
            'empresa_id' => $validated['empresa_id'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Oferta creada exitosamente');
    }

    public function updateOferta(Request $request, LandingOferta $oferta)
    {
        if ($oferta->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'descuento_porcentaje' => 'required|integer|min:1|max:99',
            'precio_original' => 'required|numeric|min:0',
            'precio_oferta' => 'nullable|numeric|min:0',
            'caracteristica_1' => 'nullable|string|max:255',
            'caracteristica_2' => 'nullable|string|max:255',
            'caracteristica_3' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        // Recalcular precio de oferta
        if (!empty($validated['precio_original']) && !empty($validated['descuento_porcentaje'])) {
            $validated['precio_oferta'] = round($validated['precio_original'] * (1 - $validated['descuento_porcentaje'] / 100), 2);
        }

        $oferta->update($validated);
        Log::info('Landing Oferta actualizada', [
            'oferta_id' => $oferta->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Oferta actualizada exitosamente');
    }

    public function destroyOferta(LandingOferta $oferta)
    {
        if ($oferta->empresa_id !== auth()->user()->empresa_id)
            abort(403);
        Log::warning('Eliminando Oferta', [
            'id' => $oferta->id,
            'titulo' => $oferta->titulo,
            'usuario_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        $oferta->delete();
        return back()->with('success', 'Oferta eliminada exitosamente');
    }
}
