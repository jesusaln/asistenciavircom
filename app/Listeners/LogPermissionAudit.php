<?php

namespace App\Listeners;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class LogPermissionAudit
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            $usuario = Auth::check() ? (Auth::user()->name ?? Auth::user()->email) : 'Sistema';
            
            $model = $event->model ?? null;
            $modelInfo = $model ? class_basename($model) . " #" . $model->getKey() : 'Modelo Desconocido';
            if ($model) {
                if (isset($model->name)) {
                    $modelInfo .= " (" . $model->name . ")";
                } elseif (isset($model->email)) {
                    $modelInfo .= " (" . $model->email . ")";
                }
            }

            $actividad = 'Modificación de permisos/roles en ' . $modelInfo;

            if ($event instanceof RoleAttachedEvent) {
                $actividad = "Rol asignado a {$modelInfo}: " . $this->formatItems($event->rolesOrIds);
            } elseif ($event instanceof RoleDetachedEvent) {
                $actividad = "Rol removido de {$modelInfo}: " . $this->formatItems($event->rolesOrIds);
            } elseif ($event instanceof PermissionAttachedEvent) {
                $actividad = "Permiso asignado a {$modelInfo}: " . $this->formatItems($event->permissionsOrIds);
            } elseif ($event instanceof PermissionDetachedEvent) {
                $actividad = "Permiso removido de {$modelInfo}: " . $this->formatItems($event->permissionsOrIds);
            }

            Auditoria::create([
                'usuario' => $usuario,
                'actividad' => $actividad
            ]);
        } catch (\Exception $e) {
            Log::error('Error al registrar auditoría de Spatie Permission', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function formatItems($items): string
    {
        if (is_array($items) || $items instanceof \Illuminate\Support\Collection) {
            $result = [];
            foreach ($items as $item) {
                if (is_object($item) && isset($item->name)) {
                    $result[] = $item->name;
                } elseif (is_scalar($item)) {
                    $result[] = (string) $item;
                }
            }
            return implode(', ', $result);
        }

        if (is_object($items) && isset($items->name)) {
            return $items->name;
        }

        return json_encode($items);
    }
}
