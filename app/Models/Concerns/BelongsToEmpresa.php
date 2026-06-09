<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Support\EmpresaResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

trait BelongsToEmpresa
{
    protected static array $empresaScopeCache = [];

    protected static function bootBelongsToEmpresa(): void
    {
        $table = (new static())->getTable();
        if (!self::hasEmpresaColumn($table)) {
            return;
        }

        // Registrar observador de seguridad para multi-tenancy (Fix #92)
        static::observe(\App\Observers\EmpresaContextObserver::class);

        static::addGlobalScope('empresa', function (Builder $builder) {
            $empresaId = EmpresaResolver::resolveId();
            
            // CRITICAL SECURITY FIX: If no empresa_id is resolved, we must ensure the query 
            // returns no results instead of returning ALL records from ALL companies.
            if ($empresaId) {
                $table = $builder->getModel()->getTable();
                $builder->where("{$table}.empresa_id", $empresaId);
            } else {
                // Force an impossible condition if not running in a context where empresa can be resolved
                // but allow it for CLI/Migrations if needed (though global scope usually isn't active there)
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
                // Si no hay conexión a BD (ej. CI/CD build), regresamos false para no romper
                // el boot del modelo.
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
