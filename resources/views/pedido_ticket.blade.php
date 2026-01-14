{{-- resources/views/pedido_ticket.blade.php --}}
@php
    use Illuminate\Support\Str;

    // Color principal saneado
    $COLOR = ltrim($configuracion->color_principal ?? '#3B82F6', '#');
    $HEX = "#{$COLOR}";
    $money = fn($n) => number_format((float) $n, 2);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket Pedido #{{ $pedido->numero_pedido }}</title>
    <style>
        @page {
            margin: 5mm 5mm 5mm 5mm;
            size: 80mm auto;
        }

        html,
        body {
            font-family: 'Courier New', monospace;
            color: #000;
            font-size: 10px;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            width: 80mm;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .underline {
            text-decoration: underline;
        }

        .empresa {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }

        .titulo {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin: 8px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
        }

        .info {
            margin: 3px 0;
            font-size: 9px;
        }

        .productos {
            margin: 5px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
        }

        .producto-item {
            margin: 2px 0;
            font-size: 8px;
        }

        .totales {
            margin: 5px 0;
            border-top: 1px dashed #000;
            padding-top: 3px;
        }

        .total-line {
            margin: 2px 0;
            font-size: 9px;
        }

        .total-final {
            font-size: 11px;
            font-weight: bold;
            margin: 5px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 0;
            text-align: center;
        }

        .footer {
            margin-top: 8px;
            font-size: 7px;
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 3px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }
    </style>
</head>

<body>
    {{-- ENCABEZADO --}}
    <div class="empresa">
        {{ Str::limit($configuracion->nombre_empresa, 25) }}
    </div>

    <div class="titulo">
        TICKET DE PEDIDO
    </div>

    {{-- INFORMACIÓN --}}
    <div class="info">
        <div><strong>N°:</strong> {{ $pedido->numero_pedido }}</div>
        <div><strong>Fecha:</strong> {{ $pedido->fecha ? $pedido->fecha->format('d/m/Y H:i') : $pedido->created_at->format('d/m/Y H:i') }}</div>
        <div><strong>Cliente:</strong> {{ Str::limit($pedido->cliente->nombre_razon_social, 20) }}</div>
        @if($pedido->cliente->telefono)
            <div><strong>Tel:</strong> {{ $pedido->cliente->telefono }}</div>
        @endif
    </div>

    {{-- PRODUCTOS --}}
    <div class="productos">
        @foreach ($pedido->items as $item)
            @php
                $nombre = $item->pedible->nombre ?? ($item->pedible->descripcion ?? '—');
                $codigo = $item->pedible->codigo ?? null;
            @endphp
            <div class="producto-item">
                <div class="bold">{{ Str::limit($nombre, 25) }}</div>
                @if($codigo)
                    <div style="font-size: 7px;">Cod: {{ Str::limit($codigo, 15) }}</div>
                @endif
                <div style="display: flex; justify-content: space-between;">
                    <span>{{ $item->cantidad }} x ${{ $money($item->precio) }}</span>
                    <span class="bold">${{ $money($item->subtotal) }}</span>
                </div>
                @if($item->descuento > 0)
                    <div style="font-size: 7px; color: #666;">Dto: {{ $item->descuento }}%</div>
                @endif
            </div>
            @if(!$loop->last)
                <div class="line"></div>
            @endif
        @endforeach
    </div>

    {{-- TOTALES --}}
    <div class="totales">
        <div class="total-line">
            <span>Subtotal:</span>
            <span class="right">${{ $money($pedido->subtotal) }}</span>
        </div>
        @if (($pedido->descuento_general ?? 0) > 0)
            <div class="total-line">
                <span>Descuento:</span>
                <span class="right">- ${{ $money($pedido->descuento_general) }}</span>
            </div>
        @endif
        <div class="total-line">
            <span>IVA ({{ $configuracion->iva_porcentaje }}%):</span>
            <span class="right">${{ $money($pedido->iva) }}</span>
        </div>
    </div>

    <div class="total-final">
        TOTAL: ${{ $money($pedido->total) }}
    </div>

    {{-- ESTADO --}}
    <div class="center" style="font-size: 8px; margin: 5px 0;">
        ESTADO: {{ strtoupper($pedido->estado->label()) }}
    </div>

    {{-- NOTAS --}}
    @if ($pedido->notas)
        <div class="center" style="font-size: 7px; margin: 3px 0; border-top: 1px dashed #000; padding-top: 3px;">
            <strong>NOTAS:</strong><br>
            {{ Str::limit($pedido->notas, 100) }}
        </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <div>Gracias por su preferencia</div>
        <div>{{ $configuracion->nombre_empresa }}</div>
        @if($configuracion->telefono)
            <div>Tel: {{ $configuracion->telefono }}</div>
        @endif
        <div style="margin-top: 3px; font-size: 6px;">
            {{ $pedido->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
</body>

</html>
