<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasTrigramSearch
 * 
 * Proporciona funcionalidad de búsqueda full-text optimizada para PostgreSQL
 * utilizando índices Trigram (pg_trgm).
 */
trait HasTrigramSearch
{
    /**
     * Scope para realizar búsquedas optimizadas.
     *
     * @param Builder $query
     * @param string|null $term Término de búsqueda
     * @param array $columns Columnas donde buscar (si está vacío usa $searchable o todas)
     * @return Builder
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns = []): Builder
    {
        if (empty($term)) {
            return $query;
        }

        // Obtener columnas de búsqueda: argumento > propiedad $searchable > exception/fallback
        if (empty($columns)) {
            $columns = property_exists($this, 'searchable') ? $this->searchable : [];
        }

        if (empty($columns)) {
            return $query; // O lanzar excepción si se prefiere
        }

        // Normalizar término para búsqueda insensible
        $term = trim($term);
        // Escapar caracteres especiales de LIKE (% y _)
        $escapedTerm = str_replace(['%', '_'], ['\%', '\_'], $term);
        // El patrón para trigram suele ser %term% pero pg_trgm optimiza ILIKE '%term%'
        $searchPattern = "%{$escapedTerm}%";

        $driver = $query->getConnection()->getDriverName();

        return $query->where(function (Builder $subQuery) use ($columns, $searchPattern, $driver) {
            foreach ($columns as $column) {
                // PostgreSQL: ILIKE sin unaccent() para no depender de CREATE EXTENSION unaccent
                // (si falta la extensión, la API respondía 500). MySQL/SQLite: LIKE estándar.
                if ($driver === 'pgsql') {
                    $safe = str_replace('"', '', $column);
                    $subQuery->orWhereRaw("\"{$safe}\"::text ILIKE ?", [$searchPattern]);
                } elseif ($driver === 'sqlite') {
                    $subQuery->orWhere($column, 'like', $searchPattern);
                } else {
                    $subQuery->orWhere($column, 'like', $searchPattern);
                }
            }
        });
    }

    /**
     * Scope para ordenar por similitud (ranking).
     * Útil para mostrar los resultados más relevantes primero.
     */
    public function scopeOrderBySimilarity(Builder $query, string $term, string $column): Builder
    {
        if (empty($term)) {
            return $query;
        }

        // requires extension: pg_trgm
        $term = str_replace("'", "''", trim($term)); // SQL injection basic protection for raw

        return $query->orderByRaw("similarity({$column}, ?) DESC", [$term]);
    }
}
