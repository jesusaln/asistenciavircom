<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PagosConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_pagos' => 'nullable|email|max:255',
            'pagos_hora_reporte' => 'nullable|string|max:5',
            'pagos_reporte_automatico' => 'nullable|boolean',
            'pagos_dias_anticipacion' => 'nullable|integer|min:0|max:60',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion pagos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        if ($request->has('pagos_reporte_automatico')) {
            $data['pagos_reporte_automatico'] = $request->boolean('pagos_reporte_automatico');
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de pagos actualizada correctamente.');
    }
}
