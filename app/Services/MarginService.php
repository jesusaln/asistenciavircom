<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\CompraItem;
use Illuminate\Support\Facades\Log;

class MarginService
{
    /**
     * Porcentaje mínimo de margen requerido sobre el costo de compra
     */
    const MARGEN_MINIMO = 0.05; // 5%

    /**
     * Calcula el precio mínimo de venta para un producto basado en su costo de compra + margen mínimo
     *
     * @param Producto $producto
     * @return float
     */
    public function calcularPrecioMinimoVenta(Producto $producto): float
    {
        $costoCompra = $this->obtenerCostoCompra($producto);

        if ($costoCompra <= 0) {
            // Si no hay costo de compra, usar el precio de venta actual como base
            return $producto->precio_venta ?? 0;
        }

        return $costoCompra * (1 + self::MARGEN_MINIMO);
    }

    /**
     * Obtiene el costo de compra más reciente de un producto
     *
     * @param Producto $producto
     * @return float
     */
    public function obtenerCostoCompra(Producto $producto): float
    {
        // Primero intentar obtener de compras recientes
        $ultimaCompra = CompraItem::where('comprable_type', Producto::class)
            ->where('comprable_id', $producto->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimaCompra) {
            return (float) $ultimaCompra->precio;
        }

        // Si no hay compras, usar el precio_compra del producto
        return (float) ($producto->precio_compra ?? 0);
    }

    /**
     * Valida si un precio de venta cumple con el margen mínimo
     *
     * @param Producto $producto
     * @param float $precioVenta
     * @return array ['valido' => bool, 'precio_minimo' => float, 'margen_actual' => float, 'margen_requerido' => float]
     */
    public function validarMargen(Producto $producto, float $precioVenta): array
    {
        $costoCompra = $this->obtenerCostoCompra($producto);
        $precioMinimo = $this->calcularPrecioMinimoVenta($producto);

        $margenActual = $costoCompra > 0 ? (($precioVenta - $costoCompra) / $costoCompra) * 100 : 0;
        $margenRequerido = self::MARGEN_MINIMO * 100;

        return [
            'valido' => $precioVenta >= $precioMinimo,
            'precio_minimo' => round($precioMinimo, 2),
            'costo_compra' => round($costoCompra, 2),
            'margen_actual' => round($margenActual, 2),
            'margen_requerido' => $margenRequerido,
            'diferencia' => round($precioMinimo - $precioVenta, 2)
        ];
    }

    /**
     * Ajusta un precio de venta al margen mínimo si está por debajo
     *
     * @param Producto $producto
     * @param float $precioVentaActual
     * @return float
     */
    public function ajustarPrecioAlMargen(Producto $producto, float $precioVentaActual): float
    {
        $precioMinimo = $this->calcularPrecioMinimoVenta($producto);

        if ($precioVentaActual < $precioMinimo) {
            Log::info("Ajustando precio de venta por debajo del margen", [
                'producto_id' => $producto->id,
                'precio_actual' => $precioVentaActual,
                'precio_minimo' => $precioMinimo,
                'ajuste' => $precioMinimo - $precioVentaActual
            ]);

            return $precioMinimo;
        }

        return $precioVentaActual;
    }

    /**
     * Valida múltiples productos y devuelve información de márgenes
     *
     * @param array $productos Array de ['id' => int, 'precio' => float, 'tipo' => string]
     * @return array
     */
    public function validarMargenesProductos(array $productos): array
    {
        $resultados = [];
        $productosBajoMargen = [];

        foreach ($productos as $item) {
            if ($item['tipo'] === 'producto') {
                $producto = Producto::find($item['id']);

                if ($producto) {
                    $validacion = $this->validarMargen($producto, $item['precio']);

                    $resultados[] = [
                        'producto_id' => $producto->id,
                        'producto_nombre' => $producto->nombre,
                        'precio_actual' => $item['precio'],
                        'validacion' => $validacion
                    ];

                    if (!$validacion['valido']) {
                        $productosBajoMargen[] = [
                            'producto' => $producto,
                            'precio_actual' => $item['precio'],
                            'precio_sugerido' => $validacion['precio_minimo'],
                            'diferencia' => $validacion['diferencia']
                        ];
                    }
                }
            }
        }

        return [
            'todos_validos' => empty($productosBajoMargen),
            'productos_bajo_margen' => $productosBajoMargen,
            'detalles' => $resultados
        ];
    }

    /**
     * Genera mensaje de advertencia para productos con margen insuficiente
     *
     * @param array $productosBajoMargen
     * @return string
     */
    public function generarMensajeAdvertencia(array $productosBajoMargen): string
    {
        if (empty($productosBajoMargen)) {
            return '';
        }

        $mensaje = "⚠️ Los siguientes productos están por debajo del margen mínimo requerido (5% adicional al costo):\n\n";

        foreach ($productosBajoMargen as $item) {
            $producto = $item['producto'];
            $mensaje .= "• {$producto->nombre}\n";
            $mensaje .= "  Precio actual: $" . number_format($item['precio_actual'], 2) . "\n";
            $mensaje .= "  Precio sugerido: $" . number_format($item['precio_sugerido'], 2) . "\n";
            $mensaje .= "  Diferencia: $" . number_format($item['diferencia'], 2) . "\n\n";
        }

        $mensaje .= "¿Desea ajustar automáticamente los precios al margen mínimo sugerido?";

        return $mensaje;
    }
}
