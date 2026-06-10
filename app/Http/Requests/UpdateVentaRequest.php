<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Producto;
use App\Enums\EstadoVenta;
use App\Services\MarginService;

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
        $venta = $this->route('venta');
        
        // Handle case where route model binding is bypassed (e.g. in some tests)
        if (is_string($venta) || is_numeric($venta)) {
            $venta = \App\Models\Venta::find($venta);
        }

        $isPaid = $venta && $venta->pagado;

        // Si la venta está pagada, solo permitir editar notas
        if ($isPaid) {
            return [
                'notas' => 'nullable|string|max:2000',
                'vendedor_id' => 'nullable|exists:users,id',
                'vendedor_type' => 'nullable|string|in:App\\Models\\User',
                'pagado_por' => 'nullable|exists:users,id',
                // Permitir que los demás campos pasen sin validación estricta
                'cliente_id' => 'nullable',
                'price_list_id' => 'nullable',
                'numero_venta' => 'nullable|string',
                'fecha' => 'nullable|date',
                'estado' => 'nullable|string',
                'descuento_general' => 'nullable|numeric|min:0',
                'metodo_pago' => 'nullable|string',
                'forma_pago_sat' => 'nullable|string|max:10',
                'metodo_pago_sat' => 'nullable|string|max:10',
                'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
                'almacen_id' => 'prohibited',
                'productos' => 'nullable|array',
                'productos.*.id' => 'nullable',
                'productos.*.cantidad' => 'nullable',
                'productos.*.precio' => 'nullable',
                'productos.*.descuento' => 'nullable',
                'productos.*.series' => 'nullable|array',
                'productos.*.series.*' => 'nullable|string',
                'productos.*.componentes_series' => 'nullable|array',
                'servicios' => 'nullable|array',
                'servicios.*.id' => 'nullable',
                'servicios.*.cantidad' => 'nullable',
                'servicios.*.precio' => 'nullable',
                'servicios.*.descuento' => 'nullable',
            ];
        }

        return [
            'cliente_id' => 'nullable|exists:clientes,id',
            'vendedor_id' => 'nullable|exists:users,id',
            'vendedor_type' => 'nullable|string|in:App\\Models\\User',
            'pagado_por' => 'nullable|exists:users,id',
            'price_list_id' => 'nullable|exists:price_lists,id',
            'numero_venta' => 'required|string',
            'fecha' => 'required|date',
            'estado' => 'required|string',
            'descuento_general' => 'nullable|numeric|min:0',
            'metodo_pago' => 'required|string',
            'forma_pago_sat' => 'nullable|string|max:10',
            'metodo_pago_sat' => 'nullable|string|max:10',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'almacen_id' => 'prohibited', // No se permite cambiar el almacén
            'productos' => 'nullable|array',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|numeric|min:0.001',
            'productos.*.precio' => 'required|numeric|min:0.01',
            'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
            'productos.*.series' => 'nullable|array',
            'productos.*.series.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            'productos.*.componentes_series' => 'nullable|array',
            'productos.*.componentes_series.*' => 'nullable|array',
            'productos.*.componentes_series.*.*' => 'required|string|regex:/^[a-zA-Z0-9\-_@]+$/|max:50',
            'servicios' => 'nullable|array',
            'servicios.*.id' => 'required|exists:servicios,id',
            'servicios.*.cantidad' => 'required|numeric|min:0.001',
            'servicios.*.precio' => 'required|numeric|min:0.01',
            'servicios.*.descuento' => 'nullable|numeric|min:0|max:100',
            'notas' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $venta = $this->route('venta');
            if (is_string($venta) || is_numeric($venta)) {
                $venta = \App\Models\Venta::find($venta);
            }
            
            $isPaid = $venta && $venta->pagado;

            $this->validateVentaNotCancelled($validator, $venta);

            if (!$isPaid) {
                $this->validateSeriesCount($validator);
                $this->validateProductosActivos($validator);
                $this->validatePreciosNoMenoresAlCosto($validator);
            }
        });
    }

    /**
     * Validate that the venta is not cancelled
     */
    protected function validateVentaNotCancelled($validator, $venta = null)
    {
        if (!$venta) {
            $venta = $this->route('venta');
            if (is_string($venta) || is_numeric($venta)) {
                $venta = \App\Models\Venta::find($venta);
            }
        }
        
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
     * Validate that products are active and prices respect minimum margin
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
     * Validate that selling price respects margin policy (configurable).
     * M-06: Added to UpdateVentaRequest (was only in StoreVentaRequest).
     */
    protected function validatePreciosNoMenoresAlCosto($validator)
    {
        if (!config('ventas.validar_margen', true)) {
            return;
        }

        $user = $this->user();
        $rolesOverride = config('ventas.roles_override_margen', []);
        if ($user && method_exists($user, 'hasRole') && !empty($rolesOverride)) {
            if ($user->hasRole($rolesOverride)) {
                return;
            }
        }

        $productos = $this->input('productos', []);
        if (empty($productos)) {
            return;
        }

        $marginService = new MarginService();

        foreach ($productos as $index => $item) {
            $producto = Producto::find($item['id']);
            if (!$producto) {
                continue;
            }

            $precio = (float) ($item['precio'] ?? 0);
            $validacion = $marginService->validarMargen($producto, $precio);

            if (!$validacion['valido']) {
                $validator->errors()->add(
                    "productos.{$index}.precio",
                    "El precio de '{$producto->nombre}' está por debajo del margen mínimo requerido ({$validacion['margen_requerido']}%)."
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
            'productos.*.cantidad.min' => 'La cantidad debe ser al menos 0.001',
            'productos.*.precio.required' => 'El precio es requerido',
            'productos.*.precio.min' => 'El precio del producto debe ser al menos 0.01',
            'productos.*.descuento.max' => 'El descuento no puede ser mayor a 100%',
            'productos.*.series.*.regex' => 'El formato de la serie es inválido',
            'productos.*.series.*.max' => 'La serie no puede tener más de 50 caracteres',
            'servicios.*.precio.min' => 'El precio del servicio debe ser al menos 0.01',
            'servicios.*.precio.required' => 'El precio del servicio es requerido',
            'servicios.*.cantidad.min' => 'La cantidad del servicio debe ser al menos 0.001',
            'almacen_id.prohibited' => 'No se permite cambiar el almacén de una venta',
            'numero_venta.required' => 'El número de venta es requerido',
            'fecha.required' => 'La fecha es requerida',
            'estado.required' => 'El estado es requerido',
            'metodo_pago.required' => 'El método de pago es requerido',
        ];
    }
}
