<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SoporteRemotoController extends Controller
{
    public function index()
    {
        return Inertia::render('SoporteRemoto/Index', [
            'remoteUrl' => 'https://soporte.climasdeldesierto.com'
        ]);
    }
}
