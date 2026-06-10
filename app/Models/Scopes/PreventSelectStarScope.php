<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * PreventSelectStarScope
 *
 * Global scope que detecta y registra queries SELECT * en un modelo específico.
 * Requiere que el modelo use el trait PreventsSelectStar.
 */
class PreventSelectStarScope implements Scope
{
    /**
     * Tabla del modelo (para filtrar solo queries relevantes)
     */
    protected ?string $tableName = null;

    /**
     * Aplicar el scope
     */
    public function apply(\Illuminate\Database\Eloquent\Builder $builder, Model $model): void
    {
        if (!$model->getConnection()) {
            return;
        }

        $this->tableName = $model->getTable();

        // Registrar listener único por modelo para evitar duplicados
        $listenerKey = "select_star_{$this->tableName}";

        if (!Event::hasListeners("eloquent.{$listenerKey}: *")) {
            Event::listen(
                function (QueryExecuted $query) use ($model, $listenerKey) {
                    $this->checkQuery($query, $model);
                }
            );
        }
    }

    /**
     * Verificar si la query es SELECT * en la tabla del modelo
     */
    protected function checkQuery(QueryExecuted $query, Model $model): void
    {
        // Solo procesar queries SELECT
        if ($query->sql === null || strtoupper(substr(trim($query->sql), 0, 6)) !== 'SELECT') {
            return;
        }

        // Solo procesar queries de la tabla del modelo
        $queryTable = $this->extractTableFromQuery($query->sql);

        if ($queryTable !== $this->tableName) {
            return;
        }

        // Verificar si es SELECT *
        if ($this->isSelectingStar($query->sql)) {
            $this->logWarning($query, $model);
        }
    }

    /**
     * Extraer nombre de tabla de la query
     */
    protected function extractTableFromQuery(string $sql): string
    {
        // Buscar FROM tabla
        if (preg_match('/from\s+["`]?(\w+)["`]?/i', $sql, $matches)) {
            return strtolower($matches[1]);
        }

        // Buscar JOIN tabla
        if (preg_match('/join\s+["`]?(\w+)["`]?/i', $sql, $matches)) {
            return strtolower($matches[1]);
        }

        return '';
    }

    /**
     * Verificar si es SELECT *
     */
    protected function isSelectingStar(string $sql): bool
    {
        $normalized = preg_replace('/\s+/', ' ', trim($sql));

        // Patrones de SELECT *
        $patterns = [
            '/select\s+\*\s+from/i',
            '/select\s+\*\s+/i',  // SELECT * WHERE...
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loguear warning
     */
    protected function logWarning(QueryExecuted $query, Model $model): void
    {
        $modelName = $model->getModelNameForLog();
        $heavyColumns = $model->getHeavyColumns();

        $context = [
            'model' => $modelName,
            'table' => $this->tableName,
            'query' => $query->sql,
            'bindings' => $query->bindings,
            'connection' => $query->connectionName,
        ];

        if (!empty($heavyColumns)) {
            $context['heavy_columns'] = $heavyColumns;
            $context['warning'] = 'SELECT * detectado - columnas pesadas pueden estar sendo transferidas innecesariamente';
        }

        Log::channel('daily')->warning("[SELECT * DETECTED] {$modelName}", $context);
    }
}
