<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

trait BelongsToEmpresa
{
    protected static array $empresaScopeCache = [];

    protected static function bootBelongsToEmpresa(): void
    {
        static::creating(function (Model $model) {
            $resolvedId = EmpresaResolver::resolveId();

            // Si es un Team, intentar resolver la empresa del usuario propietario
            if (empty($resolvedId) && ($model instanceof \App\Models\Team || get_class($model) === 'App\Models\Team')) {
                if (!empty($model->user_id)) {
                    $resolvedId = \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $model->user_id)
                        ->value('empresa_id');
                }
            }

            if (!$resolvedId) return;
            if (empty($model->empresa_id)) {
                $model->empresa_id = $resolvedId;
            } elseif ($resolvedId && (int) $model->empresa_id !== (int) $resolvedId) {
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
        });

        static::updating(function (Model $model) {
            if ($model->isDirty('empresa_id')) {
                $originalId = $model->getOriginal('empresa_id');
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
        });

        static::addGlobalScope('empresa', function (Builder $builder) {
            $table = $builder->getModel()->getTable();
            
            if (!self::hasEmpresaColumn($table)) {
                return;
            }

            if (EmpresaResolver::isSuperAdmin()) {
                return;
            }

            $empresaId = EmpresaResolver::resolveId();
            
            if ($empresaId) {
                $builder->where("{$table}.empresa_id", $empresaId);
            } else {
                if (!app()->runningInConsole()) {
                    $builder->whereRaw('1 = 0'); 
                }
            }
        });
    }

    protected static function hasEmpresaColumn(string $table): bool
    {
        if (!array_key_exists($table, self::$empresaScopeCache)) {
            try {
                self::$empresaScopeCache[$table] = Schema::hasColumn($table, 'empresa_id');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return self::$empresaScopeCache[$table];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
