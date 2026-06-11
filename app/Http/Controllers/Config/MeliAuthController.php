<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Services\MeliService;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeliAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:edit configuracion_empresa');
    }

    public function redirect(MeliService $meli)
    {
        if (!$meli->isConfigured()) {
            return redirect()->back()->withErrors(['meli' => 'Primero guarda el App ID y Client Secret.']);
        }

        $redirectUri = route('empresa-configuracion.meli.callback');
        $url = $meli->getAuthUrl($redirectUri);

        return redirect($url);
    }

    public function callback(Request $request, MeliService $meli)
    {
        $code = $request->query('code');
        $error = $request->query('error');

        if ($error || !$code) {
            Log::warning('Meli auth cancelled or error', ['error' => $error]);
            return redirect()->route('empresa-configuracion.index', ['tab' => 'tienda'])
                ->with('error', 'Autenticación con MercadoLibre cancelada.');
        }

        $redirectUri = route('empresa-configuracion.meli.callback');
        $result = $meli->authenticate($code, $redirectUri);

        if (isset($result['success'])) {
            return redirect()->route('empresa-configuracion.index', ['tab' => 'tienda'])
                ->with('success', '✅ Conectado a MercadoLibre correctamente.');
        }

        return redirect()->route('empresa-configuracion.index', ['tab' => 'tienda'])
            ->withErrors(['meli' => $result['error'] ?? 'Error al conectar con MercadoLibre.']);
    }

    public function disconnect(MeliService $meli)
    {
        $config = EmpresaConfiguracion::getConfig();
        $config->update([
            'meli_access_token' => null,
            'meli_refresh_token' => null,
            'meli_user_id' => null,
            'meli_token_expires_at' => null,
        ]);
        EmpresaConfiguracion::clearCache();

        return redirect()->route('empresa-configuracion.index', ['tab' => 'tienda'])
            ->with('success', 'Desconectado de MercadoLibre.');
    }
}
