{{--
Plantilla de email para envío de cotización por email
Ubicación: resources/views/emails/cotizacion.blade.php
--}}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización #{{ $cotizacion->numero_cotizacion }}</title>
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
        .status-aprobada {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="email-container">
    <!-- Header con Logo -->
    <div class="header" style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #FF6B35;">
        @if($configuracion->logo_url)
            <img src="{{ url($configuracion->logo_url) }}" alt="{{ $configuracion->nombre_empresa }}" style="max-height: 60px; margin-bottom: 10px;">
        @endif
        <h1 style="color: #FF6B35; margin: 0; font-size: 24px;">{{ $configuracion->nombre_empresa }}</h1>
        @if($configuracion->telefono || $configuracion->email)
            <p style="color: #666; font-size: 12px; margin: 5px 0 0 0;">
                @if($configuracion->telefono) Tel: {{ $configuracion->telefono }} @endif
                @if($configuracion->telefono && $configuracion->email) | @endif
                @if($configuracion->email) {{ $configuracion->email }} @endif
            </p>
        @endif
    </div>

    <h2 style="color: #FF6B35; margin-bottom: 20px;">Cotización #{{ $cotizacion->numero_cotizacion }}</h2>

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h3 style="margin-top: 0; color: #333;">Detalles de la Cotización</h3>

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Cliente:</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $cliente->nombre_razon_social }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Número de Cotización:</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $cotizacion->numero_cotizacion }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Fecha:</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;">{{ $cotizacion->created_at->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6;"><strong>Total:</strong></td>
                <td style="padding: 8px 0; border-bottom: 1px solid #dee2e6; color: #28a745; font-weight: bold;">
                    ${{ number_format($cotizacion->total, 2) }} {{ $configuracion->moneda }}
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Estado:</strong></td>
                <td style="padding: 8px 0;">
                    <span style="background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        {{ ucfirst($cotizacion->estado->value) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="background-color: #FEF9E7; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FF6B35;">
        <h4 style="margin: 0 0 10px 0; color: #FF6B35;">📎 PDF Adjunto</h4>
        <p style="margin: 0; color: #666;">
            Se ha adjuntado el PDF detallado de la cotización #{{ $cotizacion->numero_cotizacion }}.
            El documento incluye todos los productos/servicios, cantidades, precios y totales.
        </p>
        <p style="margin: 10px 0 0 0; color: #666; font-size: 12px;">
            <strong>Enviado el:</strong> {{ $fecha_envio ?? now()->format('d/m/Y H:i:s') }}
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
        @if($configuracion->direccion_completa)
        <p style="color: #666; margin: 5px 0;">
            <strong>Dirección:</strong> {{ $configuracion->direccion_completa }}
        </p>
        @endif
    </div>

    @if($cotizacion->notas)
    <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
        <h4 style="margin: 0 0 10px 0; color: #856404;">📝 Notas de la Cotización</h4>
        <p style="margin: 0; color: #856404;">{{ $cotizacion->notas }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f8f9fa; border-radius: 8px;">
        <p style="margin: 0; color: #666;">
            Gracias por su preferencia. Este es un mensaje automático.
        </p>
        <p style="margin: 5px 0 0 0; color: #999; font-size: 11px;">
            Si no visualiza correctamente este email, revise su carpeta de spam.
        </p>
    </div>
    </div>
</body>
</html>
