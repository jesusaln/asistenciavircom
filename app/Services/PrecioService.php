<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Cliente;
use App\Models\PriceList;
use Illuminate\Support\Facades\Log;

class PrecioService
{
    /**
     * Resolver el precio de un producto según la lista del cliente
     * 
     * @param Producto $producto Producto del cual obtener el precio
     * @param Cliente|null $cliente Cliente para determinar la lista de precios
     * @param PriceList|null $listaOverride Lista de precios para override manual
     * @return float Precio resuelto
     */
    public function resolverPrecio(
        Producto $producto,
        ?Cliente $cliente = null,
        ?PriceList $listaOverride = null
    ): float {
        $resultado = $this->resolverPrecioConDetalles($producto, $cliente, $listaOverride);
        return $resultado['precio'];
    }

    /**
     * Resolver precio con metadata adicional (lista usada, si fue fallback, etc.)
     * 
     * @param Producto $producto
     * @param Cliente|null $cliente
     * @param PriceList|null $listaOverride
     * @return array ['precio' => float, 'price_list_id' => int, 'price_list_nombre' => string, 'es_fallback' => bool]
     */
    public function resolverPrecioConDetalles(
        Producto $producto,
        ?Cliente $cliente = null,
        ?PriceList $listaOverride = null
    ): array {
        // 1. Determinar qué lista de precios usar
        $priceList = $this->determinarListaDePrecios($cliente, $listaOverride);

        // 2. Buscar precio específico en la lista
        $precioEspecifico = $producto->getPrecioParaLista($priceList->id);

        // 3. Si existe precio específico, usarlo
        if ($precioEspecifico !== null) {
            return [
                'precio' => $precioEspecifico,
                'price_list_id' => $priceList->id,
                'price_list_nombre' => $priceList->nombre,
                'price_list_clave' => $priceList->clave,
                'es_fallback' => false,
            ];
        }

        // 4. Fallback: usar precio_venta del producto
        $precioFallback = $producto->precio_venta ?? 0;

        // 5. Loggear info cuando se usa fallback (frontend ahora maneja el aviso al usuario)
        Log::info('PrecioService: Usando precio_venta como fallback', [
            'producto_id' => $producto->id,
            'producto_nombre' => $producto->nombre,
            'price_list_id' => $priceList->id,
            'price_list_nombre' => $priceList->nombre,
            'precio_fallback' => $precioFallback,
            'cliente_id' => $cliente?->id,
        ]);

        return [
            'precio' => $precioFallback,
            'price_list_id' => $priceList->id,
            'price_list_nombre' => $priceList->nombre,
            'price_list_clave' => $priceList->clave,
            'es_fallback' => true,
        ];
    }

    /**
     * Determinar qué lista de precios usar según prioridad:
     * 1. Override manual
     * 2. Lista del cliente
     * 3. Público general (default)
     * 
     * @param Cliente|null $cliente
     * @param PriceList|null $listaOverride
     * @return PriceList
     */
    protected function determinarListaDePrecios(?Cliente $cliente, ?PriceList $listaOverride): PriceList
    {
        // Prioridad 1: Override manual
        if ($listaOverride) {
            return $listaOverride;
        }

        // Prioridad 2: Lista del cliente
        if ($cliente && $cliente->priceList) {
            return $cliente->priceList;
        }

        // Prioridad 3: Público general (default)
        $publicoGeneral = PriceList::getPublicoGeneral();

        if (!$publicoGeneral) {
            // Si no existe público general, crear una advertencia crítica
            Log::critical('PrecioService: No existe lista "publico_general"', [
                'cliente_id' => $cliente?->id,
            ]);

            // Intentar obtener cualquier lista activa
            $primeraLista = PriceList::activas()->first();

            if (!$primeraLista) {
                throw new \RuntimeException('No hay listas de precios disponibles en el sistema');
            }

            return $primeraLista;
        }

        return $publicoGeneral;
    }

    /**
     * Formatear precio para mostrar al usuario
     * 
     * @param float $precio
     * @param string $moneda
     * @return string
     */
    public function formatearPrecio(float $precio, string $moneda = 'MXN'): string
    {
        $simbolo = match($moneda) {
            'MXN' => '$',
            'USD' => 'USD $',
            'EUR' => '€',
            default => $moneda . ' ',
        };

        return $simbolo . number_format($precio, 2, '.', ',');
    }

    /**
     * Obtener todas las listas de precios activas
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerListasActivas()
    {
        return PriceList::activas()->get();
    }

    /**
     * Verificar si un producto tiene precio definido en una lista específica
     * 
     * @param Producto $producto
     * @param int $priceListId
     * @return bool
     */
    public function tienePrecioEnLista(Producto $producto, int $priceListId): bool
    {
        return $producto->getPrecioParaLista($priceListId) !== null;
    }

    /**
     * Obtener precio de un producto para una lista específica por ID
     *
     * @param Producto $producto
     * @param int|null $priceListId
     * @param int $almacenId Para cálculos futuros si es necesario
     * @return float
     */
    public function obtenerPrecio(Producto $producto, ?int $priceListId, int $almacenId): float
    {
        if ($priceListId) {
            $priceList = PriceList::find($priceListId);
            if ($priceList) {
                $resultado = $this->resolverPrecioConDetalles($producto, null, $priceList);
                return $resultado['precio'];
            }
        }

        // Fallback: usar precio_venta del producto
        return $producto->precio_venta ?? 0;
    }

    /**
     * Obtener productos sin precio en una lista específica
     *
     * @param int $priceListId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerProductosSinPrecio(int $priceListId)
    {
        return Producto::whereDoesntHave('productPrices', function ($query) use ($priceListId) {
            $query->where('price_list_id', $priceListId);
        })->get();
    }
}
