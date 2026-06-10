<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EmpresaSwitcherController extends Controller
{
    public function switch(Request $request)
    {
        $company = $request->input('company'); // 'climas' or 'vircom'
        
        if (in_array($company, ['climas', 'vircom'])) {
            // Validar si el usuario tiene permiso explícito
            $user = $request->user();
            if ($user && !empty($user->empresas_acceso)) {
                $allowed = explode(',', $user->empresas_acceso);
                if (!in_array($company, $allowed)) {
                    return back()->with('error', 'No tienes permiso para acceder a ' . ($company === 'climas' ? 'Climas del Desierto' : 'Asistencia Vircom'));
                }
            }

            // Usar cookie y session para máxima compatibilidad y persistencia
            $request->session()->put('selected_company', $company);
            $cookie = cookie()->forever('selected_company', $company);
            \Illuminate\Support\Facades\Cache::flush();
            
            return back()
                ->with('success', "Empresa cambiada a " . ($company === 'climas' ? 'Climas del Desierto' : 'Asistencia Vircom'))
                ->withCookie($cookie);
        }

        return back()->with('error', 'Empresa no válida');
    }
}
