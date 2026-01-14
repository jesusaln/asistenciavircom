<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SoporteRemotoController extends Controller
{
    public function index()
    {
        return Inertia::render('SoporteRemoto/Index', [
            'remoteUrl' => 'https://remoto.asistenciavircom.com',
            'serverConfig' => [
                'id_server' => 'remoto.asistenciavircom.com',
                'relay_server' => 'remoto.asistenciavircom.com',
                'key' => 'nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=',
            ]
        ]);
    }
}
