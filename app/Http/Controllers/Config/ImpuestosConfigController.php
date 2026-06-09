<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImpuestosConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iva_porcentaje' => 'nullable|numeric|min:0|max:100',
            'isr_porcentaje' => 'nullable|numeric|min:0|max:100',
            'moneda' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion impuestos', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        if (!empty($data['moneda'])) {
            $data['moneda'] = strtoupper($data['moneda']);
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de impuestos actualizada correctamente.');
    }
}
