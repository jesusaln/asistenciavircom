<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportesController extends Controller
{
    /**
     * Muestra el dashboard principal de reportes
     */
    public function index()
    {
        return Inertia::render('Reportes/Dashboard');
    }
}
