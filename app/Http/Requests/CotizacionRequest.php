<?php

namespace App\Http\Requests;

use App\Enums\EstadoCotizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CotizacionRequest extends FormRequest
{
    /**
     * Si usas Policies puedes delegar aquí; por ahora permitimos.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * Nota: el backend recalcula totales; por eso `subtotal`, `iva` y `total`
     * son "sometimes" (aceptados si llegan, pero no obligatorios).
     */
    public function rules(): array
    {
        return [
            'cliente_id'                 => ['required', 'integer', 'exists:clientes,id'],

            // Productos/Servicios
            'productos'                  => ['required', 'array', 'min:1'],
            'productos.*.id'             => ['required', 'integer', 'min:1'],
            'productos.*.tipo'           => ['required', 'string', Rule::in(['producto', 'servicio'])],
            'productos.*.cantidad'       => ['required', 'integer', 'min:1'],
            'productos.*.precio'         => ['required', 'numeric', 'min:0'],
            'productos.*.descuento'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'productos.*.subtotal'       => ['sometimes', 'numeric', 'min:0'],
            'productos.*.descuento_monto' => ['sometimes', 'numeric', 'min:0'],

            // Campos calculados en backend (aceptados si llegan)
            'subtotal'                   => ['sometimes', 'numeric', 'min:0'],
            'descuento_general'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'iva'                        => ['sometimes', 'numeric', 'min:0'],
            'total'                      => ['sometimes', 'numeric', 'min:0'],

            // Estado opcional desde el front (validado contra Enum)
            'estado'                     => ['sometimes', Rule::in(array_map(fn($c) => $c->value, EstadoCotizacion::cases()))],

            // Número de cotización lo genera el servidor; no lo exigimos ni lo prohibimos
            // para no romper flujos existentes. Si quieres prohibirlo, agrega: 'prohibited'
            // 'numero_cotizacion'          => ['prohibited'],

            // Notas
            'notas'                      => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Normaliza datos antes de validar:
     * - Fuerza tipos (int/float)
     * - Quita espacios y pasa a minúsculas el "tipo"
     * - Default de descuento a 0 si viene vacío
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();

        if (isset($input['productos']) && is_array($input['productos'])) {
            $input['productos'] = array_values(array_map(function ($p) {
                // Normaliza estructura básica
                $p = is_array($p) ? $p : (array) $p;

                // Tipo: string, trimmed, lowercase
                if (isset($p['tipo'])) {
                    $p['tipo'] = strtolower(trim((string) $p['tipo']));
                }

                // Cantidad: int
                if (isset($p['cantidad'])) {
                    // Si viene vacío o null, déjalo como 0 para que falle la regla min:1
                    $p['cantidad'] = (int) $p['cantidad'];
                }

                // Precio: float
                if (isset($p['precio'])) {
                    $p['precio'] = is_numeric($p['precio']) ? (float) $p['precio'] : $p['precio'];
                }

                // Descuento: float (default 0 si es null o string vacío)
                if (!isset($p['descuento']) || $p['descuento'] === '' || $p['descuento'] === null) {
                    $p['descuento'] = 0;
                } else {
                    $p['descuento'] = is_numeric($p['descuento']) ? (float) $p['descuento'] : $p['descuento'];
                }

                // Subtotales enviados por el cliente (si vienen)
                if (isset($p['subtotal']) && is_numeric($p['subtotal'])) {
                    $p['subtotal'] = (float) $p['subtotal'];
                }
                if (isset($p['descuento_monto']) && is_numeric($p['descuento_monto'])) {
                    $p['descuento_monto'] = (float) $p['descuento_monto'];
                }

                return $p;
            }, $input['productos']));
        }

        // descuento_general: float o null
        if ($this->has('descuento_general')) {
            $dg = $this->input('descuento_general');
            $input['descuento_general'] = ($dg === '' || $dg === null) ? null : (is_numeric($dg) ? (float) $dg : $dg);
        }

        // Totales si vienen del cliente (serán recalculados en el backend)
        foreach (['subtotal', 'iva', 'total'] as $k) {
            if (isset($input[$k]) && is_numeric($input[$k])) {
                $input[$k] = (float) $input[$k];
            }
        }

        $this->replace($input);
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debes seleccionar un cliente.',
            'cliente_id.exists'   => 'El cliente seleccionado no existe.',

            'productos.required'  => 'Debes agregar al menos un producto o servicio.',
            'productos.array'     => 'Los productos deben enviarse como un arreglo.',
            'productos.min'       => 'Debes agregar al menos un ítem.',

            'productos.*.id.required'   => 'El ID del ítem es obligatorio.',
            'productos.*.id.integer'    => 'El ID del ítem debe ser un número entero.',
            'productos.*.id.min'        => 'El ID del ítem debe ser válido.',

            'productos.*.tipo.required' => 'El tipo del ítem es obligatorio.',
            'productos.*.tipo.in'       => 'El tipo del ítem debe ser "producto" o "servicio".',

            'productos.*.cantidad.required' => 'La cantidad es obligatoria.',
            'productos.*.cantidad.integer'  => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min'      => 'La cantidad debe ser al menos 1.',

            'productos.*.precio.required'   => 'El precio es obligatorio.',
            'productos.*.precio.numeric'    => 'El precio debe ser numérico.',
            'productos.*.precio.min'        => 'El precio no puede ser negativo.',

            'productos.*.descuento.numeric' => 'El descuento debe ser numérico.',
            'productos.*.descuento.min'     => 'El descuento no puede ser negativo.',
            'productos.*.descuento.max'     => 'El descuento no puede exceder el 100%.',

            'descuento_general.numeric'     => 'El descuento general debe ser numérico.',
            'descuento_general.min'         => 'El descuento general no puede ser negativo.',
            'descuento_general.max'         => 'El descuento general no puede exceder el 100%.',

            'subtotal.numeric'              => 'El subtotal debe ser numérico.',
            'subtotal.min'                  => 'El subtotal no puede ser negativo.',
            'iva.numeric'                   => 'El IVA debe ser numérico.',
            'iva.min'                       => 'El IVA no puede ser negativo.',
            'total.numeric'                 => 'El total debe ser numérico.',
            'total.min'                     => 'El total no puede ser negativo.',

            'estado.in'                     => 'El estado proporcionado no es válido.',
            'notas.max'                     => 'Las notas no pueden exceder 2000 caracteres.',
        ];
    }

    /**
     * Nombres de atributos para errores más legibles.
     */
    public function attributes(): array
    {
        return [
            'cliente_id'                 => 'cliente',
            'productos'                  => 'ítems',
            'productos.*.id'             => 'ID del ítem',
            'productos.*.tipo'           => 'tipo de ítem',
            'productos.*.cantidad'       => 'cantidad',
            'productos.*.precio'         => 'precio',
            'productos.*.descuento'      => 'descuento del ítem',
            'productos.*.subtotal'       => 'subtotal del ítem',
            'productos.*.descuento_monto' => 'monto de descuento del ítem',
            'descuento_general'          => 'descuento general',
            'subtotal'                   => 'subtotal',
            'iva'                        => 'IVA',
            'total'                      => 'total',
            'estado'                     => 'estado',
            'notas'                      => 'notas',
        ];
    }
}
