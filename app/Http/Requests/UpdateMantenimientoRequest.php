<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $carroKilometraje = $this->carro_id ? $this->getCarroKilometraje() : 0;
        $tipos = $this->getTiposServicioValidos();

        return [
            'carro_id' => ['required', 'integer', 'exists:carros,id'],
            'tipo' => ['required', 'string', 'max:100', Rule::in($tipos)],
            'otro_servicio' => [
                'nullable',
                'string',
                'max:100',
                'required_if:tipo,Otro servicio',
                'prohibited_unless:tipo,Otro servicio',
            ],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'proximo_mantenimiento' => [
                'required',
                'date',
                'after:fecha',
                'before_or_equal:' . now()->addYears(5)->format('Y-m-d'),
            ],
            'kilometraje_actual' => [
                'required',
                'integer',
                'min:' . max(0, (int) $carroKilometraje),
                'max:' . ((int) $carroKilometraje + 100000),
            ],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'taller' => ['nullable', 'string', 'max:100'],
            'prioridad' => ['required', Rule::in(['baja', 'media', 'alta', 'critica'])],
            'dias_anticipacion_alerta' => ['required', 'integer', 'min:1', 'max:365'],
            'requiere_aprobacion' => ['boolean'],
            'observaciones_alerta' => ['nullable', 'string', 'max:500'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completado'])],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'carro_id' => $this->carro_id ? (int) $this->carro_id : null,
            'kilometraje_actual' => $this->kilometraje_actual !== null && $this->kilometraje_actual !== ''
                ? (int) $this->kilometraje_actual
                : null,
            'costo' => $this->costo !== null && $this->costo !== '' ? (float) $this->costo : null,
            'dias_anticipacion_alerta' => $this->dias_anticipacion_alerta ? (int) $this->dias_anticipacion_alerta : null,
            'requiere_aprobacion' => $this->boolean('requiere_aprobacion'),
        ]);
    }

    private function getCarroKilometraje(): int
    {
        if (!$this->carro_id) {
            return 0;
        }

        $carro = \App\Models\Carro::find($this->carro_id);

        return $carro ? (int) $carro->kilometraje : 0;
    }

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
            'Otro servicio',
        ];
    }
}
