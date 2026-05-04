<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMantenimientoRequest extends FormRequest
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
            ],
            'proximo_mantenimiento' => [
                'required',
                'date',
                'after:fecha',
            ],
            'kilometraje_actual' => [
                'required',
                'integer',
            ],
            'costo' => [
                'required',
                'numeric',
                'min:0',
            ],
            'taller' => [
                'nullable',
                'string',
                'max:100',
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
                'max:1000'
            ],
        ];
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

    private function getCarroKilometraje(): int
    {
        if (!$this->carro_id) {
            return 0;
        }

        $carro = \App\Models\Carro::find($this->carro_id);
        return $carro ? $carro->kilometraje : 0;
    }
}
