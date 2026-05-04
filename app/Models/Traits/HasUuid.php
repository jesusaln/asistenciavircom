<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Trait HasUuid
 *
 * Proporciona funcionalidad para validación y generación de UUIDs.
 * Garantiza que los UUIDs sean válidos y únicos.
 *
 * Uso:
 *   class Cliente extends Model
 *   {
 *       use HasUuid;
 *
 *       protected $uuidColumn = 'uuid';
 *       protected $uuidVersion = 4; // UUID v4 aleatorio
 *   }
 */
trait HasUuid
{
    /**
     * Columna UUID (por defecto: 'uuid')
     */
    protected string $uuidColumn = 'uuid';

    /**
     * Versión de UUID a generar
     * 4 = Aleatorio (recomendado)
     * 5 = Basado en namespace + nombre
     */
    protected int $uuidVersion = 4;

    /**
     * Namespace para UUID v5
     */
    protected ?string $uuidNamespace = null;

    /**
     * Boot the trait
     */
    public static function bootHasUuid(): void
    {
        // Generar y validar UUID al guardar
        static::saving(function (Model $model) {
            // Generar UUID si no existe
            if (empty($model->{$model->uuidColumn})) {
                $model->{$model->uuidColumn} = $model->generateUuid();
            }

            // Validar UUID
            $model->validateUuid();
        });
    }

    /**
     * Genera un UUID válido
     */
    public function generateUuid(): string
    {
        return match ($this->uuidVersion) {
            4 => (string) Str::uuid(),
            5 => $this->generateUuidV5(),
            default => (string) Str::uuid(),
        };
    }

    /**
     * Genera UUID v5 basado en namespace + nombre
     */
    protected function generateUuidV5(): string
    {
        $namespace = $this->uuidNamespace ?? config('app.url', 'http://localhost');
        $name = $this->getUuidV5Name();

        return (string) Str::uuid5($namespace, $name);
    }

    /**
     * Obtiene el nombre para UUID v5
     */
    protected function getUuidV5Name(): string
    {
        return static::class . ':' . ($this->getKey() ?? 'new');
    }

    /**
     * Valida que el UUID sea correcto
     *
     * @throws ValidationException
     */
    public function validateUuid(): void
    {
        $uuid = $this->{$this->uuidColumn};

        // Si está vacío y no es nullable, lanzar error
        if (empty($uuid)) {
            if ($this->isUuidRequired()) {
                throw ValidationException::withMessages([
                    $this->uuidColumn => 'El UUID es requerido.',
                ]);
            }
            return;
        }

        // Validar formato UUID
        if (!$this->isValidUuid($uuid)) {
            throw ValidationException::withMessages([
                $this->uuidColumn => 'El formato del UUID es inválido.',
            ]);
        }
    }

    /**
     * Verifica si el UUID es válido (formato)
     */
    public function isValidUuid(string $uuid): bool
    {
        // UUID v4: 8-4-4-4-12 hex digits
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return (bool) preg_match($pattern, $uuid);
    }

    /**
     * Verifica si el UUID es único
     */
    public function isUniqueUuid(): bool
    {
        $uuid = $this->{$this->uuidColumn};

        if (empty($uuid)) {
            return false;
        }

        $query = static::where($this->uuidColumn, $uuid);

        // Excluir el modelo actual si ya existe
        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return !$query->exists();
    }

    /**
     * Determina si el UUID es requerido
     */
    protected function isUuidRequired(): bool
    {
        // Override en el modelo si el UUID no es requerido
        return true;
    }

    /**
     * Regenera el UUID (ÚNICAMENTE para desarrollo)
     */
    public function regenerateUuid(bool $force = false): string
    {
        if (!$force && app()->environment('production')) {
            throw new \RuntimeException('No se puede regenerar UUID en producción.');
        }

        $this->{$this->uuidColumn} = $this->generateUuid();
        $this->save();

        return $this->{$this->uuidColumn};
    }

    /**
     * Obtiene el UUID
     */
    public function getUuid(): ?string
    {
        return $this->{$this->uuidColumn} ?? null;
    }

    /**
     * Scope para buscar por UUID
     */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->uuidColumn, $uuid);
    }

    /**
     * Scope para buscar por UUID (retorna null si es inválido)
     */
    public function scopeFindByUuid($query, string $uuid)
    {
        if (!$this->isValidUuid($uuid)) {
            return $query->whereRaw('1 = 0'); // Retornar vacío
        }

        return $query->where($this->uuidColumn, $uuid);
    }

    /**
     * Buscar modelo por UUID
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::findByUuid($uuid)->first();
    }

    /**
     * Buscar modelo por UUID o fallar
     */
    public static function findByUuidOrFail(string $uuid): self
    {
        return static::findByUuid($uuid)->firstOrFail();
    }

    /**
     * Obtiene el nombre de la columna UUID
     */
    public function getUuidColumnName(): string
    {
        return $this->uuidColumn;
    }
}
