{{--
Plantilla de email para envío de orden de compra por email
Ubicación: resources/views/emails/orden_compra.blade.php
--}}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra #{{ $ordenCompra->numero_orden }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3B82F6;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .info-block h3 {
            color: #3B82F6;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 0;
            font-size: 11px;
        }
        .info-table td.label {
            font-weight: bold;
            color: #666;
            width: 120px;
        }
        .highlight-box {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3B82F6;
        }
        .highlight-box h4 {
            margin: 0 0 10px 0;
            color: #3B82F6;
        }
        .notes-box {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .notes-box h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-enviado_a_proveedor {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .priority-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .priority-baja {
            background-color: #d4edda;
            color: #155724;
        }
        .priority-media {
            background-color: #fff3cd;
            color: #856404;
        }
        .priority-alta {
            background-color: #f8d7da;
            color: #721c24;
        }
        .priority-urgente {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1 style="color: #3B82F6; margin: 0;">Nueva Orden de Compra</h1>
            <p style="color: #666; margin: 10px 0 0 0;">Se ha generado una nueva orden de compra para su atención</p>
        </div>

        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #333;">Detalles de la Orden de Compra</h3>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Proveedor:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $ordenCompra->proveedor->nombre_razon_social }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Número de Orden:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $ordenCompra->numero_orden }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Fecha de Orden:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $ordenCompra->fecha_orden->format('d/m/Y') }}</td>
                </tr>
                @if($ordenCompra->fecha_entrega_esperada)
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Fecha de Entrega:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $ordenCompra->fecha_entrega_esperada->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Total:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6; color: #28a745; font-weight: bold;">
                        ${{ number_format($ordenCompra->total, 2) }} {{ $configuracion->moneda ?? 'MXN' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Estado:</strong></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">
                        <span class="status-badge status-{{ $ordenCompra->estado }}">
                            {{ ucfirst(str_replace('_', ' ', $ordenCompra->estado)) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Prioridad:</strong></td>
                    <td style="padding: 8px 0;">
                        <span class="priority-badge priority-{{ $ordenCompra->prioridad }}">
                            {{ ucfirst($ordenCompra->prioridad) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="highlight-box">
            <h4>📦 Productos Solicitados</h4>
            <p style="margin: 0; color: #666;">
                La orden incluye {{ $ordenCompra->productos->count() }} producto(s) con un total de
                ${{ number_format($ordenCompra->total, 2) }} {{ $configuracion->moneda ?? 'MXN' }}.
            </p>
        </div>

        <div class="highlight-box">
            <h4>📎 PDF Adjunto</h4>
            <p style="margin: 0; color: #666;">
                Se ha adjuntado el PDF detallado de la orden de compra #{{ $ordenCompra->numero_orden }}.
                El documento incluye todos los productos, cantidades, precios, condiciones de pago y términos de entrega.
            </p>
        </div>

        @if($ordenCompra->observaciones)
        <div class="notes-box">
            <h4>📝 Observaciones</h4>
            <p style="margin: 0; color: #856404;">{{ $ordenCompra->observaciones }}</p>
        </div>
        @endif

        <div style="background-color: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3B82F6;">
            <h4 style="margin: 0 0 10px 0; color: #3B82F6;">⚠️ Acción Requerida</h4>
            <p style="margin: 0; color: #666;">
                Por favor, confirme la recepción de esta orden de compra y proporcione la fecha exacta de entrega.
                Cualquier cambio en precios, cantidades o fechas debe ser comunicado inmediatamente.
            </p>
        </div>

        <div style="margin: 20px 0;">
            <p style="color: #666; margin: 0;">
                <strong>Empresa:</strong> {{ $configuracion->nombre_empresa }}
            </p>
            @if($configuracion->telefono)
            <p style="color: #666; margin: 5px 0;">
                <strong>Teléfono:</strong> {{ $configuracion->telefono }}
            </p>
            @endif
            @if($configuracion->email)
            <p style="color: #666; margin: 5px 0;">
                <strong>Email:</strong> {{ $configuracion->email }}
            </p>
            @endif
            @if($ordenCompra->direccion_entrega)
            <p style="color: #666; margin: 5px 0;">
                <strong>Dirección de Entrega:</strong> {{ $ordenCompra->direccion_entrega }}
            </p>
            @endif
        </div>

        <div class="footer">
            <p style="margin: 0; color: #666;">
                Este es un mensaje automático enviado desde {{ $configuracion->nombre_empresa }}.
            </p>
            <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">
                Sistema de Gestión CDD - {{ $configuracion->nombre_empresa }} - Orden de Compra #{{ $ordenCompra->numero_orden }}
            </p>
        </div>
    </div>
</body>
</html>
