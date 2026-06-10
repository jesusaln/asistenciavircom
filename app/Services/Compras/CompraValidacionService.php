<?php

namespace App\Services\Compras;

use App\Models\Producto;
use App\Models\ProductoSerie;
use Illuminate\Http\Request;

/**
 * Servicio para validaciones de compras
 */
class CompraValidacionService
{
    /**
     * Validar request de compra
     */
    public function validarRequest(Request $request): array
    {
        $rules = [
            'proveedor_id' => 'required|exists:proveedores,id',
            'almacen_id' => 'required|exists:almacenes,id',
            'metodo_pago' => 'required|string',
            'cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
            'descuento_general' => 'nullable|numeric|min:0',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            'productos.*.descuento' => 'nullable|numeric|min:0|max:100',
            'aplicar_retencion_iva' => 'boolean',
            'aplicar_retencion_isr' => 'boolean',
            'cfdi_uuid' => 'nullable|string|uuid',
            'cfdi_folio' => 'nullable|string|max:50',
            'cfdi_serie' => 'nullable|string|max:50',
            'cfdi_fecha' => 'nullable|date',
            'cfdi_emisor_rfc' => 'nullable|string|max:15',
            'cfdi_emisor_nombre' => 'nullable|string|max:255',
            'fecha_compra' => 'nullable|date',
            'pagado_importacion' => 'nullable|boolean',
            'pue_metodo_pago' => 'nullable|string',
            'pue_cuenta_bancaria_id' => 'nullable|exists:cuentas_bancarias,id',
        ];

        // Agregar validación de lotes para productos que vencen y series por unidad
        foreach ($request->productos ?? [] as $index => $producto) {
            $productoModel = Producto::find($producto['id']);
            if ($productoModel && $productoModel->expires) {
                $rules["productos.{$index}.numero_lote"] = 'required|string|max:100';
                $rules["productos.{$index}.fecha_caducidad"] = 'nullable|date|after:today';
                $rules["productos.{$index}.costo_unitario"] = 'nullable|numeric|min:0';
            }
            if ($productoModel && ($productoModel->requiere_serie ?? false)) {
                $requiredSize = isset($producto['cantidad']) ? max(1, (int) $producto['cantidad']) : 1;
                $rules["productos.{$index}.seriales"] = ['required', 'array', 'size:' . $requiredSize];
                $rules["productos.{$index}.seriales.*"] = 'required|string|max:191|distinct';
            }
        }

        return $request->validate($rules);
    }

    /**
     * Validar unicidad de series
     */
    public function validarSeriesUnicas(array $productos, ?int $compraId = null): array
    {
        $errors = [];

        foreach ($productos as $index => $productoData) {
            if (empty($productoData['seriales']) || !is_array($productoData['seriales'])) {
                continue;
            }

            $producto = Producto::find($productoData['id']);
            if (!$producto || !($producto->requiere_serie ?? false)) {
                continue;
            }

            $seriales = array_map('trim', $productoData['seriales']);

            // Check for duplicates in the request
            if (count($seriales) !== count(array_unique($seriales))) {
                $errors[] = [
                    'producto' => $producto->nombre,
                    'serie' => 'Series duplicadas en la solicitud',
                    'compra_existente' => null,
                    'estado' => null,
                ];
                continue;
            }

            foreach ($seriales as $serie) {
                $query = ProductoSerie::where('numero_serie', $serie)
                    ->where('producto_id', $producto->id);

                // En edición, excluir series de la compra actual
                if ($compraId) {
                    $query->where('compra_id', '!=', $compraId);
                }

                $serieExistente = $query->first();

                if ($serieExistente) {
                    $errors[] = [
                        'producto' => $producto->nombre,
                        'serie' => $serie,
                        'compra_existente' => $serieExistente->compra_id,
                        'estado' => $serieExistente->estado,
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Validar que todas las series requeridas estén capturadas
     */
    public function validarSeriesRequeridas(array $productos): array
    {
        $errors = [];

        foreach ($productos as $productoData) {
            $producto = Producto::find($productoData['id']);
            if (!$producto || !($producto->requiere_serie ?? false)) {
                continue;
            }

            $cantidad = $productoData['cantidad'];
            $seriales = $productoData['seriales'] ?? [];

            if (!is_array($seriales) || count($seriales) !== $cantidad) {
                $errors[] = "El producto '{$producto->nombre}' requiere exactamente {$cantidad} series, pero se proporcionaron " . count($seriales);
            }

            // Validar que todas las series sean únicas
            $uniqueSerials = array_unique(array_map('trim', $seriales));
            if (count($uniqueSerials) !== count($seriales)) {
                $errors[] = "El producto '{$producto->nombre}' tiene series duplicadas";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? '' : "Errores en series:\n" . implode("\n", $errors)
        ];
    }
}
