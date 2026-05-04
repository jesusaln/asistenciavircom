<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Trait SafeFillable
 *
 * Proporciona validación automática de protección contra Mass Assignment.
 * Los modelos que usen este trait deberán tener $fillable explícito,
 * de lo contrario lanzarán una excepción en entorno de desarrollo.
 *
 * Uso:
 *   use SafeFillable;
 *
 *   class MiModelo extends Model
 *   {
 *       use SafeFillable;
 *
 *       protected $fillable = ['campo1', 'campo2'];
 *   }
 */
trait SafeFillable
{
    /**
     * Boot the trait
     */
    public static function bootSafeFillable(): void
    {
        // Validar en el evento de inicialización del modelo
        static::initializing(function (Model $model) {
            $model->validateMassAssignmentProtection();
        });
    }

    /**
     * Valida que el modelo tenga protección contra Mass Assignment
     *
     * @throws \RuntimeException si $fillable no está definido
     */
    public function validateMassAssignmentProtection(): void
    {
        // Solo validar en entorno de desarrollo o testing
        if (!App::isLocal() && !App::runningUnitTests()) {
            return;
        }

        $class = static::class;

        // Verificar si tiene $fillable explícito
        $hasFillable = property_exists($class, 'fillable')
            && is_array($this->fillable)
            && !empty($this->fillable);

        // Verificar si tiene $guarded explícito (y no es un array vacío)
        $hasGuarded = property_exists($class, 'guarded')
            && is_array($this->guarded)
            && !empty($this->guarded);

        // Verificar si tiene unguuard() en algún lugar
        $hasUnguarded = false;
        try {
            $reflection = new \ReflectionClass($class);
            $hasUnguarded = $reflection->hasMethod('unguard')
                || (property_exists($class, 'unguarded') && $this->unguarded);
        } catch (\Throwable $e) {
            // Ignorar errores de reflexión
        }

        if (!$hasFillable && !$hasGuarded && !$hasUnguarded) {
            $message = "⚠️ SECURITY WARNING: Model [{$class}] does not have \$fillable or \$guarded defined. " .
                "This model is vulnerable to Mass Assignment attacks. " .
                "Add 'protected \$fillable = [...]' to this model.";

            if (App::isLocal()) {
                throw new \RuntimeException($message);
            }

            Log::warning('MassAssignmentVulnerability', [
                'model' => $class,
                'message' => $message,
                'suggested_fillable' => $this->getSuggestedFillableFields(),
            ]);
        }

        // Verificar si está desguarded (peligroso)
        if ($hasUnguarded) {
            $message = "⚠️ SECURITY WARNING: Model [{$class}] is using Model::unguard(). " .
                "This disables mass assignment protection. Consider using 'createQuietly' or 'withoutSyncingToModel'.";

            if (App::isLocal()) {
                Log::warning('ModelUnguarded', [
                    'model' => $class,
                    'message' => $message,
                ]);
            }
        }
    }

    /**
     * Obtiene los nombres de columnas de la tabla que podrían ser fillable
     *
     * @return array
     */
    public function getSuggestedFillableFields(): array
    {
        try {
            return $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->getTable());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Verifica si el modelo está protegido contra Mass Assignment
     *
     * @return bool
     */
    public function isMassAssignmentProtected(): bool
    {
        $class = static::class;

        $hasFillable = property_exists($class, 'fillable')
            && is_array($this->fillable)
            && !empty($this->fillable);

        $hasGuarded = property_exists($class, 'guarded')
            && is_array($this->guarded)
            && !empty($this->guarded);

        return $hasFillable || $hasGuarded;
    }

    /**
     * Obtiene el estado de protección del modelo
     *
     * @return array
     */
    public function getMassAssignmentStatus(): array
    {
        $class = static::class;

        return [
            'model' => $class,
            'has_fillable' => property_exists($class, 'fillable'),
            'fillable_count' => property_exists($class, 'fillable') ? count($this->fillable) : 0,
            'has_guarded' => property_exists($class, 'guarded'),
            'guarded_count' => property_exists($class, 'guarded') ? count($this->guarded) : 0,
            'is_protected' => $this->isMassAssignmentProtected(),
            'suggested_fillable' => $this->getSuggestedFillableFields(),
        ];
    }
}
