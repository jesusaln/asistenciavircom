<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RedConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dominio_principal' => 'nullable|string|max:255',
            'dominio_secundario' => 'nullable|string|max:255',
            'servidor_ipv4' => 'nullable|string|max:45',
            'servidor_ipv6' => 'nullable|string|max:45',
            'ssl_enabled' => 'nullable|boolean',
            'ssl_certificado_path' => 'nullable|string|max:255',
            'ssl_key_path' => 'nullable|string|max:255',
            'ssl_ca_bundle_path' => 'nullable|string|max:255',
            'ssl_fecha_expiracion' => 'nullable|date',
            'ssl_proveedor' => 'nullable|string|max:255',
            'app_url' => 'nullable|string|max:255',
            'force_https' => 'nullable|boolean',
            'zerotier_enabled' => 'nullable|boolean',
            'zerotier_network_id' => 'nullable|string|max:100',
            'zerotier_ip' => 'nullable|string|max:45',
            'zerotier_node_id' => 'nullable|string|max:100',
            'zerotier_notas' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion red', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $booleanos = ['ssl_enabled', 'force_https', 'zerotier_enabled'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración de red actualizada correctamente.');
    }
}
