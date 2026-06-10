<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use App\Models\SatRegimenFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    /**
     * Obtener configuración pública de la empresa (Logo, Nombre, etc.)
     *
     * @param  Request  $request  query: with_logo_pdf=1 incluye logo en data URI (mismo que Empresa → Apariencia / getInfoEmpresa, para jsPDF en Ionic)
     */
    public function publicConfig(Request $request): JsonResponse
    {
        // Obtener configuración usando el método estático que maneja caché y default
        $config = EmpresaConfiguracion::getConfig();

        // Valores por defecto
        $data = [
            'app_name' => config('app.name', 'CDD App'),
            'logo_url' => null,
            /** Misma lógica que resources/views/app.blade.php (favicon empresa o logo.webp) */
            'favicon_url' => asset('images/logo.webp'),
            'primary_color' => '#FF6B35',
            'secondary_color' => '#1E40AF',
            'iva_porcentaje' => 16.00,
            'isr_porcentaje' => 1.25,
            'moneda' => 'MXN',
            'margen_ganancia_default' => 15.00,
            'apk_version' => config('app.apk_version', '1.0.0'),
        ];

        if ($config) {
            $data['app_name'] = $config->nombre_empresa ?? $data['app_name'];

            // Usar el accesor del modelo que ya devuelve la URL completa
            if ($config->logo_url) {
                $data['logo_url'] = url($config->logo_url);
            }

            if ($config->favicon_url) {
                $data['favicon_url'] = url($config->favicon_url);
            }

            $data['primary_color'] = $config->color_principal ?? $data['primary_color'];
            $data['secondary_color'] = $config->color_secundario ?? $data['secondary_color'];

            // Configuración fiscal y márgenes
            $data['iva_porcentaje'] = (float) ($config->iva_porcentaje ?? 16.00);
            $data['isr_porcentaje'] = (float) ($config->isr_porcentaje ?? 1.25);
            $data['moneda'] = $config->moneda ?? 'MXN';
            $data['margen_ganancia_default'] = (float) ($config->margen_ganancia_default ?? 15.00);

            // Información general (misma pestaña que /empresa/configuracion → GeneralTab.vue)
            $data['nombre_empresa'] = $config->nombre_empresa;
            $data['razon_social'] = $config->razon_social;
            $data['rfc'] = $config->rfc;
            $data['regimen_fiscal'] = $config->regimen_fiscal;
            $data['regimen_fiscal_descripcion'] = $config->regimen_fiscal
                ? SatRegimenFiscal::where('clave', $config->regimen_fiscal)->value('descripcion')
                : null;

            $data['calle'] = $config->calle;
            $data['numero_exterior'] = $config->numero_exterior;
            $data['numero_interior'] = $config->numero_interior;
            $data['codigo_postal'] = $config->codigo_postal;
            $data['colonia'] = $config->colonia;
            $data['ciudad'] = $config->ciudad;
            $data['estado'] = $config->estado;
            $data['pais'] = $config->pais;
            $data['direccion_completa'] = $config->direccion_completa;

            $data['telefono'] = $config->telefono;
            $data['whatsapp'] = $config->whatsapp;
            $data['email'] = $config->email;
            $data['sitio_web'] = $config->sitio_web;

            $desc = $config->descripcion_empresa ?? null;
            $data['descripcion_empresa'] = $desc !== null && $desc !== ''
                ? trim(strip_tags((string) $desc))
                : null;

            // Pie configurado en Empresa → Documentos (ventas), alineado con getPiePagina('ventas')
            $pieVentas = $config->pie_pagina_ventas ?? null;
            $data['pie_pagina_ventas'] = $pieVentas !== null && $pieVentas !== ''
                ? trim(strip_tags((string) $pieVentas))
                : null;

            // Datos bancarios (misma fuente que pdf.blade.php de ventas)
            $data['banco'] = $config->banco ?? null;
            $data['cuenta'] = $config->cuenta ?? null;
            $data['clabe'] = $config->clabe ?? null;
            $data['titular'] = $config->titular ?? null;
            $data['nombre_titular'] = $config->nombre_titular ?? null;
        }

        if ($request->boolean('with_logo_pdf') && $config) {
            $info = EmpresaConfiguracion::getInfoEmpresa();
            $data['logo_base64'] = $info['logo_base64'] ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
