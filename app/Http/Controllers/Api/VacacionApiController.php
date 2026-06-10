<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vacacion;
use App\Models\User;
use App\Models\RegistroVacaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class VacacionApiController extends Controller
{
    /**
     * Obtener historial de vacaciones del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $query = Vacacion::where('user_id', $user->id)
                ->with(['aprobador:id,name']);

            if ($estado = $request->input('estado')) {
                $query->where('estado', $estado);
            }

            $vacaciones = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $vacaciones
            ]);
        } catch (Exception $e) {
            Log::error('Error en VacacionApiController@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el historial de vacaciones.'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas/saldo de vacaciones del usuario
     */
    public function stats()
    {
        try {
            $user = Auth::user();
            
            // Asegurarse de que el registro anual esté actualizado
            $registro = RegistroVacaciones::actualizarRegistroAnual($user->id);

            if (!$registro) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No se encontró registro de vacaciones. Verifique que el usuario esté marcado como empleado y tenga fecha de contratación.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'anio' => $registro->anio,
                    'dias_correspondientes' => $registro->dias_correspondientes,
                    'dias_disponibles' => $registro->dias_disponibles,
                    'dias_utilizados' => $registro->dias_utilizados,
                    'dias_pendientes' => $registro->dias_pendientes,
                    'antiguedad' => $user->antiguedad,
                    'fecha_contratacion' => $user->fecha_contratacion,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error en VacacionApiController@stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de vacaciones.'
            ], 500);
        }
    }

    /**
     * Crear una nueva solicitud de vacaciones
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'fecha_inicio' => 'required|date|after_or_equal:today',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'motivo' => 'nullable|string|max:500',
            ]);

            $fechaInicio = \Carbon\Carbon::parse($validated['fecha_inicio']);
            $fechaFin = \Carbon\Carbon::parse($validated['fecha_fin']);
            $diasSolicitados = $fechaInicio->diffInDays($fechaFin) + 1;

            // Verificar conflicto de fechas
            $conflicto = Vacacion::where('user_id', $user->id)
                ->whereIn('estado', ['pendiente', 'aprobada'])
                ->where(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                        ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin]);
                })
                ->exists();

            if ($conflicto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tiene una solicitud pendiente o aprobada en esas fechas.'
                ], 422);
            }

            // Verificar disponibilidad de días
            $registro = RegistroVacaciones::actualizarRegistroAnual($user->id);
            if (!$registro || !$registro->tieneDiasDisponibles($diasSolicitados)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene suficientes días disponibles. Días disponibles: ' . ($registro->dias_pendientes ?? 0)
                ], 422);
            }

            $vacacion = Vacacion::create([
                'user_id' => $user->id,
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'dias_solicitados' => $diasSolicitados,
                'dias_pendientes' => $diasSolicitados,
                'motivo' => $validated['motivo'],
                'estado' => 'pendiente',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de vacaciones enviada correctamente.',
                'data' => $vacacion
            ]);

        } catch (Exception $e) {
            Log::error('Error en VacacionApiController@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una solicitud de vacaciones (si está pendiente)
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();
            $vacacion = Vacacion::where('user_id', $user->id)->findOrFail($id);

            if ($vacacion->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar solicitudes pendientes.'
                ], 422);
            }

            $vacacion->update(['estado' => 'cancelada']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud cancelada correctamente.'
            ]);
        } catch (Exception $e) {
            Log::error('Error en VacacionApiController@cancel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la solicitud.'
            ], 500);
        }
    }
    /**
     * Sincronizar al usuario actual como empleado si no lo está
     */
    public function syncEmployee(Request $request)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            $updates = [
                'es_empleado' => true,
            ];

            // Si no tiene fecha de contratación, poner inicio de año como default
            if (!$user->fecha_contratacion) {
                $updates['fecha_contratacion'] = now()->startOfYear();
            }

            $user->update($updates);

            // Generar/Actualizar registro de vacaciones
            $registro = \App\Models\RegistroVacaciones::actualizarRegistroAnual($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Perfil sincronizado como empleado correctamente.',
                'data' => [
                    'es_empleado' => $user->es_empleado,
                    'fecha_contratacion' => $user->fecha_contratacion,
                    'registro' => $registro
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error en VacacionApiController@syncEmployee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar perfil: ' . $e->getMessage()
            ], 500);
        }
    }
}
