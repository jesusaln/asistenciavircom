<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Venta</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
        }
        .total {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✓ Comprobante de Venta</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">Gracias por su compra</p>
    </div>

    <div class="content">
        <p>Estimado/a <strong>{{ $cliente ? $cliente->nombre_razon_social : 'Cliente' }}</strong>,</p>
        
        <p>Le enviamos el comprobante de su venta. Adjunto encontrará el PDF con todos los detalles.</p>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Número de Venta:</span>
                <span class="value">{{ $venta->numero_venta }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha:</span>
                <span class="value">{{ $venta->created_at ? $venta->created_at->format('d/m/Y H:i') : ($venta->fecha ?? 'N/A') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Estado:</span>
                <span class="value">{{ $venta->estado ? ucfirst($venta->estado->value) : 'Pendiente' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Total:</span>
                <span class="total">${{ number_format($venta->total, 2) }}</span>
            </div>
        </div>

        <p style="color: #6b7280; font-size: 14px;">
            📎 El comprobante en PDF está adjunto a este correo.
        </p>

        <p>Si tiene alguna pregunta o necesita asistencia, no dude en contactarnos.</p>

        <p>Saludos cordiales,<br>
        <strong>Equipo de CDD App</strong></p>
    </div>

    <div class="footer">
        <p>Este es un correo automático, por favor no responda a este mensaje.</p>
        <p style="margin: 5px 0;">© {{ date('Y') }} CDD App. Todos los derechos reservados.</p>
    </div>
</body>
</html>
