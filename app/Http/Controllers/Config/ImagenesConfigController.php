<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImagenesConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images_webp_enabled' => 'nullable|boolean',
            'images_webp_quality' => 'required|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion de imágenes', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['images_webp_enabled'] = $request->boolean('images_webp_enabled');

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de imágenes actualizada correctamente.');
    }
}

