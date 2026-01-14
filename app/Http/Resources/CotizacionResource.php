<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\ClienteResource;

class CotizacionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    // public function toArray($request): array
    // {
    //     return [
    //         'id' => $this->id,
    //         'cliente' => $this->when($this->relationLoaded('cliente'), function () {
    //             return new ClienteResource($this->cliente);
    //         }, $this->cliente_id),
    //         'productos' => $this->getItemsCollection(),
    //         'total' => $this->formatTotal(),
    //         'fecha' => $this->formatFecha(),
    //         'estado' => $this->getEstadoData(),
    //         'created_at' => $this->created_at?->toISOString(),
    //         'updated_at' => $this->updated_at?->toISOString(),
    //         'cliente_info' => $this->getClienteInfo(),
    //         'resumen' => $this->getResumen(),
    //         'metadatos' => $this->getMetadatos(),
    //     ];
    // }

    /**
     * Obtiene la colección combinada de productos y servicios
     */
    private function getItemsCollection(): Collection
    {
        try {
            $productos = $this->getProductosFormatted();
            $servicios = $this->getServiciosFormatted();

            return $productos->merge($servicios)->values();
        } catch (\Exception $e) {
            Log::warning('Error al obtener items de cotización', [
                'cotizacion_id' => $this->id,
                'error' => $e->getMessage()
            ]);

            return collect([]);
        }
    }

    /**
     * Formatea los productos
     */
    private function getProductosFormatted(): Collection
    {
        if (!$this->relationLoaded('productos') || !$this->productos) {
            return collect([]);
        }

        return collect($this->productos)->map(function ($producto) {
            return [
                'id' => $producto->id ?? null,
                'nombre' => $producto->nombre ?? 'Producto sin nombre',
                'descripcion' => $producto->descripcion ?? null,
                'codigo' => $producto->codigo ?? null,
                'tipo' => 'producto',
                'pivot' => $this->formatPivotData($producto->pivot ?? null),
                'disponible' => $producto->disponible ?? true,
                'categoria' => $producto->categoria?->nombre ?? null,
            ];
        })->filter(function ($item) {
            return !is_null($item['id']); // Filtrar items inválidos
        });
    }

    /**
     * Formatea los servicios
     */
    private function getServiciosFormatted(): Collection
    {
        if (!$this->relationLoaded('servicios') || !$this->servicios) {
            return collect([]);
        }

        return collect($this->servicios)->map(function ($servicio) {
            return [
                'id' => $servicio->id ?? null,
                'nombre' => $servicio->nombre ?? 'Servicio sin nombre',
                'descripcion' => $servicio->descripcion ?? null,
                'codigo' => $servicio->codigo ?? null,
                'tipo' => 'servicio',
                'pivot' => $this->formatPivotData($servicio->pivot ?? null),
                'disponible' => $servicio->disponible ?? true,
                'categoria' => $servicio->categoria?->nombre ?? null,
            ];
        })->filter(function ($item) {
            return !is_null($item['id']); // Filtrar items inválidos
        });
    }

    /**
     * Formatea los datos del pivot
     */
    private function formatPivotData($pivot): array
    {
        if (!$pivot) {
            return [
                'cantidad' => 0,
                'precio' => 0.00,
                'subtotal' => 0.00,
                'descuento' => 0.00,
                'impuestos' => 0.00,
            ];
        }

        $cantidad = (float) ($pivot->cantidad ?? 0);
        $precio = (float) ($pivot->precio ?? 0);
        $descuento = (float) ($pivot->descuento ?? 0);
        $impuestos = (float) ($pivot->impuestos ?? 0);

        $subtotal = $cantidad * $precio;
        $subtotalConDescuento = $subtotal - $descuento;
        $total = $subtotalConDescuento + $impuestos;

        return [
            'cantidad' => $cantidad,
            'precio' => round($precio, 2),
            'subtotal' => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'impuestos' => round($impuestos, 2),
            'total' => round($total, 2),
            'precio_formatted' => '$' . number_format($precio, 2),
            'subtotal_formatted' => '$' . number_format($subtotal, 2),
            'total_formatted' => '$' . number_format($total, 2),
        ];
    }

    /**
     * Formatea el total
     */
    private function formatTotal(): array
    {
        $total = (float) ($this->total ?? 0);

        return [
            'valor' => round($total, 2),
            'formatted' => '$' . number_format($total, 2),
            'en_letras' => $this->convertirNumeroALetras($total),
        ];
    }

    /**
     * Formatea la fecha
     */
    private function formatFecha(): array
    {
        $fecha = $this->created_at;

        if (!$fecha) {
            return [
                'iso' => null,
                'formatted' => 'Fecha no disponible',
                'timestamp' => null,
            ];
        }

        return [
            'iso' => $fecha->toISOString(),
            'formatted' => $fecha->format('d/m/Y H:i:s'),
            'fecha_corta' => $fecha->format('d/m/Y'),
            'timestamp' => $fecha->timestamp,
            'humana' => $fecha->diffForHumans(),
        ];
    }

    /**
     * Obtiene los datos del estado
     */
    private function getEstadoData(): array
    {
        $estado = $this->estado;

        if (!$estado) {
            return [
                'value' => 'pendiente',
                'label' => 'Pendiente',
                'color' => 'gray',
                'puede_convertir' => false,
                'puede_editar' => true,
                'puede_eliminar' => true,
            ];
        }

        return [
            'value' => $estado->value ?? 'pendiente',
            'label' => method_exists($estado, 'label') ? $estado->label() : 'Estado desconocido',
            'color' => method_exists($estado, 'color') ? $estado->color() : 'gray',
            'puede_convertir' => method_exists($estado, 'puedeConvertir') ? $estado->puedeConvertir() : false,
            'puede_editar' => method_exists($estado, 'puedeEditar') ? $estado->puedeEditar() : true,
            'puede_eliminar' => method_exists($estado, 'puedeEliminar') ? $estado->puedeEliminar() : true,
            'siguiente_estados' => method_exists($estado, 'siguientesEstados') ? $estado->siguientesEstados() : [],
        ];
    }

    /**
     * Obtiene información del cliente
     */
    private function getClienteInfo(): array
    {
        $cliente = $this->cliente;

        if (!$cliente) {
            return [
                'nombre' => 'Cliente no disponible',
                'email' => null,
                'telefono' => null,
                'direccion' => null,
            ];
        }

        return [
            'id' => $cliente->id ?? null,
            'nombre' => $cliente->nombre_razon_social ?? $cliente->nombre ?? 'Sin nombre',
            'email' => $cliente->email ?? null,
            'telefono' => $cliente->telefono ?? null,
            'direccion' => $cliente->direccion ?? null,
            'documento' => $cliente->documento ?? null,
            'tipo_documento' => $cliente->tipo_documento ?? null,
        ];
    }

    /**
     * Obtiene resumen de la cotización
     */
    private function getResumen(): array
    {
        $items = $this->getItemsCollection();

        return [
            'total_items' => $items->count(),
            'total_productos' => $items->where('tipo', 'producto')->count(),
            'total_servicios' => $items->where('tipo', 'servicio')->count(),
            'cantidad_total' => $items->sum('pivot.cantidad'),
            'subtotal' => round($items->sum('pivot.subtotal'), 2),
            'descuentos' => round($items->sum('pivot.descuento'), 2),
            'impuestos' => round($items->sum('pivot.impuestos'), 2),
        ];
    }

    /**
     * Obtiene metadatos adicionales
     */
    // private function getMetadatos(): array
    // {
    //     return [
    //         'version' => '1.0',
    //         'generado_en' => now()->toISOString(),
    //         'usuario_id' => auth()->id() ?? null,
    //         'ip' => request()->ip() ?? null,
    //         'user_agent' => request()->userAgent() ?? null,
    //     ];
    // }

    /**
     * Convierte número a letras (implementación básica)
     */
    private function convertirNumeroALetras(float $numero): string
    {
        // Implementación básica - puedes usar una librería más completa
        if ($numero == 0) return 'Cero pesos';

        $entero = floor($numero);
        $decimales = round(($numero - $entero) * 100);

        // Aquí implementarías la lógica completa de conversión
        // Por ahora retorna un formato simple
        return number_format($entero, 0) . ' pesos' .
            ($decimales > 0 ? ' con ' . $decimales . ' centavos' : '');
    }

    /**
     * Additional attributes to include when the resource is loaded
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toISOString(),
                'version' => 'v1.0',
            ]
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function additional(array $data): static
    {
        return $this->additional(array_merge($data, [
            'success' => true,
            'message' => 'Cotización obtenida correctamente'
        ]));
    }
}
