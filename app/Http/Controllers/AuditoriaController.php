<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $auditorias = Auditoria::orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Bitacora/Auditoria', [
            'auditorias' => $auditorias
        ]);
    }

    public function clear()
    {
        \App\Helpers\ActivityLogger::log("Limpió la bitácora de auditoría (se borraron todos los registros)");
        Auditoria::truncate();
        return redirect()->back()->with('success', 'Bitácora de auditoría limpiada correctamente.');
    }
}
