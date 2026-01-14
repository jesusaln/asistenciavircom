<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SeguridadConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intentos_login' => 'nullable|integer|min:1|max:20',
            'tiempo_bloqueo' => 'nullable|integer|min:1|max:1440',
            'requerir_2fa' => 'nullable|boolean',
            'dkim_selector' => 'nullable|string|max:255',
            'dkim_domain' => 'nullable|string|max:255',
            'dkim_public_key' => 'nullable|string|max:2000',
            'dkim_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion seguridad', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $booleanos = ['requerir_2fa', 'dkim_enabled'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de seguridad actualizada correctamente.');
    }
}
