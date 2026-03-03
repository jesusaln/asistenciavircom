<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SoporteRemotoController extends Controller
{
    public function index()
    {
        $config = EmpresaConfiguracion::getConfig();

        return Inertia::render('SoporteRemoto/Index', [
            'remoteUrl' => config('rustdesk.panel_url', 'https://remoto.asistenciavircom.com'),
            'serverConfig' => [
                'id_server' => $config->rustdesk_server_address ?: 'remoto.asistenciavircom.com',
                'relay_server' => $config->rustdesk_relay_server ?: 'remoto.asistenciavircom.com',
                'key' => $config->rustdesk_public_key ?: '',
            ]
        ]);
    }
}
