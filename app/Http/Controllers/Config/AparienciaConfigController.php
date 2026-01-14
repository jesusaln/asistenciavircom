<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ImageOptimizerTrait;

/**
 * Controller para gestión de apariencia visual
 * 
 * Maneja: logos, favicon, colores y temas
 */
class AparienciaConfigController extends Controller
{
    use ApiResponse, ImageOptimizerTrait;

    /**
     * Actualizar colores de la marca
     */
    public function updateColores(Request $request)
    {
        $data = $request->validate([
            'color_principal' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'color_secundario' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        if (!empty($data['color_principal'])) {
            $data['color_principal'] = strtoupper($data['color_principal']);
        }
        if (!empty($data['color_secundario'])) {
            $data['color_secundario'] = strtoupper($data['color_secundario']);
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Colores actualizados correctamente.');
    }

    /**
     * Subir logo de la empresa
     */
    public function subirLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $configuracion = EmpresaConfiguracion::getConfig();

        // Eliminar logo anterior si existe
        if ($configuracion->logo_path && Storage::exists($configuracion->logo_path)) {
            Storage::delete($configuracion->logo_path);
        }

        // Guardar nuevo logo
        $path = $this->saveImageAsWebP($request->file('logo'), 'logos');

        $configuracion->update([
            'logo_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Logo subido correctamente.');
    }

    /**
     * Subir favicon
     */
    public function subirFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:jpeg,png,jpg,gif,ico|max:1024',
        ]);

        $configuracion = EmpresaConfiguracion::getConfig();

        // Eliminar favicon anterior si existe
        if ($configuracion->favicon_path && Storage::exists($configuracion->favicon_path)) {
            Storage::delete($configuracion->favicon_path);
        }

        // Guardar nuevo favicon
        $path = $this->saveImageAsWebP($request->file('favicon'), 'favicons');

        $configuracion->update([
            'favicon_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Favicon subido correctamente.');
    }

    /**
     * Subir logo para reportes
     */
    public function subirLogoReportes(Request $request)
    {
        $request->validate([
            'logo_reportes' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $configuracion = EmpresaConfiguracion::getConfig();

        // Eliminar logo anterior si existe
        if ($configuracion->logo_reportes && Storage::exists($configuracion->logo_reportes)) {
            Storage::delete($configuracion->logo_reportes);
        }

        // Guardar nuevo logo para reportes
        $path = $this->saveImageAsWebP($request->file('logo_reportes'), 'logos_reportes');

        $configuracion->update([
            'logo_reportes' => $path,
        ]);

        return redirect()->back()->with('success', 'Logo para reportes subido correctamente.');
    }

    /**
     * Eliminar logo
     */
    public function eliminarLogo()
    {
        $configuracion = EmpresaConfiguracion::getConfig();

        if ($configuracion->logo_path && Storage::exists($configuracion->logo_path)) {
            Storage::delete($configuracion->logo_path);
        }

        $configuracion->update([
            'logo_path' => null,
        ]);

        return redirect()->back()->with('success', 'Logo eliminado correctamente.');
    }

    /**
     * Eliminar favicon
     */
    public function eliminarFavicon()
    {
        $configuracion = EmpresaConfiguracion::getConfig();

        if ($configuracion->favicon_path && Storage::exists($configuracion->favicon_path)) {
            Storage::delete($configuracion->favicon_path);
        }

        $configuracion->update([
            'favicon_path' => null,
        ]);

        return redirect()->back()->with('success', 'Favicon eliminado correctamente.');
    }

    /**
     * Eliminar logo para reportes
     */
    public function eliminarLogoReportes()
    {
        $configuracion = EmpresaConfiguracion::getConfig();

        if ($configuracion->logo_reportes && Storage::exists($configuracion->logo_reportes)) {
            Storage::delete($configuracion->logo_reportes);
        }

        $configuracion->update([
            'logo_reportes' => null,
        ]);

        return redirect()->back()->with('success', 'Logo para reportes eliminado correctamente.');
    }

    /**
     * Probar configuración de colores
     */
    public function previewColores(Request $request)
    {
        $colores = $request->validate([
            'color_principal' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'color_secundario' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        return $this->success($colores, 'Vista previa de colores');
    }
}
