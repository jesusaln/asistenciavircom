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
                    // Use unaccent for accent-insensitive search if PostgreSQL
                    $subQuery->orWhereRaw("unaccent(\"{$safe}\"::text) ILIKE unaccent(?)", [$searchPattern]);
                } elseif ($driver === 'sqlite') {
                    $subQuery->orWhere($column, 'like', $searchPattern);
                } else {
                    $subQuery->orWhere($column, 'like', $searchPattern);
                }
            }
        });
    }

    /**
     * Scope para ordenar por relevancia inteligente.
     * Prioriza coincidencias al inicio del nombre, luego similitud.
     */
    public function scopeOrderByRelevance(Builder $query, string $term, string $column = 'nombre'): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $driver = $query->getConnection()->getDriverName();
        $term = trim($term);
        $escapedTerm = str_replace("'", "''", $term);

        if ($driver === 'pgsql') {
            // PostgreSQL: 
            // 1. Prioridad: Empieza con el término (unaccented)
            // 2. Prioridad: Similaridad trigram
            return $query->orderByRaw("
                CASE 
                    WHEN unaccent(\"{$column}\"::text) ILIKE unaccent(?) THEN 0 
                    ELSE 1 
                END ASC,
                similarity(unaccent(\"{$column}\"::text), unaccent(?)) DESC
            ", ["{$escapedTerm}%", $escapedTerm]);
        }

        // Fallback para otros drivers
        return $query->orderByRaw("
            CASE 
                WHEN \"{$column}\" LIKE ? THEN 0 
                ELSE 1 
            END ASC
        ", ["{$term}%"]);
    }
}
