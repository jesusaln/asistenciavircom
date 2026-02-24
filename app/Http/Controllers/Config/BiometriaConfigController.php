<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BiometriaConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'biometrics_strict_match' => 'nullable|boolean',
            'biometrics_local_match_threshold' => 'required|numeric|min:0.50|max:0.95',
            'biometrics_local_liveness_threshold' => 'required|numeric|min:0.30|max:0.95',
            'biometrics_geofence_soft_margin_meters' => 'required|integer|min:0|max:5000',
            'biometrics_nearby_match_relax' => 'required|numeric|min:0|max:0.30',
            'biometrics_nearby_liveness_relax' => 'required|numeric|min:0|max:0.30',
            'biometrics_far_match_penalty' => 'required|numeric|min:0|max:0.30',
            'biometrics_far_liveness_penalty' => 'required|numeric|min:0|max:0.30',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuración biométrica', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $data['biometrics_strict_match'] = $request->boolean('biometrics_strict_match');

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        return redirect()->back()->with('success', 'Configuración biométrica actualizada correctamente.');
    }
}

