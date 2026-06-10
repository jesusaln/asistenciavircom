<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    public function index()
    {
        return Inertia::render('Tracking/Index', [
            'tecnicos' => $this->getUbicaciones(),
        ]);
    }

    public function data()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->getUbicaciones(),
            ]);
        } catch (\Exception $e) {
            Log::error('Tracking data error: ' . $e->getMessage());
            return response()->json(['success' => false, 'data' => []], 500);
        }
    }

    private function getUbicaciones(): array
    {
        return User::tecnicosActivos()
            ->select('id', 'name', 'telefono', 'latitud', 'longitud', 'ultima_fecha_gps', 'es_tecnico')
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'nombre' => $t->name,
                'telefono' => $t->telefono,
                'latitud' => $t->latitud ? (float) $t->latitud : null,
                'longitud' => $t->longitud ? (float) $t->longitud : null,
                'ultima_fecha_gps' => $t->ultima_fecha_gps?->toIso8601String(),
                'ultima_fecha_gps_humano' => $t->ultima_fecha_gps?->diffForHumans(),
                'domicilio_actual' => $t->domicilio_actual,
                'cita_actual' => $t->cita_actual,
            ])
            ->toArray();
    }
}
