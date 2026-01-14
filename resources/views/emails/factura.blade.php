<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
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
        .uuid {
            font-family: monospace;
            font-size: 12px;
            background: #e5e7eb;
            padding: 4px 8px;
            border-radius: 4px;
            word-break: break-all;
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
        .attachments {
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .attachments-title {
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Factura Electrónica</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">CFDI 4.0 - Comprobante Fiscal Digital</p>
    </div>

    <div class="content">
        <p>Estimado/a <strong>{{ $cliente->nombre_razon_social ?? $cliente->nombre ?? 'Cliente' }}</strong>,</p>
        
        <p>Le enviamos su factura electrónica (CFDI). Adjunto encontrará los archivos XML y PDF correspondientes.</p>

        <div class="info-box">
            <div class="info-row">
                <span class="label">Serie/Folio:</span>
                <span class="value">{{ $cfdi->serie ?? '' }}{{ $cfdi->folio ?? '' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha de Emisión:</span>
                <span class="value">{{ isset($cfdi->fecha_emision) ? \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">UUID (Folio Fiscal):</span>
                <span class="uuid">{{ $cfdi->uuid ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Total:</span>
                <span class="total">${{ number_format($cfdi->total ?? $venta->total ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="attachments">
            <div class="attachments-title">📎 Archivos Adjuntos</div>
            <p style="margin: 0; color: #1e40af;">
                • XML del CFDI (archivo fiscal para contabilidad)<br>
                • PDF de la factura (representación impresa)
            </p>
        </div>

        <p style="color: #6b7280; font-size: 14px;">
            <strong>Importante:</strong> Conserve el archivo XML para sus registros fiscales. Este es el documento oficial ante el SAT.
        </p>

        <p>Si tiene alguna pregunta o necesita asistencia, no dude en contactarnos.</p>

        <p>Saludos cordiales,<br>
        <strong>{{ config('app.name', 'CDD App') }}</strong></p>
    </div>

    <div class="footer">
        <p>Este es un correo automático, por favor no responda a este mensaje.</p>
        <p style="margin: 5px 0;">© {{ date('Y') }} {{ config('app.name', 'CDD App') }}. Todos los derechos reservados.</p>
    </div>
</body>
</html>
