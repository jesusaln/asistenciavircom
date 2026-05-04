<?php

namespace App\Helpers;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log($mensaje)
    {
        try {
            Auditoria::create([
                'usuario' => Auth::user()?->name ?? 'Sistema',
                'actividad' => $mensaje
            ]);
        } catch (\Exception $e) {
            \Log::error("Error registrando auditoría: " . $e->getMessage());
        }
    }
}
