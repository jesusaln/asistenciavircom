<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TiendaConfigController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tienda_online_activa' => 'nullable|boolean',
            'google_client_id' => 'nullable|string|max:255',
            'google_client_secret' => 'nullable|string|max:255',
            'microsoft_client_id' => 'nullable|string|max:255',
            'microsoft_client_secret' => 'nullable|string|max:255',
            'microsoft_tenant_id' => 'nullable|string|max:255',
            'mercadopago_access_token' => 'nullable|string|max:255',
            'mercadopago_public_key' => 'nullable|string|max:255',
            'mercadopago_sandbox' => 'nullable|boolean',
            'paypal_client_id' => 'nullable|string|max:255',
            'paypal_client_secret' => 'nullable|string|max:255',
            'paypal_sandbox' => 'nullable|boolean',
            'stripe_public_key' => 'nullable|string|max:255',
            'stripe_secret_key' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'stripe_sandbox' => 'nullable|boolean',
            'cuenta_id_paypal' => 'nullable|exists:cuentas_bancarias,id',
            'cuenta_id_mercadopago' => 'nullable|exists:cuentas_bancarias,id',
            'cuenta_id_stripe' => 'nullable|exists:cuentas_bancarias,id',
            'cva_active' => 'nullable|boolean',
            'cva_user' => 'nullable|string|max:255',
            'cva_password' => 'nullable|string|max:255',
            'cva_utility_percentage' => 'nullable|numeric|min:0|max:100',
            'cva_codigo_sucursal' => 'nullable|integer',
            'cva_paqueteria_envio' => 'nullable|integer',
            'cva_utility_tiers' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en configuracion tienda', ['errors' => $validator->errors()]);
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // 1. Manejo de booleanos (checkboxes no enviados = false)
        $booleanos = ['tienda_online_activa', 'mercadopago_sandbox', 'paypal_sandbox', 'stripe_sandbox', 'cva_active'];
        foreach ($booleanos as $campo) {
            if ($request->has($campo)) {
                $data[$campo] = $request->boolean($campo);
            }
        }

        // 2. Manejo de contraseñas/secretos: Solo actualizar si se envió valor
        $secretos = [
            'cva_password',
            'mercadopago_access_token',
            'paypal_client_secret',
            'stripe_secret_key',
            'stripe_webhook_secret',
            'google_client_secret',
            'microsoft_client_secret'
        ];

        foreach ($secretos as $secreto) {
            // Si el campo es nulo o vacío, lo quitamos para no sobrescribir lo que ya existe
            if (empty($data[$secreto])) {
                unset($data[$secreto]);
            }
        }

        $configuracion = EmpresaConfiguracion::getConfig();
        $configuracion->update($data);
        EmpresaConfiguracion::clearCache();

        // Limpiar caché de precios de productos CVA para que los cambios se apliquen "automáticamente"
        try {
            \Illuminate\Support\Facades\Cache::flush(); // Opción agresiva pero segura para config
            // O más específico si es preferible: \Illuminate\Support\Facades\Redis::del('cva_price_*');
        } catch (\Exception $e) {
            Log::error('Error cleaning CVA price cache: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Configuración de tienda actualizada correctamente.');
    }
}
