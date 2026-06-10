<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DocumentosConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pie_pagina_facturas' => 'nullable|string|max:1000',
            'pie_pagina_cotizaciones' => 'nullable|string|max:1000',
            'pie_pagina_ventas' => 'nullable|string|max:1000',
            'terminos_condiciones' => 'nullable|string|max:2000',
            'politica_privacidad' => 'nullable|string|max:2000',
            'firma_digital' => 'nullable|string', // Acepta el base64 de la firma
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion documentos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // ✅ PERFORMANCE: Guardar firma digital como archivo en Storage en vez de base64 en la BD
        if (!empty($validated['firma_digital']) && str_starts_with($validated['firma_digital'], 'data:')) {
            $firmaPath = \App\Helpers\Base64ToFile::save($validated['firma_digital'], 'empresa/firmas', 'firma_digital');
            if ($firmaPath) {
                $validated['firma_digital'] = $firmaPath;
            }
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($validated);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de documentos actualizada correctamente.');
    }
}
