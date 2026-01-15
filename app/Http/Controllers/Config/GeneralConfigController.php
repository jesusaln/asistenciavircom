<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GeneralConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre_empresa' => 'required|string|max:255',
            'rfc' => 'nullable|string|min:12|max:13',
            'razon_social' => 'nullable|string|max:255',
            'regimen_fiscal' => 'nullable|string|max:100',
            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:50',
            'numero_interior' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'colonia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'pais' => 'nullable|string|max:255',
            'descripcion_empresa' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion general', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if (isset($data['nombre_empresa'])) {
            $data['nombre_empresa'] = trim($data['nombre_empresa']);
        }

        if (!empty($data['rfc'])) {
            $data['rfc'] = strtoupper(trim($data['rfc']));
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración general actualizada correctamente.');
    }

    /**
     * Actualiza las URLs de redes sociales
     */
    public function updateRedesSociales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'n8n_webhook_blog' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en redes sociales', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // Limpiar URLs vacías
        foreach ($data as $key => $value) {
            $data[$key] = trim($value) ?: null;
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Redes sociales actualizadas correctamente.');
    }
}
