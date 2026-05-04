<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsistenciaController extends Controller
{
    /**
     * Reloj checador (entrada/salida) — pantalla base hasta conectar modelo/API.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Asistencia/Index', [
            'usuario' => [
                'name' => $request->user()->name,
            ],
        ]);
    }

    /**
     * Bitácora de asistencia (solo quien puede ver empleados o es admin).
     */
    public function registros(Request $request): Response
    {
        $user = $request->user();

        $allowed = $user->hasRole(['admin', 'super-admin']) || $user->can('view empleados');

        if (! $allowed) {
            abort(403);
        }

        return Inertia::render('Asistencia/Registros', [
            'registros' => [],
        ]);
    }
}
