<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipos   = ['soporte', 'mantenimiento', 'instalacion', 'cotizacion', 'visita', 'administrativo', 'otro'];
        $estados = ['pendiente', 'en_proceso', 'completado', 'cancelado'];

        return [
            'titulo'       => ['required', 'string', 'max:150'],
            'user_id'      => ['required', 'integer', 'exists:users,id'],
            'asignado_id'  => ['nullable', 'integer', 'exists:users,id'],
            'cliente_id'   => ['nullable', 'integer', 'exists:clientes,id'],
            'tipo'         => ['required', 'string', Rule::in($tipos)],
            'estado'       => ['required', 'string', Rule::in($estados)],
            'inicio_at'    => ['required', 'date'],
            'fin_at'       => ['nullable', 'date', 'after:inicio_at'],
            'ubicacion'    => ['nullable', 'string', 'max:255'],
            'descripcion'  => ['nullable', 'string', 'max:5000'],
            'prioridad'    => ['nullable', 'string', 'max:50'],
            'adjuntos'     => ['nullable', 'array'],
            'adjuntos.*'   => ['nullable'],
            'es_facturable' => ['nullable', 'boolean'],
            'costo_mxn'    => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id'    => 'empleado',
            'cliente_id' => 'cliente',
            'inicio_at'  => 'inicio',
            'fin_at'     => 'fin',
        ];
    }
}
