<?php

namespace App\Http\Controllers;

use App\Models\SolicitudMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SolicitudMaterialController extends Controller
{
    public function index()
    {
        $solicitudes = SolicitudMaterial::with(['user', 'items.producto'])
            ->where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('SolicitudesMaterial/Index', [
            'solicitudes' => $solicitudes
        ]);
    }

    public function update(Request $request, SolicitudMaterial $solicitud)
    {
        $request->validate([
            'estado' => 'required|string|in:Pendiente,En Proceso,Aprobada,Rechazada,Entregada',
            'comentarios_admin' => 'nullable|string',
        ]);

        $solicitud->update([
            'estado' => $request->estado,
            'comentarios_admin' => $request->comentarios_admin,
        ]);

        return redirect()->back()->with('success', 'Solicitud actualizada correctamente');
    }

    public function show(SolicitudMaterial $solicitud)
    {
        return Inertia::render('SolicitudesMaterial/Show', [
            'solicitud' => $solicitud->load(['user', 'items.producto'])
        ]);
    }
}
