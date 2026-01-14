<?php

namespace App\Services\Panel;

use App\Models\BitacoraActividad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PanelBitacoraService
{
    /**
     * Obtener tareas pendientes asignadas a un usuario
     */
    public function getTareasPendientes(int $userId): array
    {
        $cacheKey = "panel_bitacora_pendientes_{$userId}";

        return Cache::remember($cacheKey, 120, function () use ($userId) {
            try {
                $tareas = BitacoraActividad::with(['usuario:id,name', 'cliente:id,nombre_razon_social'])
                    ->pendientesParaUsuario($userId)
                    ->limit(10)
                    ->get();

                return [
                    'tareas' => $this->formatTareas($tareas),
                    'total' => $tareas->count(),
                    'en_proceso' => $tareas->where('estado', 'en_proceso')->count(),
                    'pendientes' => $tareas->where('estado', 'pendiente')->count(),
                ];
            } catch (\Exception $e) {
                \Log::error("Error loading bitacora alerts: " . $e->getMessage());
                return [
                    'tareas' => [],
                    'total' => 0,
                    'en_proceso' => 0,
                    'pendientes' => 0,
                ];
            }
        });
    }

    /**
     * Formatear tareas para el Panel
     */
    private function formatTareas($tareas): array
    {
        $now = Carbon::now();

        return $tareas->map(function ($tarea) use ($now) {
            $fecha = Carbon::parse($tarea->fecha);
            $diasRestantes = $now->diffInDays($fecha, false);

            return [
                'id' => $tarea->id,
                'titulo' => $tarea->titulo,
                'descripcion' => $tarea->descripcion ? \Str::limit($tarea->descripcion, 80) : null,
                'tipo' => $tarea->tipo,
                'estado' => $tarea->estado,
                'estado_label' => $this->getEstadoLabel($tarea->estado),
                'prioridad' => $tarea->prioridad ?? 3,
                'prioridad_label' => $this->getPrioridadLabel($tarea->prioridad ?? 3),
                'fecha' => $fecha->format('d/m/Y'),
                'fecha_raw' => $tarea->fecha,
                'dias_restantes' => $diasRestantes,
                'vencida' => $diasRestantes < 0,
                'creador' => $tarea->usuario?->name ?? 'N/A',
                'cliente' => $tarea->cliente?->nombre_razon_social ?? null,
                'ubicacion' => $tarea->ubicacion,
            ];
        })->toArray();
    }

    private function getEstadoLabel(string $estado): string
    {
        return match($estado) {
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Proceso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
            default => ucfirst($estado),
        };
    }

    private function getPrioridadLabel(int $prioridad): string
    {
        return match($prioridad) {
            1 => 'Urgente',
            2 => 'Alta',
            3 => 'Normal',
            4 => 'Baja',
            5 => 'Muy Baja',
            default => 'Normal',
        };
    }

    /**
     * Limpiar cache de un usuario
     */
    public function clearCache(int $userId): void
    {
        Cache::forget("panel_bitacora_pendientes_{$userId}");
    }
}
