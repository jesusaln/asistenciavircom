<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CobrosConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_cobros' => 'nullable|email|max:255',
            'cobros_hora_reporte' => 'nullable|string|max:5',
            'cobros_reporte_automatico' => 'nullable|boolean',
            'cobros_dias_anticipacion' => 'nullable|integer|min:0|max:60',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion cobros', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        if ($request->has('cobros_reporte_automatico')) {
            $data['cobros_reporte_automatico'] = $request->boolean('cobros_reporte_automatico');
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de cobros actualizada correctamente.');
    }
}
