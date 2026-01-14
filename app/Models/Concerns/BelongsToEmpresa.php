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

        static::addGlobalScope('empresa', function (Builder $builder) {
            $empresaId = EmpresaResolver::resolveId();
            if ($empresaId) {
                $table = $builder->getModel()->getTable();
                $builder->where("{$table}.empresa_id", $empresaId);
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->empresa_id)) {
                $empresaId = EmpresaResolver::resolveId();
                if ($empresaId) {
                    $model->empresa_id = $empresaId;
                }
            }
        });
    }

    protected static function hasEmpresaColumn(string $table): bool
    {
        if (!array_key_exists($table, self::$empresaScopeCache)) {
            self::$empresaScopeCache[$table] = Schema::hasColumn($table, 'empresa_id');
        }

        return self::$empresaScopeCache[$table];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
