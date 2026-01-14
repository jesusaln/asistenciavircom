<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMantenimientoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $carroKilometraje = $this->carro_id ? $this->getCarroKilometraje() : 0;

        return [
            'carro_id' => [
                'required',
                'integer',
                'exists:carros,id'
            ],
            'tipo' => [
                'required',
                'string',
                'max:100',
                'in:' . implode(',', $this->getTiposServicioValidos())
            ],
            'otro_servicio' => [
                'nullable',
                'string',
                'max:100',
                'required_if:tipo,Otro servicio',
                'prohibited_unless:tipo,Otro servicio'
            ],
            'fecha' => [
                'required',
                'date',
                'before_or_equal:today',
                'after_or_equal:' . now()->subYears(2)->format('Y-m-d') // No más de 2 años atrás
            ],
            'proximo_mantenimiento' => [
                'required',
                'date',
                'after:fecha',
                'before_or_equal:' . now()->addYears(5)->format('Y-m-d') // Máximo 5 años en el futuro
            ],
            'kilometraje_actual' => [
                'required',
                'integer',
                'min:' . ($carroKilometraje + 1), // Debe ser mayor al kilometraje actual
                'max:' . ($carroKilometraje + 100000) // Máximo 100k km más que el actual
            ],
            'costo' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99', // Máximo 1 millón
                'decimal:0,2'
            ],
            'taller' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[\pL\s\-\.\&\(\)0-9]+$/u' // Solo letras, números, espacios y símbolos comunes
            ],
            'prioridad' => [
                'required',
                'in:baja,media,alta,critica'
            ],
            'dias_anticipacion_alerta' => [
                'required',
                'integer',
                'min:1',
                'max:365'
            ],
            'requiere_aprobacion' => [
                'boolean'
            ],
            'observaciones_alerta' => [
                'nullable',
                'string',
                'max:500'
            ],
            'notas' => [
                'nullable',
                'string',
                'max:1000' // Aumentado para más detalles
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'carro_id.required' => 'Debes seleccionar un vehículo.',
            'carro_id.exists' => 'El vehículo seleccionado no existe.',
            'tipo.required' => 'Debes seleccionar un tipo de servicio.',
            'tipo.in' => 'Tipo de servicio no válido.',
            'otro_servicio.required_if' => 'Debes especificar el tipo de servicio personalizado.',
            'otro_servicio.prohibited_unless' => 'El campo "otro servicio" solo se permite cuando el tipo es "Otro servicio".',
            'fecha.required' => 'La fecha del servicio es requerida.',
            'fecha.before_or_equal' => 'La fecha del servicio no puede ser futura.',
            'fecha.after_or_equal' => 'La fecha del servicio no puede ser anterior a 2 años.',
            'proximo_mantenimiento.required' => 'La fecha del próximo mantenimiento es requerida.',
            'proximo_mantenimiento.after' => 'El próximo mantenimiento debe ser posterior a la fecha del servicio.',
            'proximo_mantenimiento.before_or_equal' => 'El próximo mantenimiento no puede ser posterior a 5 años.',
            'kilometraje_actual.required' => 'El kilometraje actual es requerido.',
            'kilometraje_actual.min' => 'El kilometraje debe ser mayor al kilometraje actual del vehículo.',
            'kilometraje_actual.max' => 'El kilometraje parece demasiado alto. Verifica los datos.',
            'prioridad.required' => 'Debes seleccionar una prioridad.',
            'prioridad.in' => 'La prioridad debe ser: baja, media, alta o crítica.',
            'dias_anticipacion_alerta.required' => 'Los días de anticipación son requeridos.',
            'dias_anticipacion_alerta.min' => 'Debe haber al menos 1 día de anticipación.',
            'dias_anticipacion_alerta.max' => 'No pueden haber más de 365 días de anticipación.',
            'costo.required' => 'El costo del servicio es requerido.',
            'costo.numeric' => 'El costo debe ser un número válido.',
            'costo.min' => 'El costo no puede ser negativo.',
            'costo.max' => 'El costo es demasiado alto. Verifica los datos.',
            'taller.regex' => 'El nombre del taller contiene caracteres no válidos.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'carro_id' => 'vehículo',
            'tipo' => 'tipo de servicio',
            'otro_servicio' => 'otro servicio',
            'fecha' => 'fecha del servicio',
            'proximo_mantenimiento' => 'próximo mantenimiento',
            'kilometraje_actual' => 'kilometraje actual',
            'costo' => 'costo',
            'taller' => 'taller',
            'prioridad' => 'prioridad',
            'dias_anticipacion_alerta' => 'días de anticipación',
            'requiere_aprobacion' => 'requiere aprobación',
            'observaciones_alerta' => 'observaciones de alerta',
            'notas' => 'notas',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir campos booleanos y numéricos
        $this->merge([
            'carro_id' => $this->carro_id ? (int) $this->carro_id : null,
            'kilometraje_actual' => $this->kilometraje_actual ? (int) $this->kilometraje_actual : null,
            'costo' => $this->costo ? (float) $this->costo : null,
            'dias_anticipacion_alerta' => $this->dias_anticipacion_alerta ? (int) $this->dias_anticipacion_alerta : null,
            'requiere_aprobacion' => $this->boolean('requiere_aprobacion'),
        ]);
    }

    /**
     * Get the current kilometraje of the selected vehicle.
     */
    private function getCarroKilometraje(): int
    {
        if (!$this->carro_id) {
            return 0;
        }

        $carro = \App\Models\Carro::find($this->carro_id);
        return $carro ? $carro->kilometraje : 0;
    }

    /**
     * Get valid service types from configuration
     */
    private function getTiposServicioValidos(): array
    {
        return [
            'Cambio de aceite',
            'Revisión periódica',
            'Servicio de frenos',
            'Servicio de llantas',
            'Servicio de batería',
            'Servicio de motor',
            'Revisión de luces',
            'Alineación y balanceo',
            'Cambio de filtros',
            'Revisión de transmisión',
            'Otro servicio'
        ];
    }


    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validación personalizada: días de anticipación vs fecha del próximo mantenimiento
            if ($this->tipo && $this->dias_anticipacion_alerta && $this->proximo_mantenimiento) {
                $fechaProximo = \Carbon\Carbon::parse($this->proximo_mantenimiento);
                $fechaAlerta = $fechaProximo->copy()->subDays($this->dias_anticipacion_alerta);
                $diasHastaProximo = \Carbon\Carbon::today()->diffInDays($fechaProximo, false);

                // Si los días de anticipación son mayores que los días hasta el próximo mantenimiento
                if ($this->dias_anticipacion_alerta >= $diasHastaProximo && $diasHastaProximo > 0) {
                    $validator->errors()->add('dias_anticipacion_alerta', 'Los días de anticipación no pueden ser mayores o iguales a los días restantes hasta el próximo mantenimiento.');
                }
            }

            // Validación personalizada: costo sugerido vs tipo de servicio
            if ($this->tipo && $this->costo !== null) {
                $costoSugerido = $this->getCostoSugeridoPorTipo($this->tipo);

                // ⚠️ ALERTA: Si el costo es 0, advertir
                if ($this->costo == 0) {
                    $validator->errors()->add('costo', '⚠️ ADVERTENCIA: El costo es $0. ¿Estás seguro de que el servicio fue gratuito?');
                }
                // ⚠️ ALERTA: Si el costo es mucho menor al sugerido, advertir
                elseif ($costoSugerido > 0 && $this->costo < ($costoSugerido * 0.3)) {
                    $validator->errors()->add('costo', "⚠️ ADVERTENCIA: El costo (\${$this->costo}) es significativamente menor al sugerido (\${$costoSugerido}). Verifica que sea correcto.");
                }
                // ⚠️ ALERTA: Si el costo es mucho mayor al sugerido, advertir
                elseif ($costoSugerido > 0 && $this->costo > ($costoSugerido * 3)) {
                    $validator->errors()->add('costo', "⚠️ ADVERTENCIA: El costo (\${$this->costo}) es significativamente mayor al sugerido (\${$costoSugerido}). Verifica que sea correcto.");
                }
            }
        });
    }

    /**
     * Get suggested cost by service type
     */
    private function getCostoSugeridoPorTipo(string $tipo): float
    {
        return match ($tipo) {
            'Cambio de aceite' => 800.00,
            'Revisión periódica' => 1200.00,
            'Servicio de frenos' => 2500.00,
            'Servicio de llantas' => 600.00,
            'Servicio de batería' => 1800.00,
            'Servicio de motor' => 3500.00,
            'Revisión de luces' => 300.00,
            'Alineación y balanceo' => 800.00,
            'Cambio de filtros' => 400.00,
            'Revisión de transmisión' => 2000.00,
            'Otro servicio' => 0.00,
            default => 0.00,
        };
    }
}
