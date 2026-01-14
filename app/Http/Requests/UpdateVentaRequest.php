<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Producto;
use App\Enums\EstadoVenta;

class UpdateVentaRequest extends FormRequest
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
            'numero_venta' => 'required|string',
            'fecha' => 'required|date',
            'estado' => 'required|string',
            'descuento_general' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|string',
            'almacen_id' => 'prohibited', // No se permite cambiar el almacén
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
            'productos.*.series' => 'nullable|array',
            'productos.*.series.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            // ✅ FIX: Added missing kit component series validation (same as StoreVentaRequest)
            'productos.*.componentes_series' => 'nullable|array',
            'productos.*.componentes_series.*' => 'nullable|array',
            'productos.*.componentes_series.*.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            'servicios' => 'nullable|array',
            'servicios.*.id' => 'nullable|exists:servicios,id',
            'servicios.*.cantidad' => 'nullable|integer|min:1',
            'servicios.*.precio' => 'nullable|numeric|min:0',
            'servicios.*.descuento' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateVentaNotPaid($validator);
            $this->validateVentaNotCancelled($validator);
            $this->validateSeriesCount($validator);
            $this->validateProductosActivos($validator);
        });
    }

    /**
     * Validate that the venta is not paid
     */
    protected function validateVentaNotPaid($validator)
    {
        $venta = $this->route('venta');
        
        if ($venta && $venta->pagado) {
            $validator->errors()->add('venta', 'No se pueden editar ventas pagadas');
        }
    }

    /**
     * Validate that the venta is not cancelled
     */
    protected function validateVentaNotCancelled($validator)
    {
        $venta = $this->route('venta');
        
        if ($venta && $venta->estado?->value === EstadoVenta::Cancelada->value) {
            $validator->errors()->add('venta', 'No se pueden editar ventas canceladas');
        }
    }

    /**
     * Validate that series count matches quantity for products that require them
     * ✅ FIX: Added kit component series validation (same as StoreVentaRequest)
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
                // ✅ FIX: Kits - validar series por componente serializado (matching StoreVentaRequest)
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
                    
                    $requiereSeriesComponente = ($componente->requiere_serie ?? false) || 
                                                 ($componente->maneja_series ?? false) || 
                                                 ($componente->expires ?? false);
                    if (!$requiereSeriesComponente) {
                        continue;
                    }

                    $cantidadNecesaria = $kitItem->cantidad * ($productoData['cantidad'] ?? 1);
                    $seriesComponente = $componentesSeries[$componente->id] ?? [];

                    if (empty($seriesComponente)) {
                        $validator->errors()->add(
                            "productos.{$index}.componentes_series.{$componente->id}",
                            "⚠️ El componente '{$componente->nombre}' (del kit '{$producto->nombre}') requiere series pero no se proporcionaron."
                        );
                    } elseif (count($seriesComponente) !== $cantidadNecesaria) {
                        $validator->errors()->add(
                            "productos.{$index}.componentes_series.{$componente->id}",
                            "⚠️ El componente '{$componente->nombre}' (del kit '{$producto->nombre}') requiere {$cantidadNecesaria} serie(s), pero se proporcionaron " . count($seriesComponente) . "."
                        );
                    }
                }
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
            'productos.*.series.*.regex' => 'El formato de la serie es inválido',
            'productos.*.series.*.max' => 'La serie no puede tener más de 50 caracteres',
            'almacen_id.prohibited' => 'No se permite cambiar el almacén de una venta',
            'numero_venta.required' => 'El número de venta es requerido',
            'fecha.required' => 'La fecha es requerida',
            'estado.required' => 'El estado es requerido',
            'metodo_pago.required' => 'El método de pago es requerido',
        ];
    }
}
