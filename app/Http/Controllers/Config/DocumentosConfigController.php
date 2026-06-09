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
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion documentos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($validator->validated());
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de documentos actualizada correctamente.');
    }
}
