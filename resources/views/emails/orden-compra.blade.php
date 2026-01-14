<x-mail::message>
# Orden de Compra #{{ $orden->numero_orden }}

Estimado proveedor **{{ $proveedor->nombre_razon_social }}**,

Le enviamos la siguiente orden de compra para su revisión y procesamiento.

---

## Detalles de la Orden

| Campo | Valor |
|:------|:------|
| **Número de Orden** | {{ $orden->numero_orden }} |
| **Fecha** | {{ $orden->fecha_orden?->format('d/m/Y') ?? 'N/A' }} |
| **Fecha de Entrega Esperada** | {{ $orden->fecha_entrega_esperada?->format('d/m/Y') ?? 'A convenir' }} |
| **Prioridad** | {{ ucfirst($orden->prioridad) }} |
| **Términos de Pago** | {{ str_replace('_', ' ', $orden->terminos_pago) }} |
| **Método de Pago** | {{ ucfirst($orden->metodo_pago) }} |

@if($orden->direccion_entrega)
**Dirección de Entrega:** {{ $orden->direccion_entrega }}
@endif

---

## Productos Solicitados

<x-mail::table>
| Producto | Cantidad | Precio Unit. | Descuento | Subtotal |
|:---------|:--------:|-------------:|:---------:|---------:|
@foreach($productos as $producto)
| {{ $producto->nombre }} | {{ number_format($producto->pivot->cantidad, 0) }} | ${{ number_format($producto->pivot->precio, 2) }} | {{ number_format($producto->pivot->descuento ?? 0, 0) }}% | ${{ number_format($producto->pivot->cantidad * $producto->pivot->precio * (1 - ($producto->pivot->descuento ?? 0) / 100), 2) }} |
@endforeach
</x-mail::table>

---

## Resumen

| Concepto | Monto |
|:---------|------:|
| Subtotal | ${{ number_format($orden->subtotal, 2) }} |
@if($orden->descuento_items > 0)
| Descuento por Items | -${{ number_format($orden->descuento_items, 2) }} |
@endif
@if($orden->descuento_general > 0)
| Descuento General | -${{ number_format($orden->descuento_general, 2) }} |
@endif
| IVA (16%) | ${{ number_format($orden->iva, 2) }} |
| **Total** | **${{ number_format($orden->total, 2) }} MXN** |

@if($orden->observaciones)
---

## Observaciones

{{ $orden->observaciones }}
@endif

---

Por favor confirme la recepción de esta orden y la fecha tentativa de entrega.

Gracias por su atención,<br>
**{{ $empresaNombre }}**
</x-mail::message>
