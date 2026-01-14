<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Obtener configuración pública de la empresa (Logo, Nombre, etc.)
     */
    public function publicConfig(): JsonResponse
    {
        // Obtener configuración usando el método estático que maneja caché y default
        $config = EmpresaConfiguracion::getConfig();

        // Valores por defecto
        $data = [
            'app_name' => config('app.name', 'CDD App'),
            'logo_url' => null,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
        ];

        if ($config) {
            $data['app_name'] = $config->nombre_empresa ?? $data['app_name'];
            
            // Usar el accesor del modelo que ya devuelve la URL completa
            if ($config->logo_url) {
                $data['logo_url'] = url($config->logo_url);
            }

            $data['primary_color'] = $config->color_principal ?? $data['primary_color'];
            $data['secondary_color'] = $config->color_secundario ?? $data['secondary_color'];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
