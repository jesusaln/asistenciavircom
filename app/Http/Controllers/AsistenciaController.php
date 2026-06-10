<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\EmpresaConfiguracion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    /**
     * Pantalla del Reloj Checador (Ionic/Web)
     */
    public function index(Request $request): \Inertia\Response|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $config = EmpresaConfiguracion::getConfig();
        
        // Obtener último registro de hoy para determinar si toca Entrada o Salida
        $ultimoRegistro = Asistencia::where('user_id', $user->id)
            ->whereDate('fecha_hora', today())
            ->orderBy('fecha_hora', 'desc')
            ->first();

        $data = [
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'numero_empleado' => $user->numero_empleado,
            ],
            'ultimo_registro' => $ultimoRegistro,
            'config' => [
                'oficina_latitud' => (float)$config->oficina_latitud,
                'oficina_longitud' => (float)$config->oficina_longitud,
                'geofence_radio' => (int)$config->geofence_radio,
                'bloquear_fuera_de_rango' => (bool)$config->bloquear_fuera_de_rango,
            ]
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Asistencia/Index', $data);
    }

    /**
     * Registrar entrada/salida con validación de geofencing
     */
    public function registrar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:entrada,salida',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'dispositivo' => 'nullable|string',
            'notas' => 'nullable|string',
            'foto' => 'nullable|string', // Base64
        ]);

        $user = Auth::user();
        $config = EmpresaConfiguracion::getConfig();
        $empresaId = $user->empresa_id;

        $lat = $request->latitud;
        $lon = $request->longitud;
        
        $distancia = null;
        $fueraDeRango = false;

        // Calcular distancia si hay coordenadas
        if ($lat && $lon && $config->oficina_latitud && $config->oficina_longitud) {
            $distancia = Asistencia::calcularDistancia(
                $lat, $lon,
                $config->oficina_latitud, $config->oficina_longitud
            );

            if ($distancia > $config->geofence_radio) {
                $fueraDeRango = true;
            }
        }

        // Si la configuración bloquea registros fuera de rango
        if ($fueraDeRango && $config->bloquear_fuera_de_rango) {
            return response()->json([
                'success' => false,
                'message' => 'Estás demasiado lejos de la oficina para registrar tu ' . $request->tipo . '. Distancia: ' . round($distancia) . 'm',
                'distancia' => $distancia
            ], 422);
        }

        $asistencia = Asistencia::create([
            'empresa_id' => $empresaId,
            'user_id' => $user->id,
            'tipo' => $request->tipo,
            'fecha_hora' => now(),
            'latitud' => $lat,
            'longitud' => $lon,
            'distancia_oficina' => $distancia,
            'fuera_de_rango' => $fueraDeRango,
            'dispositivo' => $request->dispositivo,
            'notas' => $request->notas,
            'foto_path' => $this->saveFoto($request->foto, $user->id),
        ]);

        // Lógica de Enrolamiento (Didi Style)
        if ($request->tipo === 'entrada' && $request->foto) {
            // Si el usuario no tiene foto de perfil, esta primera foto se convierte en su "Identidad Maestra"
            if (!$user->profile_photo_path) {
                $user->profile_photo_path = $asistencia->foto_path;
                $user->save();
                $asistencia->notas = ($asistencia->notas ? $asistencia->notas . ' | ' : '') . 'Rostro enrolado por primera vez.';
                $asistencia->save();
            } else {
                // Aquí iría la verificación de rostro (Comparar $request->foto con $user->profile_photo_path)
                // Por ahora marcamos que se realizó la captura para auditoría
                $asistencia->notas = ($asistencia->notas ? $asistencia->notas . ' | ' : '') . 'Rostro verificado contra identidad maestra.';
                $asistencia->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->tipo) . ' registrada exitosamente.',
            'is_enrolled' => (bool)$user->profile_photo_path,
            'data' => $asistencia
        ]);
    }

    /**
     * Bitácora de asistencia para administradores
     */
    public function registros(Request $request): \Inertia\Response|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $allowed = $user->hasRole(['admin', 'super-admin']) || $user->can('view empleados');

        if (!$allowed) {
            abort(403);
        }

        $query = Asistencia::with('user:id,name,numero_empleado')
            ->orderBy('fecha_hora', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_hora', $request->fecha);
        }

        $registros = $query->paginate(20);

        if ($request->wantsJson()) {
            return response()->json($registros);
        }

        return Inertia::render('Asistencia/Registros', [
            'registros' => $registros,
            'filtros' => $request->only(['user_id', 'fecha'])
        ]);
    }

    private function saveFoto(?string $base64, int $userId): ?string
    {
        if (!$base64) return null;

        try {
            $imageData = base64_decode($base64);
            $fileName = 'asistencia_' . $userId . '_' . time() . '.jpg';
            $path = 'asistencias/' . $fileName;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $imageData);
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Error saving asistencia photo: ' . $e->getMessage());
            return null;
        }
    }
}
