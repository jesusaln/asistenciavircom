<?php

namespace App\Services\Panel;

use App\Models\BitacoraActividad;
use App\Support\EmpresaResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PanelBitacoraService
{
    /**
     * Obtener tareas pendientes asignadas a un usuario
     */
    public function getTareasPendientes(int $userId): array
    {
        $eid = EmpresaResolver::resolveId();
        $connection = config('database.default');
        $cacheKey = "panel:{$connection}:bitacora_pendientes:".($eid ?? 'global').":".$userId;

        return Cache::remember($cacheKey, PanelCacheKeys::ttl('bitacora'), function () use ($userId) {
            try {
                $tareasBitacora = BitacoraActividad::with(['usuario:id,name', 'cliente:id,nombre_razon_social'])
                    ->pendientesParaUsuario($userId)
                    ->limit(10)
                    ->get();

                $todosPersonales = \App\Models\Todo::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->limit(10)
                    ->get();

                $unificadas = $this->formatTareasUnificadas($tareasBitacora, $todosPersonales);

                return [
                    'tareas' => $unificadas,
                    'total' => count($unificadas),
                    'en_proceso' => $tareasBitacora->where('estado', 'en_proceso')->count() + $todosPersonales->count(), // Los todos personales cuentan como pendientes/en proceso
                    'pendientes' => $tareasBitacora->where('estado', 'pendiente')->count(),
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
     * Formatear tareas unificadas (Bitácora + Todos)
     */
    private function formatTareasUnificadas($bitacora, $todos): array
    {
        $now = Carbon::now();
        $items = [];

        // Formatear Bitácora
        foreach ($bitacora as $tarea) {
            $fecha = Carbon::parse($tarea->fecha);
            $diasRestantes = $now->diffInDays($fecha, false);
            $items[] = [
                'id' => $tarea->id,
                'titulo' => $tarea->titulo,
                'descripcion' => $tarea->descripcion ? \Str::limit($tarea->descripcion, 80) : null,
                'tipo' => 'bitacora',
                'estado' => $tarea->estado,
                'prioridad' => $tarea->prioridad ?? 3,
                'fecha' => $fecha->format('d/m/Y'),
                'vencida' => $diasRestantes < 0,
                'url' => "/mis-pendientes?open_id=B{$tarea->id}", // Unificado a Mis Pendientes con prefijo B
                'icon' => 'wrench'
            ];
        }

        // Formatear Todos
        foreach ($todos as $todo) {
            $fecha = $todo->due_date ? Carbon::parse($todo->due_date) : null;
            $diasRestantes = $fecha ? $now->diffInDays($fecha, false) : 0;
            $items[] = [
                'id' => $todo->id,
                'titulo' => $todo->title,
                'descripcion' => $todo->description ? \Str::limit($todo->description, 80) : null,
                'tipo' => 'todo',
                'estado' => $todo->status,
                'prioridad' => $todo->priority === 'high' ? 1 : ($todo->priority === 'medium' ? 2 : 3),
                'fecha' => $fecha ? $fecha->format('d/m/Y') : 'Sin fecha',
                'vencida' => $fecha ? $diasRestantes < 0 : false,
                'url' => "/mis-pendientes?open_id={$todo->id}",
                'icon' => 'clipboard-list'
            ];
        }

        // Ordenar por vencimiento y prioridad
        usort($items, function($a, $b) {
            if ($a['vencida'] !== $b['vencida']) return $b['vencida'] <=> $a['vencida'];
            return $a['prioridad'] <=> $b['prioridad'];
        });

        return $items;
    }

    /**
     * Limpiar cache de un usuario
     */
    public function clearCache(int $userId): void
    {
        $eid = EmpresaResolver::resolveId();
        $connection = config('database.default');
        $cacheKey = "panel:{$connection}:bitacora_pendientes:".($eid ?? 'global').":".$userId;
        Cache::forget($cacheKey);
    }
}
