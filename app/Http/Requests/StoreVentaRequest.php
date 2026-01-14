<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Almacen;
use App\Models\Producto;

class StoreVentaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cliente_id' => 'nullable|exists:clientes,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'cita_id' => 'nullable|exists:citas,id',
            'almacen_id' => [
                'required',
                'exists:almacenes,id',
                function ($attribute, $value, $fail) {
                    $almacen = Almacen::find($value);
                    if (!$almacen || $almacen->estado !== 'activo') {
                        $fail('El almacén seleccionado no está activo.');
                    }
                },
            ],
            'metodo_pago' => 'required|string|in:efectivo,tarjeta,transferencia,cheque,credito',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            // ✅ FIX: Permitir ventas solo con servicios (productos es opcional si hay servicios)
            'productos' => 'nullable|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
            'productos.*.series' => 'nullable|array',
            'productos.*.series.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            // Series por componente cuando el producto es un kit con componentes serializados
            'productos.*.componentes_series' => 'nullable|array',
            'productos.*.componentes_series.*' => 'nullable|array',
            'productos.*.componentes_series.*.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            // ✅ FIX: Servicios con validación apropiada
            'servicios' => 'nullable|array',
            'servicios.*.id' => 'required|exists:servicios,id',
            'servicios.*.cantidad' => 'required|integer|min:1',
            'servicios.*.precio' => 'required|numeric|min:0.01',
            'servicios.*.descuento' => 'nullable|numeric|min:0|max:100',
            'descuento_general' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // ✅ FIX: Validar que haya al menos un producto O un servicio
            $this->validateAtLeastOneItem($validator);
            $this->validateSeriesCount($validator);
            $this->validateSeriesUniqueness($validator);
            $this->validateProductosActivos($validator);
            $this->validatePreciosNoMenoresAlCosto($validator);
        });
    }

    /**
     * Validate that at least one product or service is present
     */
    protected function validateAtLeastOneItem($validator)
    {
        $productos = $this->input('productos', []);
        $servicios = $this->input('servicios', []);

        if (empty($productos) && empty($servicios)) {
            $validator->errors()->add('productos', 'Debe incluir al menos un producto o servicio en la venta.');
        }
    }

    /**
     * Validate that selling price is not below purchase cost
     * ✅ REMOVED: This validation was blocking sales below cost.
     * Business decision: Users may sell below cost for promotions, liquidation, etc.
     * The margin information is displayed in the UI for user awareness.
     */
    protected function validatePreciosNoMenoresAlCosto($validator)
    {
        // Validación removida intencionalmente - el usuario puede vender a cualquier precio
        // La información de margen/costo se muestra en la UI para que el usuario tome decisiones informadas
        return;
    }

    /**
     * Validate that series count matches quantity for products that require them
     */
    protected function validateSeriesCount($validator)
    {
        $productos = $this->input('productos', []);

        foreach ($productos as $index => $productoData) {
            $producto = Producto::find($productoData['id']);

            if (!$producto) {
                continue;
            }

            // Productos individuales con series
            if ($producto->requiere_serie) {
                $series = $productoData['series'] ?? [];
                $cantidad = $productoData['cantidad'];

                if (empty($series)) {
                    $validator->errors()->add(
                        "productos.{$index}.series",
                        "El producto '{$producto->nombre}' requiere series pero no se proporcionaron"
                    );
                } elseif (count($series) !== $cantidad) {
                    $validator->errors()->add(
                        "productos.{$index}.series",
                        "El producto '{$producto->nombre}' requiere {$cantidad} serie(s), pero solo se proporcionaron " . count($series)
                    );
                }
            } elseif ($producto->tipo_producto === 'kit') {
                // Kits: validar series por componente serializado
                $componentesSeries = $productoData['componentes_series'] ?? [];
                foreach ($producto->kitItems as $kitItem) {
                    // Solo validar productos, no servicios
                    if (!$kitItem->esProducto()) {
                        continue;
                    }

                    $componente = $kitItem->item;
                    if (!$componente) {
                        continue;
                    }
                    $requiereSeriesComponente = ($componente->requiere_serie ?? false) || ($componente->maneja_series ?? false) || ($componente->expires ?? false);
                    if (!$requiereSeriesComponente) {
                        continue;
                    }

                    $cantidadNecesaria = $kitItem->cantidad * ($productoData['cantidad'] ?? 1);
                    $seriesComponente = $componentesSeries[$componente->id] ?? [];

                    if (empty($seriesComponente)) {
                        // ✅ FIX Error #7: Mensaje de error más específico con ID del componente
                        $validator->errors()->add(
                            "productos.{$index}.componentes_series.{$componente->id}",
                            "⚠️ El componente '{$componente->nombre}' (del kit '{$producto->nombre}') requiere series pero no se proporcionaron. Por favor, selecciona las series necesarias para este componente."
                        );
                    } elseif (count($seriesComponente) !== $cantidadNecesaria) {
                        // ✅ FIX Error #7: Mensaje de error más claro con contexto completo
                        $validator->errors()->add(
                            "productos.{$index}.componentes_series.{$componente->id}",
                            "⚠️ El componente '{$componente->nombre}' (del kit '{$producto->nombre}') requiere {$cantidadNecesaria} serie(s), pero se proporcionaron " . count($seriesComponente) . ". Por favor, verifica tu selección de series."
                        );
                    }
                }
            }
        }
    }

    /**
     * Validate that series are unique across the request and database
     */
    protected function validateSeriesUniqueness($validator)
    {
        $productos = $this->input('productos', []);
        $seriesUsadas = [];

        foreach ($productos as $index => $productoData) {
            $series = $productoData['series'] ?? [];

            foreach ($series as $serieIndex => $numeroSerie) {
                // Check for duplicates in request
                if (in_array($numeroSerie, $seriesUsadas)) {
                    $validator->errors()->add(
                        "productos.{$index}.series.{$serieIndex}",
                        "La serie {$numeroSerie} está duplicada en la solicitud"
                    );
                    continue;
                }

                // Check if series exists in database for another product
                $serieExistente = \App\Models\ProductoSerie::where('numero_serie', $numeroSerie)
                    ->where('producto_id', '!=', $productoData['id'])
                    ->whereNull('deleted_at')
                    ->first();

                if ($serieExistente) {
                    $productoAsociado = Producto::find($serieExistente->producto_id);
                    $validator->errors()->add(
                        "productos.{$index}.series.{$serieIndex}",
                        "La serie {$numeroSerie} ya está registrada para el producto '{$productoAsociado->nombre}'"
                    );
                    continue;
                }

                $seriesUsadas[] = $numeroSerie;
            }
        }
    }

    /**
     * Validate that all products are active
     */
    protected function validateProductosActivos($validator)
    {
        $productos = $this->input('productos', []);

        foreach ($productos as $index => $productoData) {
            $producto = Producto::find($productoData['id']);

            if ($producto && $producto->estado !== 'activo') {
                $validator->errors()->add(
                    "productos.{$index}.id",
                    "El producto '{$producto->nombre}' no está activo"
                );
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'productos.required' => 'Debe incluir al menos un producto',
            'productos.*.id.required' => 'El ID del producto es requerido',
            'productos.*.id.exists' => 'El producto seleccionado no existe',
            'productos.*.cantidad.required' => 'La cantidad es requerida',
            'productos.*.cantidad.min' => 'La cantidad debe ser al menos 1',
            'productos.*.precio.required' => 'El precio es requerido',
            'productos.*.precio.min' => 'El precio no puede ser negativo',
            'productos.*.descuento.max' => 'El descuento no puede ser mayor a 100%',
            'productos.*.series.*.regex' => 'El formato de la serie es inválido. Solo se permiten letras, números, guiones y @',
            'productos.*.series.*.max' => 'La serie no puede tener más de 50 caracteres',
            'almacen_id.required' => 'El almacén es requerido',
            'almacen_id.exists' => 'El almacén seleccionado no existe',
            'metodo_pago.required' => 'El método de pago es requerido',
            'cliente_id.exists' => 'El cliente seleccionado no existe',
        ];
    }
}
