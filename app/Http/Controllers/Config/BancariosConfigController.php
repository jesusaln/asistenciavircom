<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BancariosConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banco' => 'nullable|string|max:255',
            'sucursal' => 'nullable|string|max:50',
            'cuenta' => 'nullable|string|max:50',
            'clabe' => 'nullable|string|max:18',
            'titular' => 'nullable|string|max:255',
            'numero_cuenta' => 'nullable|string|max:50',
            'numero_tarjeta' => 'nullable|string|max:19|regex:/^[0-9\s\-]{13,19}$/',
            'nombre_titular' => 'nullable|string|max:255',
            'informacion_adicional_bancaria' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion bancaria', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($validator->validated());
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración bancaria actualizada correctamente.');
    }
}
