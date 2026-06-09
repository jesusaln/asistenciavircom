<?php

namespace App\Http\Controllers;

use App\Models\SeoLandingPage;
use App\Models\Producto;
use App\Models\EmpresaConfiguracion;
use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PublicSeoLandingController extends Controller
{
    /**
     * Resuelve una landing SEO dinámica por su slug
     */
    public function show(string $slug)
    {
        $empresaId = EmpresaResolver::resolveId();

        $page = SeoLandingPage::where('empresa_id', $empresaId)
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        // Buscar productos relacionados según la categoría de la landing
        $productos = Producto::where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($page->service_category) {
            // Unir con categorías de productos si existen
            $productos->whereHas('categorias', function ($q) use ($page) {
                $q->where('nombre', 'like', '%' . $page->service_category . '%');
            });
        }

        $productosRelacionados = $productos->latest()->take(4)->get();

        // Obtener configuración de empresa para colores/logos
        $empresaModel = Empresa::find($empresaId);
        $config = EmpresaConfiguracion::getConfig($empresaId);

        $empresaData = array_merge($empresaModel ? $empresaModel->toArray() : [], [
            'color_principal' => $config->color_principal,
            'color_secundario' => $config->color_secundario,
            'color_terciario' => $config->color_terciario,
            'logo_url' => $config->logo_url,
            'favicon_url' => $config->favicon_url,
            'nombre_empresa' => $config->nombre_empresa,
            'whatsapp' => $config->whatsapp,
            'email' => $config->email,
            'telefono' => $config->telefono,
        ]);

        return Inertia::render('Public/SeoLanding', [
            'page' => $page,
            'productos' => $productosRelacionados,
            'empresa' => $empresaData,
        ]);
    }
}
