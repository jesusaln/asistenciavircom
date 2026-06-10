<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

/**
 * Trait WithValidation
 *
 * Proporciona métodos de validación para servicios.
 * Las validaciones críticas se definen como propiedades y no pueden ser comentadas.
 *
 * Uso:
 *   class MiServicio
 *   {
 *       use WithValidation;
 *
 *       protected array $criticalValidationRules = [
 *           'importe_total' => 'required|numeric|min:0.01',
 *           'items' => 'required|array|min:1',
 *       ];
 *
 *       public function crearVenta(array $data): Venta
 *       {
 *           $this->validateCritical($data); // Lanza excepción si falla
 *           // ...
 *       }
 *   }
 */
trait WithValidation
{
    /**
     * Reglas de validación críticas - NO COMMENTAR
     * Estas reglas siempre se aplican en el servicio
     */
    protected array $criticalValidationRules = [];

    /**
     * Mensajes de error personalizados
     */
    protected array $validationMessages = [];

    /**
     * Atributos personalizados para mensajes
     */
    protected array $validationAttributes = [];

    /**
     * Valida datos con reglas críticas
     *
     * @throws ValidationException
     */
    protected function validateCritical(array $data, array $additionalRules = []): array
    {
        $rules = array_merge($this->criticalValidationRules, $additionalRules);

        if (empty($rules)) {
            return $data;
        }

        $validator = Validator::make($data, $rules, $this->validationMessages)
            ->setAttributeNames($this->validationAttributes);

        if ($validator->fails()) {
            $this->logValidationFailure($validator->errors(), $data);
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Valida datos sin lanzar excepción
     * Retorna el validador para inspección
     */
    protected function validateWithRules(array $data, array $rules): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, $rules, $this->validationMessages)
            ->setAttributeNames($this->validationAttributes);
    }

    /**
     * Valida y retorna datos validados o null si fallan
     */
    protected function validateOrNull(array $data, array $rules): ?array
    {
        $validator = Validator::make($data, $rules, $this->validationMessages);

        if ($validator->fails()) {
            return null;
        }

        return $validator->validated();
    }

    /**
     * Valida una colección de items
     */
    protected function validateCollection(array $items, array $itemRules, string $errorField = 'items'): \Illuminate\Support\Collection
    {
        $errors = [];
        $validItems = [];

        foreach ($items as $index => $item) {
            $validator = Validator::make($item, $itemRules, $this->validationMessages);

            if ($validator->fails()) {
                $errors["{$errorField}.{$index}"] = $validator->errors()->all();
            } else {
                $validItems[$index] = $validator->validated();
            }
        }

        if (!empty($errors)) {
            $validationException = new ValidationException(
                Validator::make(['_items_' => $items], ['_items_.' => 'array'])
            );
            $validationException->errors()->merge($errors);
            throw $validationException;
        }

        return Collection::make($validItems);
    }

    /**
     * Log de fallos de validación
     */
    protected function logValidationFailure($errors, array $data): void
    {
        \Log::warning('ServiceValidationFailed', [
            'service' => static::class,
            'user_id' => auth()->id() ?? 'guest',
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function'] ?? 'unknown',
            'errors' => $errors->messages(),
            'data_keys' => array_keys($data),
        ]);
    }

    /**
     * Obtiene las reglas de validación críticas
     */
    public function getCriticalValidationRules(): array
    {
        return $this->criticalValidationRules;
    }

    /**
     * Verifica si hay reglas de validación definidas
     */
    public function hasCriticalValidationRules(): bool
    {
        return !empty($this->criticalValidationRules);
    }
}
