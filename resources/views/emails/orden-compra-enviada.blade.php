<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Orden de Compra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .orden-info {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid #4F46E5;
        }
        .productos-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .productos-table th,
        .productos-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .productos-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .totales {
            background-color: #e8f4f8;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nueva Orden de Compra</h1>
        <p>Se ha generado una nueva orden de compra para su atención</p>
    </div>

    <div class="content">
        <p>Estimado proveedor,</p>

        <p>Nos complace informarle que hemos generado una nueva orden de compra con los siguientes detalles:</p>

        <div class="orden-info">
            <h3>Información de la Orden</h3>
            <p><strong>Número de Orden:</strong> {{ $ordenCompra->numero_orden }}</p>
            <p><strong>Fecha de Orden:</strong> {{ $ordenCompra->fecha_orden?->format('d/m/Y') }}</p>
            <p><strong>Fecha de Entrega Esperada:</strong> {{ $ordenCompra->fecha_entrega_esperada?->format('d/m/Y') }}</p>
            <p><strong>Prioridad:</strong> {{ ucfirst($ordenCompra->prioridad) }}</p>
            <p><strong>Estado:</strong> {{ ucfirst(str_replace('_', ' ', $ordenCompra->estado)) }}</p>
        </div>

        <div class="orden-info">
            <h3>Información del Proveedor</h3>
            <p><strong>Nombre/Razón Social:</strong> {{ $proveedor->nombre_razon_social }}</p>
            <p><strong>RFC:</strong> {{ $proveedor->rfc ?? 'No especificado' }}</p>
            <p><strong>Email:</strong> {{ $proveedor->email ?? 'No especificado' }}</p>
            <p><strong>Teléfono:</strong> {{ $proveedor->telefono ?? 'No especificado' }}</p>
        </div>

        <h3>Productos Solicitados</h3>
        <table class="productos-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Descuento</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $producto)
                <tr>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->pivot->cantidad }} {{ $producto->pivot->unidad_medida ?? 'u' }}</td>
                    <td>${{ number_format($producto->pivot->precio, 2) }}</td>
                    <td>{{ $producto->pivot->descuento ?? 0 }}%</td>
                    <td>${{ number_format(($producto->pivot->cantidad * $producto->pivot->precio) * (1 - ($producto->pivot->descuento ?? 0) / 100), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totales">
            <h3>Totales</h3>
            <p><strong>Subtotal:</strong> ${{ number_format($ordenCompra->subtotal, 2) }}</p>
            <p><strong>Descuento en Items:</strong> ${{ number_format($ordenCompra->descuento_items, 2) }}</p>
            <p><strong>Descuento General:</strong> ${{ number_format($ordenCompra->descuento_general, 2) }}</p>
            <p><strong>IVA ({{ $configuracion->iva_porcentaje }}%):</strong> ${{ number_format($ordenCompra->iva, 2) }}</p>
            <p><strong><span style="font-size: 18px; color: #4F46E5;">Total:</span></strong> <span style="font-size: 18px; color: #4F46E5; font-weight: bold;">${{ number_format($ordenCompra->total, 2) }}</span></p>
        </div>

        @if($ordenCompra->direccion_entrega)
        <div class="orden-info">
            <h3>Dirección de Entrega</h3>
            <p>{{ $ordenCompra->direccion_entrega }}</p>
        </div>
        @endif

        @if($ordenCompra->terminos_pago)
        <div class="orden-info">
            <h3>Términos de Pago</h3>
            <p>{{ $ordenCompra->terminos_pago }}</p>
        </div>
        @endif

        @if($ordenCompra->metodo_pago)
        <div class="orden-info">
            <h3>Método de Pago</h3>
            <p>{{ $ordenCompra->metodo_pago }}</p>
        </div>
        @endif

        @if($ordenCompra->observaciones)
        <div class="orden-info">
            <h3>Observaciones</h3>
            <p>{{ $ordenCompra->observaciones }}</p>
        </div>
        @endif

        <p>Por favor, confirme la recepción de esta orden y proceda con la preparación de los productos solicitados.</p>

        <p>Si tiene alguna duda o requiere información adicional, no dude en contactarnos.</p>

        <p>Atentamente,<br>
        El equipo de {{ config('app.name') }}</p>
    </div>

    <div class="footer">
        <p>Este es un mensaje automático generado por el sistema de gestión.</p>
        <p>Por favor, no responda directamente a este correo.</p>
    </div>
</body>
</html>
