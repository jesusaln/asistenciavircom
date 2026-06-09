<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use App\Support\EmpresaResolver;
use Illuminate\Support\Facades\Log;

class EmpresaContextObserver
{
    /**
     * Handle the Model "creating" event.
     * Prevents tenant leakage by strictly enforcing empresa_id.
     */
    public function creating(Model $model): void
    {
        $resolvedId = EmpresaResolver::resolveId();

        // If there is no resolved empresa_id (e.g. initial setup or system commands), 
        // we allow manual setting or null.
        if (!$resolvedId) {
            return;
        }

        if (empty($model->empresa_id)) {
            $model->empresa_id = $resolvedId;
        } elseif ((int) $model->empresa_id !== (int) $resolvedId) {
            Log::critical("SECURITY ALERT: Tenant ID Mismatch", [
                'model' => get_class($model),
                'attempted_empresa_id' => $model->empresa_id,
                'resolved_empresa_id' => $resolvedId,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'url' => request()->fullUrl(),
            ]);

            throw new \RuntimeException("Tenant Mismatch: Tentativa de cruce de datos entre empresas detectada y bloqueada.");
        }
    }

    /**
     * Handle the Model "updating" event.
     * Hard prevents changing empresa_id once set.
     */
    public function updating(Model $model): void
    {
        if ($model->isDirty('empresa_id')) {
            $originalId = $model->getOriginal('empresa_id');

            // Allow setting it if it was null (rare, but possible if created outside context)
            if (!empty($originalId) && (int) $model->empresa_id !== (int) $originalId) {
                Log::error("SECURITY ALERT: Attempt to change empresa_id", [
                    'model' => get_class($model),
                    'model_id' => $model->getKey(),
                    'from' => $originalId,
                    'to' => $model->empresa_id,
                    'user_id' => auth()->id()
                ]);

                throw new \RuntimeException("Inmutabilidad Violada: El empresa_id no puede ser modificado después de la creación.");
            }
        }
    }
}
