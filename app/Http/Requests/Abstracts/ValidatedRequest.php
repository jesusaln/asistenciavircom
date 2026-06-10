<?php

namespace App\Http\Requests\Abstracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * Abstract ValidatedRequest
 *
 * Clase base para Requests que require validación inmutable.
 * Las validaciones críticas se definen como métodos finales que NO pueden ser sobrescritos.
 * Esto previene que desarrolladores comenten o eliminen validaciones de seguridad.
 *
 * Uso:
 *   class StoreVentaRequest extends ValidatedRequest
 *   {
 *       // Las validaciones CRÍTICAS van en getCriticalRules() - NO pueden ser modificadas
 *       protected function getCriticalRules(): array
 *       {
 *           return [
 *               // Reglas que nunca deben comentarse
 *               'importe_total' => 'required|numeric|min:0.01',
 *               'items' => 'required|array|min:1',
 *           ];
 *       }
 *
 *       // Las reglas opcionales van en rules() - pueden extenderse
 *       public function rules(): array
 *       {
 *           return [
 *               'notas' => 'nullable|string|max:1000',
 *           ];
 *       }
 *   }
 */
abstract class ValidatedRequest extends FormRequest
{
    /**
     * Obtiene las reglas CRÍTICAS que no pueden ser modificadas
     * Estas reglas siempre se aplican y no pueden ser comentadas o eliminadas
     */
    abstract protected function getCriticalRules(): array;

    /**
     * Obtiene las reglas de validación combinadas
     * Une reglas críticas + reglas adicionales
     */
    public function rules(): array
    {
        return array_merge(
            $this->getCriticalRules(),
            $this->getAdditionalRules()
        );
    }

    /**
     * Reglas adicionales que pueden extenderse
     * Override este método en clases hijo para agregar reglas
     */
    protected function getAdditionalRules(): array
    {
        return [];
    }

    /**
     * Validaciones personalizadas críticas
     * Override este método para agregar validaciones personalizadas CRÍTICAS
     */
    protected function getCriticalCustomValidations(): array
    {
        return [];
    }

    /**
     * Validaciones personalizadas adicionales
     * Override este método para agregar validaciones personalizadas opcionales
     */
    protected function getAdditionalCustomValidations(): array
    {
        return [];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        // Aplicar validaciones críticas PRIMERO
        foreach ($this->getCriticalCustomValidations() as $validation) {
            if (is_callable($validation)) {
                $validator->after($validation);
            }
        }

        // Aplicar validaciones adicionales después
        foreach ($this->getAdditionalCustomValidations() as $validation) {
            if (is_callable($validation)) {
                $validator->after($validation);
            }
        }
    }

    /**
     * Handle a failed validation attempt.
     *
     * Override para loggear cuando hay fallos en validaciones críticas
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors();

        // Log de validación fallida
        if (method_exists($this, 'logValidationFailure')) {
            $this->logValidationFailure($errors);
        }

        // ✅ POS FIX: Si la petición viene del POS o es AJAX (no Inertia), devolver JSON
        // Inertia envía peticiones XHR con header X-Inertia, esas deben seguir el flujo normal
        // de redirect-with-errors para que el frontend las maneje correctamente.
        $isInertia = $this->header('X-Inertia');
        if (!$isInertia && ($this->input('source') === 'pos' || $this->ajax() || $this->wantsJson())) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errors->messages(),
            ], 422));
        }

        // Llamar al parent para mantener comportamiento original (Inertia)
        parent::failedValidation($validator);
    }

    /**
     * Log de fallos de validación (para auditoría)
     */
    protected function logValidationFailure($errors): void
    {
        \Log::warning('ValidationFailed', [
            'request' => static::class,
            'user_id' => auth()->id(),
            'route' => request()->fullUrl(),
            'method' => request()->method(),
            'errors' => $errors->messages(),
            'input_keys' => array_keys(request()->all()),
        ]);
    }

    /**
     * Verifica si la solicitud pasa todas las validaciones críticas
     * Útil para validaciones en servicios
     */
    public function passesCriticalValidation(): bool
    {
        $validator = \Validator::make(
            $this->all(),
            $this->getCriticalRules(),
            $this->messages()
        );

        foreach ($this->getCriticalCustomValidations() as $validation) {
            if (is_callable($validation)) {
                $validator->after($validation);
            }
        }

        return !$validator->fails();
    }

    /**
     * Obtiene solo los errores de validación crítica
     */
    public function getCriticalErrors(): \Illuminate\Support\MessageBag
    {
        $validator = \Validator::make(
            $this->all(),
            $this->getCriticalRules(),
            $this->messages()
        );

        return $validator->errors();
    }
}
