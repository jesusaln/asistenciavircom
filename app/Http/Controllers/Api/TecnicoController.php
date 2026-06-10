<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    public function index()
    {
        try {
            $tecnicos = \App\Models\User::tecnicos()
                ->where(function ($query) {
                    $query->where('activo', true)->orWhereNull('activo');
                })
                ->select('id', 'name as nombre', 'email', 'activo') // name replaces nombre/apellido in unified model
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tecnicos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los técnicos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza las coordenadas GPS del técnico desde la app móvil Ionic
     */
    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        $user->update([
            'latitud' => $validated['latitud'],
            'longitud' => $validated['longitud'],
            'ultima_fecha_gps' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ubicación actualizada correctamente.',
            'domicilio_actual' => $user->domicilio_actual,
            'cita_actual' => $user->cita_actual,
        ]);
    }

    /**
     * Consulta el estado, ubicación GPS y domicilio actual de todos los técnicos activos
     */
    public function ubicaciones()
    {
        try {
            $tecnicos = User::tecnicosActivos()
                ->select('id', 'name', 'telefono', 'latitud', 'longitud', 'ultima_fecha_gps')
                ->orderBy('name')
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'nombre' => $t->name,
                    'telefono' => $t->telefono,
                    'latitud' => $t->latitud ? (float) $t->latitud : null,
                    'longitud' => $t->longitud ? (float) $t->longitud : null,
                    'ultima_fecha_gps' => $t->ultima_fecha_gps?->format('Y-m-d H:i:s'),
                    'ultima_fecha_gps_humano' => $t->ultima_fecha_gps?->diffForHumans(),
                    'domicilio_actual' => $t->domicilio_actual,
                    'cita_actual' => $t->cita_actual,
                ]);

            return response()->json([
                'success' => true,
                'data' => $tecnicos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar ubicaciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
