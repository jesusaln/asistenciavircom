<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SoportePublicoController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/Soporte/Index');
    }

    public function ticketStatus(Request $request)
    {
        // Simple status checker for public
        return Inertia::render('Public/Soporte/Status');
    }
}
