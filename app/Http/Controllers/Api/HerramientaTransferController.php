<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Herramienta;
use App\Models\TransferenciaHerramienta;
use App\Models\TransferenciaHerramientaItem;
use App\Models\HistorialHerramienta;
use App\Models\Notification;
use App\Models\User;
use App\Support\EmpresaResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HerramientaTransferController extends Controller
{
    /**
     * Listar transferencias pendientes para el usuario actual.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->query('type', 'received'); // 'sent' o 'received'

            $query = TransferenciaHerramienta::with(['emisor', 'receptor', 'herramientas'])
                ->where('estado', 'pendiente');

            if ($type === 'sent') {
                $query->where('emisor_id', $user->id);
            } else {
                $query->where('receptor_id', $user->id);
            }

            return response()->json($query->orderByDesc('created_at')->get());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener transferencias: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear una nueva transferencia (Asignación o Traspaso).
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('!!! PETICION RECIBIDA EN HerramientaTransferController@store !!!', [
            'data' => $request->all(),
            'user' => auth()->id()
        ]);
        try {
            $request->validate([
                'receptor_id' => 'required|exists:users,id',
                'herramientas_ids' => 'required|array',
                'herramientas_ids.*' => 'exists:herramientas,id',
                'observaciones' => 'nullable|string'
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $receptorId = $request->input('receptor_id');
            $herramientasIds = $request->input('herramientas_ids');

            // 1. Identificar permisos de administración
            $esAdmin = $user->hasAnyRole(['super-admin', 'admin', 'compras']);

            // 2. Obtener herramientas y validar pertenencia
            $herramientas = Herramienta::whereIn('id', $herramientasIds)->get();
            $ownerIds = $herramientas->pluck('user_id')->unique();

            if (!$esAdmin) {
                // Si no es admin, todas deben pertenecerle y no pueden ser nulas
                if ($ownerIds->count() > 1 || $ownerIds->first() !== $user->id) {
                    return response()->json(['error' => 'Una o más herramientas no te pertenecen.'], 403);
                }
                $emisorId = $user->id;
            } else {
                // Si es admin, el emisor es el dueño actual. 
                // Si hay herramientas del almacén (null), el emisor es el admin mismo.
                $emisorId = $ownerIds->contains(null) ? $user->id : $ownerIds->first();
            }

            if ($receptorId == $emisorId) {
                return response()->json(['error' => 'No puedes transferir herramientas al mismo dueño actual.'], 422);
            }

            DB::beginTransaction();

            // Resolver ID de empresa de forma segura para evitar Tenant Mismatch
            $empresaId = EmpresaResolver::resolveId() ?? $user->empresa_id;

            // 3. Crear la cabecera de la transferencia
            $transferencia = TransferenciaHerramienta::create([
                'emisor_id' => $emisorId,
                'receptor_id' => $receptorId,
                'estado' => 'pendiente',
                'observaciones' => $request->input('observaciones'),
                'empresa_id' => $empresaId
            ]);

            // 4. Vincular herramientas a la transferencia
            foreach ($herramientasIds as $id) {
                TransferenciaHerramientaItem::create([
                    'transferencia_id' => $transferencia->id,
                    'herramienta_id' => $id
                ]);
            }

            // 5. Notificar al receptor
            $nombresPrev = $herramientas->pluck('nombre')->take(2)->join(', ');
            $total = $herramientas->count();
            $emisorNombre = ($user->id === $emisorId) ? $user->name : (User::find($emisorId)?->name ?? 'Sistema');
            
            $msg = "{$emisorNombre} te envió {$total} herramientas: {$nombresPrev}" . ($total > 2 ? "..." : "");

            Notification::create([
                'user_id' => $receptorId,
                'notifiable_id' => $receptorId,
                'notifiable_type' => 'App\Models\User',
                'type' => 'transferencia_herramientas',
                'title' => 'Nueva Entrega de Equipo',
                'message' => $msg,
                'data' => ['transferencia_id' => $transferencia->id],
                'action_url' => '/tabs/mis-herramientas',
                'icon' => 'fas fa-tools'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'id' => $transferencia->id]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error("Error Fatal en Traspaso Masivo: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error crítico en el servidor.',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Aceptar una transferencia.
     */
    public function accept(TransferenciaHerramienta $transferencia)
    {
        $user = Auth::user();

        if ($transferencia->receptor_id !== $user->id) {
            return response()->json(['error' => 'No estás autorizado para aceptar esta entrega.'], 403);
        }

        if ($transferencia->estado !== 'pendiente') {
            return response()->json(['error' => 'Esta entrega ya fue procesada anteriormente.'], 422);
        }

        try {
            DB::beginTransaction();

            $transferencia->update(['estado' => 'aceptada']);

            foreach ($transferencia->herramientas as $herramienta) {
                // Registrar salida del dueño anterior si existía
                if ($transferencia->emisor_id) {
                    HistorialHerramienta::create([
                        'empresa_id' => $transferencia->empresa_id,
                        'herramienta_id' => $herramienta->id,
                        'user_id' => $transferencia->emisor_id,
                        'tecnico_id' => $transferencia->emisor_id,
                        'tipo_asignacion' => 'individual',
                        'fecha_asignacion' => $herramienta->fecha_asignacion ?? now(),
                        'fecha_devolucion' => now(),
                        'estado_herramienta_devolucion' => $herramienta->estado,
                        'observaciones_devolucion' => "Entrega aceptada por {$user->name}",
                        'recibido_por' => $user->id
                    ]);
                }

                // Actualizar herramienta al nuevo dueño sin disparar el Observer (evita duplicados)
                \App\Models\Herramienta::withoutEvents(function() use ($herramienta, $user) {
                    $herramienta->update([
                        'user_id' => $user->id,
                        'tecnico_id' => $user->id,
                        'estado' => Herramienta::ESTADO_ASIGNADA,
                        'fecha_asignacion' => now()
                    ]);
                });

                // Registrar entrada al nuevo dueño (ya marcada como confirmada)
                HistorialHerramienta::create([
                    'empresa_id' => $transferencia->empresa_id,
                    'herramienta_id' => $herramienta->id,
                    'user_id' => $user->id,
                    'tecnico_id' => $user->id,
                    'tipo_asignacion' => 'individual',
                    'fecha_asignacion' => now(),
                    'estado_herramienta_asignacion' => Herramienta::ESTADO_ASIGNADA,
                    'observaciones_asignacion' => "Recibido de " . ($transferencia->emisor?->name ?? 'Almacén Central'),
                    'asignado_por' => $transferencia->emisor_id ?? $user->id,
                    'confirmado_por_tecnico' => true,
                    'fecha_confirmacion' => now()
                ]);
            }

            // Notificar al emisor del éxito
            Notification::create([
                'user_id' => $transferencia->emisor_id,
                'notifiable_id' => $transferencia->emisor_id,
                'notifiable_type' => 'App\Models\User',
                'type' => 'transferencia_aceptada',
                'title' => 'Entrega Confirmada',
                'message' => "{$user->name} ha recibido y aceptado las herramientas correctamente.",
                'data' => ['transferencia_id' => $transferencia->id],
                'action_url' => '/tabs/mis-herramientas',
                'icon' => 'fas fa-check-double'
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al aceptar entrega: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Rechazar una transferencia.
     */
    public function reject(TransferenciaHerramienta $transferencia)
    {
        $user = Auth::user();

        if ($transferencia->receptor_id !== $user->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $transferencia->update(['estado' => 'rechazada']);

        Notification::create([
            'user_id' => $transferencia->emisor_id,
            'notifiable_id' => $transferencia->emisor_id,
            'notifiable_type' => 'App\Models\User',
            'type' => 'transferencia_rechazada',
            'title' => 'Entrega Rechazada',
            'message' => "{$user->name} no aceptó las herramientas enviadas.",
            'data' => ['transferencia_id' => $transferencia->id],
            'action_url' => '/tabs/mis-herramientas',
            'icon' => 'fas fa-undo'
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Cancelar una transferencia enviada.
     */
    public function cancel(TransferenciaHerramienta $transferencia)
    {
        $user = Auth::user();

        if ($transferencia->emisor_id !== $user->id) {
            return response()->json(['error' => 'No puedes cancelar una entrega que no enviaste.'], 403);
        }

        if ($transferencia->estado === 'pendiente') {
            $transferencia->update(['estado' => 'cancelada']);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Solo se pueden cancelar entregas pendientes.'], 422);
    }
}
