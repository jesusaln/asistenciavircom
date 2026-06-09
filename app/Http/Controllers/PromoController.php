<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrmProspecto;
use App\Models\Empresa;

class PromoController extends Controller
{
    public function index()
    {
        return view('promo');
    }

    public function storeLead(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
        ]);

        // Buscar empresa por default (ID 1)
        $empresaId = Empresa::first()->id ?? 1;

        $prospecto = CrmProspecto::create([
            'empresa_id' => $empresaId,
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'origen' => 'web',
            'etapa' => 'interesado',
            'notas' => "Lead capturado desde Landing de Promoción (Cámara HiLook $2,950)",
            'prioridad' => 'alta',
            'created_by' => 1, // Asignar al admin principal
        ]);

        // Redirigir a WhatsApp con mensaje pre-rellenado
        $mensaje = urlencode("Hola! Soy {$request->nombre}. Acabo de dejar mis datos en la promo de la cámara HiLook de $2,950. ¡Quiero apartar la mía!");
        $waUrl = "https://wa.me/5216624590092?text={$mensaje}";

        return response()->json([
            'success' => true,
            'redirect' => $waUrl
        ]);
    }
}
