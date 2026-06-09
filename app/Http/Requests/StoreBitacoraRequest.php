<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreBitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Derivamos valores antes de validar para evitar errores.
     */
    protected function prepareForValidation(): void
    {
        // user_id por defecto = usuario autenticado si no viene
        $userId = $this->input('user_id') ?: Auth::id();

        // Derivar fecha/hora desde inicio_at si faltan
        $inicio = $this->input('inicio_at');
        $fecha  = $this->input('fecha');
        $hora   = $this->input('hora');

        if ($inicio && (!$fecha || !$hora)) {
            $i = Carbon::parse($inicio);
            $fecha = $fecha ?: $i->toDateString(); // YYYY-MM-DD
            $hora  = $hora  ?: $i->format('H:i');  // HH:MM
        }

        // Defaults seguros
        $adjuntos = $this->input('adjuntos', []);
        $esFacturable = $this->boolean('es_facturable'); // true/false

        $this->merge([
            'user_id'       => $userId,
            'fecha'         => $fecha,
            'hora'          => $hora,
            'adjuntos'      => $adjuntos,
            'es_facturable' => $esFacturable,
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id'       => ['required', 'exists:users,id'],
            'asignado_id'   => ['nullable', 'exists:users,id'],
            'cliente_id'    => ['nullable', 'exists:clientes,id'],

            'titulo'        => ['required', 'string', 'max:160'],
            'descripcion'   => ['nullable', 'string'],

            // Ahora ya llega desde prepareForValidation, se mantiene required
            'fecha'         => ['required', 'date'],
            'hora'          => ['nullable', 'date_format:H:i'],

            // Recomendado: que inicio_at sea requerido en el Create
            'inicio_at'     => ['required', 'date'],
            'fin_at'        => ['nullable', 'date', 'after_or_equal:inicio_at'],

            'tipo'          => ['required', 'in:soporte,mantenimiento,instalacion,cotizacion,visita,administrativo,otro'],
            'estado'        => ['required', 'in:pendiente,en_proceso,completado,cancelado'],

            'prioridad'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'ubicacion'     => ['nullable', 'string', 'max:180'],

            'adjuntos'      => ['nullable', 'array'],
            'adjuntos.*'    => ['url'], // ajusta si subirás archivos en vez de URLs

            'es_facturable' => ['boolean'],
            'costo_mxn'     => ['nullable', 'numeric', 'min:0'],
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
