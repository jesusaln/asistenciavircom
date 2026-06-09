<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SoportePublicoController extends Controller
{
    public function index()
    {
        $view = config('app.business') === 'climas' ? 'PublicClimas/Soporte/Index' : 'Public/Soporte/Index';
        return Inertia::render($view);
    }

    public function ticketStatus(Request $request)
    {
        $view = config('app.business') === 'climas' ? 'PublicClimas/Soporte/Status' : 'Public/Soporte/Status';
        return Inertia::render($view);
    }
}
