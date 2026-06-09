<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Scopes\PreventSelectStarScope;

/**
 * Trait PreventsSelectStar
 *
 * Previene el uso de SELECT * en queries emitiendo warnings/logs.
 * Úsalo en modelos que contengan columnas pesadas (blobs, firmas, etc.)
 *
 * @usage:
 *   use PreventsSelectStar;
 *
 *   // En el modelo, agregar:
 *   protected $preventSelectStar = true;
 */
trait PreventsSelectStar
{
    /**
     * NOTA: Este trait asume que el modelo puede definir las siguientes propiedades:
     * protected array $heavyColumns = [];
     * protected bool $preventSelectStar = false;
     * protected string $modelNameForLog = '';
     * 
     * Se eliminan aquí para evitar conflictos de composición "definition differs".
     */

    /**
     * Boot el trait - registrar listeners de eventos
     */
    public static function bootPreventsSelectStar(): void
    {
        // Solo activar si el modelo tiene la propiedad $preventSelectStar en true
        if (static::getPreventSelectStarProperty()) {
            static::addGlobalScope(new PreventSelectStarScope);
        }
    }

    /**
     * Obtener el valor de la propiedad $preventSelectStar
     */
    protected static function getPreventSelectStarProperty(): bool
    {
        $instance = new static;
        return $instance->preventSelectStar ?? false;
    }

    /**
     * Obtener columnas pesadas definidas en el modelo
     */
    public function getHeavyColumns(): array
    {
        return $this->heavyColumns ?? [];
    }

    /**
     * Obtener nombre del modelo para logs
     */
    public function getModelNameForLog(): string
    {
        return $this->modelNameForLog ?? static::class;
    }

    /**
     * Verificar si una query usa SELECT *
     */
    public static function isSelectingStar(string $query): bool
    {
        // Normalizar: quitar espacios, lowercase
        $normalized = strtolower(preg_replace('/\s+/', ' ', trim($query)));

        // Patrones que indican SELECT *
        $starPatterns = [
            '/select\s+\*\s+from/i',
            '/select\s+\*\s+,/i',  // SELECT *, otra_columa
        ];

        foreach ($starPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Loguear warning con contexto de la query
     */
    public static function logSelectStarWarning(string $query, array $backtrace = []): void
    {
        $instance = new static;
        $modelName = $instance->getModelNameForLog();
        $heavyColumns = $instance->getHeavyColumns();

        $warning = "[SELECT * DETECTED] Modelo: {$modelName}\n";
        $warning .= "Query: {$query}\n";

        if (!empty($heavyColumns)) {
            $warning .= "Columnas pesadas detectadas: " . implode(', ', $heavyColumns) . "\n";
            $warning .= "Recomendación: Especifica las columnas necesarias en el select()\n";
        }

        // Loguear con stack trace reducido
        Log::channel('daily')->warning($warning, [
            'model' => $modelName,
            'heavy_columns' => $heavyColumns,
            'caller' => $backtrace[1] ?? 'unknown',
        ]);
    }
}
