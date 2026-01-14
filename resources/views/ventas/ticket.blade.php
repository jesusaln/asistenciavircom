<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $venta->numero_venta }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            width: 80mm;
            padding: 5mm;
        }
        
        .center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .info-line {
            margin: 2px 0;
        }
        
        .separator {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .double-separator {
            border-top: 2px solid #000;
            margin: 10px 0;
        }
        
        .item {
            margin: 5px 0;
        }
        
        .item-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 5px;
        }
        
        .item-name {
            font-weight: bold;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            margin-top: 2px;
        }
        
        .totals {
            margin-top: 10px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .total-final {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }
        
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">CDD APP</div>
        <div class="info-line">Sistema de Gestión</div>
        <div class="info-line">RFC: XAXX010101000</div>
    </div>

    <!-- Ticket Info -->
    <div class="info-line center bold">TICKET DE VENTA</div>
    <div class="info-line">No: {{ $venta->numero_venta }}</div>
    <div class="info-line">Fecha: {{ $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : ($venta->fecha ?? date('d/m/Y H:i')) }}</div>
    <div class="info-line">Almacén: {{ $venta->almacen->nombre ?? 'N/A' }}</div>
    
    @if($venta->cliente)
    <div class="separator"></div>
    <div class="info-line">Cliente: {{ $venta->cliente->nombre_razon_social }}</div>
    @if($venta->cliente->rfc)
    <div class="info-line">RFC: {{ $venta->cliente->rfc }}</div>
    @endif
    @endif

    <!-- Items -->
    <div class="separator"></div>
    <div class="item-header">PRODUCTOS</div>
    
    @foreach($venta->items as $item)
    <div class="item">
        <div class="item-name">{{ $item->ventable->nombre ?? $item->ventable->descripcion ?? 'Producto' }}</div>
        <div class="item-details">
            <span>{{ $item->cantidad }} x ${{ number_format($item->precio, 2) }}</span>
            <span>${{ number_format($item->cantidad * $item->precio, 2) }}</span>
        </div>
        @if($item->descuento > 0)
        <div class="info-line" style="font-size: 10px;">
            Desc: {{ $item->descuento }}% (-${{ number_format($item->descuento_monto ?? 0, 2) }})
        </div>
        @endif
        
        @if($item->series && $item->series->count() > 0)
        <div class="info-line" style="font-size: 9px;">
            Series: {{ $item->series->pluck('numero_serie')->implode(', ') }}
        </div>
        @endif
    </div>
    @endforeach

    <!-- Totals -->
    <div class="double-separator"></div>
    <div class="totals">
        <div class="total-line">
            <span>Subtotal:</span>
            <span>${{ number_format($venta->subtotal, 2) }}</span>
        </div>
        
        @if($venta->descuento_general > 0)
        <div class="total-line">
            <span>Descuento:</span>
            <span>-${{ number_format($venta->descuento_general, 2) }}</span>
        </div>
        @endif
        
        <div class="total-line">
            <span>IVA (16%):</span>
            <span>${{ number_format($venta->iva, 2) }}</span>
        </div>
        
        <div class="total-line total-final">
            <span>TOTAL:</span>
            <span>${{ number_format($venta->total, 2) }}</span>
        </div>
    </div>

    <!-- Payment Info -->
    @if($venta->pagado)
    <div class="separator"></div>
    <div class="info-line center">
        <span class="bold">PAGADO</span>
        @if($venta->metodo_pago)
        - {{ ucfirst($venta->metodo_pago) }}
        @endif
    </div>
    @else
    <div class="separator"></div>
    <div class="info-line center bold">PENDIENTE DE PAGO</div>
    @endif

    <!-- Barcode -->
    <div class="barcode">
        <div>{{ $venta->numero_venta }}</div>
    </div>

    <!-- Footer -->
    <div class="double-separator"></div>
    <div class="footer">
        <div>¡Gracias por su compra!</div>
        <div style="margin-top: 5px;">{{ date('Y') }} - CDD App</div>
        <div style="margin-top: 10px; font-size: 9px;">
            Este ticket no es válido como factura fiscal
        </div>
    </div>
</body>
</html>
