<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudMaterial;
use App\Models\SolicitudMaterialItem;
use App\Http\Controllers\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SolicitudMaterialController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole(['super-admin', 'admin']);

        $query = SolicitudMaterial::with(['user', 'items.producto'])
            ->where('empresa_id', $user->empresa_id)
            ->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $solicitudes = $query->paginate(20);

        return $this->success($solicitudes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'prioridad' => 'required|string',
            'motivo' => 'required|string',
            'fecha_requerida' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required_without:items.*.producto_id|string',
            'items.*.producto_id' => 'nullable|exists:productos,id',
            'items.*.cantidad' => 'required|numeric|min:0.1',
            'items.*.unidad_medida' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                
                // Generar Folio
                $lastSolicitud = SolicitudMaterial::where('empresa_id', $user->empresa_id)
                    ->orderBy('id', 'desc')
                    ->first();
                $nextId = ($lastSolicitud ? $lastSolicitud->id : 0) + 1;
                $folio = 'SOL-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

                $solicitud = SolicitudMaterial::create([
                    'empresa_id' => $user->empresa_id,
                    'user_id' => $user->id,
                    'folio' => $folio,
                    'tipo' => $request->tipo,
                    'prioridad' => $request->prioridad,
                    'estado' => 'Pendiente',
                    'motivo' => $request->motivo,
                    'fecha_requerida' => $request->fecha_requerida,
                ]);

                foreach ($request->items as $itemData) {
                    SolicitudMaterialItem::create([
                        'solicitud_material_id' => $solicitud->id,
                        'producto_id' => $itemData['producto_id'] ?? null,
                        'descripcion' => $itemData['descripcion'] ?? null,
                        'cantidad' => $itemData['cantidad'],
                        'unidad_medida' => $itemData['unidad_medida'] ?? null,
                    ]);
                }

                // Notificar a Compras vía Email
                $admins = \App\Models\User::role(['compras'])
                    ->where('empresa_id', $user->empresa_id)
                    ->get();
                \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\SolicitudMaterialRecibida($solicitud));
                
                // Notificar vía Campana (Bell) del Dashboard
                \App\Models\UserNotification::createSolicitudMaterialNotification($solicitud);
                
                return $this->success($solicitud->load('items.producto'), 'Solicitud enviada correctamente');
            });
        } catch (\Exception $e) {
            Log::error('Error en SolicitudMaterial API: ' . $e->getMessage());
            return $this->error('Error al procesar la solicitud: ' . $e->getMessage(), 400);
        }
    }

    public function show(SolicitudMaterial $solicitud)
    {
        return $this->success($solicitud->load(['user', 'items.producto']));
    }

    public function update(Request $request, SolicitudMaterial $solicitud)
    {
        $user = $request->user();
        $isAdmin = $user->hasRole(['super-admin', 'admin']);

        if (!$isAdmin) {
            return $this->error('No tienes permisos para actualizar solicitudes', 403);
        }

        $request->validate([
            'estado' => 'required|string|in:Pendiente,En Proceso,Entregado,Rechazado',
            'comentarios_admin' => 'nullable|string',
        ]);

        $solicitud->update($request->only('estado', 'comentarios_admin'));

        return $this->success($solicitud, 'Estado actualizado correctamente');
    }
}
