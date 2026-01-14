<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $factura->numero_venta ?? $factura->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid {{ $configuracion['colores']['principal'] }};
        }
        .empresa-info {
            flex: 1;
        }
        .empresa-logo {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
        }
        .empresa-nombre {
            font-size: 18px;
            font-weight: bold;
            color: {{ $configuracion['colores']['principal'] }};
            margin-bottom: 5px;
        }
        .empresa-detalles {
            font-size: 10px;
            color: #666;
        }
        .factura-info {
            text-align: right;
            color: {{ $configuracion['colores']['secundario'] }};
        }
        .factura-titulo {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .factura-numero {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-block {
            flex: 1;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .info-titulo {
            font-weight: bold;
            color: {{ $configuracion['colores']['principal'] }};
            margin-bottom: 8px;
            font-size: 11px;
        }
        .info-contenido {
            font-size: 10px;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: {{ $configuracion['colores']['principal'] }};
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #f0f8ff;
            font-weight: bold;
        }
        .total-final {
            background-color: {{ $configuracion['colores']['principal'] }};
            color: white;
            font-size: 14px;
        }
        .pie-pagina {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
            text-align: center;
            line-height: 1.3;
        }
        .pie-empresa {
            color: {{ $configuracion['colores']['principal'] }};
            font-weight: bold;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="empresa-info">
            @if($configuracion['empresa']['logo_url'] ?? false)
                <img src="{{ $configuracion['empresa']['logo_url'] }}" alt="Logo" class="empresa-logo" />
            @endif
            <div class="empresa-nombre">{{ $configuracion['empresa']['nombre'] }}</div>
            <div class="empresa-detalles">
                {{ $configuracion['empresa']['razon_social'] }}<br>
                {{ $configuracion['empresa']['direccion'] }}<br>
                Tel: {{ $configuracion['empresa']['telefono'] }}<br>
                Email: {{ $configuracion['empresa']['email'] }}<br>
                @if($configuracion['empresa']['sitio_web'])
                    Web: {{ $configuracion['empresa']['sitio_web'] }}<br>
                @endif
            </div>
        </div>
        <div class="factura-info">
            <div class="factura-titulo">FACTURA</div>
            <div class="factura-numero">#{{ $factura->numero_venta ?? $factura->id }}</div>
            <div><strong>Fecha:</strong> {{ \App\Services\EmpresaConfiguracionService::formatearFecha($factura->fecha ?? now()) }}</div>
            @if($factura->fecha_vencimiento ?? false)
                <div><strong>Vencimiento:</strong> {{ \App\Services\EmpresaConfiguracionService::formatearFecha($factura->fecha_vencimiento) }}</div>
            @endif
        </div>
    </div>

    <div class="info-section">
        <div class="info-block">
            <div class="info-titulo">FACTURAR A:</div>
            <div class="info-contenido">
                {{ $factura->cliente->nombre_razon_social ?? 'Cliente' }}<br>
                @if($factura->cliente->email ?? false)
                    {{ $factura->cliente->email }}<br>
                @endif
                @if($factura->cliente->telefono ?? false)
                    {{ $factura->cliente->telefono }}<br>
                @endif
                @if($factura->cliente->direccion ?? false)
                    {{ $factura->cliente->direccion }}
                @endif
            </div>
        </div>
        <div class="info-block">
            <div class="info-titulo">INFORMACIÓN DE PAGO:</div>
            <div class="info-contenido">
                @if($factura->pagado ?? false)
                    <strong>Pagado</strong><br>
                    Método: {{ $factura->metodo_pago ?? 'N/A' }}<br>
                    @if($factura->fecha_pago ?? false)
                        Fecha: {{ \App\Services\EmpresaConfiguracionService::formatearFecha($factura->fecha_pago) }}<br>
                    @endif
                    @if($factura->referencia_pago ?? false)
                        Ref: {{ $factura->referencia_pago }}<br>
                    @endif
                @else
                    <strong>Pendiente de Pago</strong><br>
                    @if($factura->fecha_vencimiento ?? false)
                        Vence: {{ \App\Services\EmpresaConfiguracionService::formatearFecha($factura->fecha_vencimiento) }}<br>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Cant.</th>
                <th style="width: 50%;">Descripción</th>
                <th style="width: 15%;">Precio Unit.</th>
                <th style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->productos ?? [] as $producto)
                <tr>
                    <td class="text-center">{{ $producto->pivot->cantidad ?? 1 }}</td>
                    <td>{{ $producto->nombre ?? 'Producto' }}</td>
                    <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($producto->pivot->precio ?? 0) }}</td>
                    <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda(($producto->pivot->precio ?? 0) * ($producto->pivot->cantidad ?? 1)) }}</td>
                </tr>
            @endforeach

            @foreach($factura->servicios ?? [] as $servicio)
                <tr>
                    <td class="text-center">{{ $servicio->pivot->cantidad ?? 1 }}</td>
                    <td>{{ $servicio->nombre ?? 'Servicio' }}</td>
                    <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($servicio->pivot->precio ?? 0) }}</td>
                    <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda(($servicio->pivot->precio ?? 0) * ($servicio->pivot->cantidad ?? 1)) }}</td>
                </tr>
            @endforeach

            <!-- Subtotal -->
            <tr>
                <td colspan="3" class="text-right">Subtotal:</td>
                <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($factura->subtotal ?? 0) }}</td>
            </tr>

            <!-- Descuento -->
            @if(($factura->descuento_general ?? 0) > 0)
                <tr>
                    <td colspan="3" class="text-right">Descuento:</td>
                    <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($factura->descuento_general) }}</td>
                </tr>
            @endif

            <!-- IVA -->
            <tr>
                <td colspan="3" class="text-right">IVA ({{ $configuracion['financiera']['iva_porcentaje'] }}%):</td>
                <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($factura->iva ?? 0) }}</td>
            </tr>

            <!-- Total -->
            <tr class="total-final">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-right">{{ \App\Services\EmpresaConfiguracionService::formatearMoneda($factura->total ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    @if($factura->notas ?? false)
        <div style="margin: 20px 0; padding: 15px; background-color: #f0f8ff; border-radius: 5px;">
            <strong>Notas:</strong><br>
            {{ $factura->notas }}
        </div>
    @endif

    @if($configuracion['pie_pagina_facturas'] ?? false)
        <div class="pie-pagina">
            <div class="pie-empresa">{{ $configuracion['empresa']['nombre'] }}</div>
            {!! nl2br(e($configuracion['pie_pagina_facturas'])) !!}
        </div>
    @endif

    @if($configuracion['empresa']['terminos_condiciones'] ?? false)
        <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; font-size: 8px;">
            <strong>Términos y Condiciones:</strong><br>
            {!! nl2br(e($configuracion['empresa']['terminos_condiciones'])) !!}
        </div>
    @endif
</body>
</html>
