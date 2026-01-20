<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color:
                {{ $empresa->color_principal ?? '#10b981' }}
            ;
            padding: 40px;
            text-align: center;
            color: white;
        }

        .content {
            padding: 40px;
        }

        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .btn {
            display: inline-block;
            padding: 16px 32px;
            background-color:
                {{ $empresa->color_principal ?? '#10b981' }}
            ;
            color: white !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            margin-top: 20px;
            box-shadow: 0 4px 12px
                {{ ($empresa->color_principal ?? '#10b981') }}
                40;
        }

        .info-card {
            background-color: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }

        .info-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: bold;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-value {
            font-weight: 800;
            color: #1e293b;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }

        .signature-preview {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if($empresa && $empresa->logo_url)
                <img src="{{ $empresa->logo_url }}" alt="{{ $empresa->nombre_empresa }}"
                    style="max-height: 60px; margin-bottom: 20px;">
            @endif
            <h1>Nueva Solicitud Firmada</h1>
        </div>
        <div class="content">
            <p>Hola Administrador,</p>
            <p>El cliente <strong>{{ $cliente->nombre_razon_social }}</strong> ha firmado digitalmente su solicitud de
                crédito a través del portal.</p>

            <div class="info-card">
                <div class="info-item">
                    <span class="info-label">Cliente:</span>
                    <span class="info-value">{{ $cliente->nombre_razon_social }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">RFC:</span>
                    <span class="info-value">{{ $cliente->rfc }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Límite Solicitado:</span>
                    <span class="info-value">${{ number_format($cliente->credito_solicitado_monto, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Días de Crédito:</span>
                    <span class="info-value">{{ $cliente->credito_solicitado_dias }} días</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha de Firma:</span>
                    <span class="info-value">{{ $cliente->credito_firmado_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            <div class="signature-preview">
                <p style="font-size: 10px; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase;">Vista previa
                    de firma</p>
                <img src="{{ $cliente->credito_firma }}" style="max-height: 80px; mix-blend-multiply: multiply;"
                    alt="Firma">
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/clientes/' . $cliente->id) }}" class="btn">Revisar Solicitud en el Sistema</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $empresa->nombre_empresa }}. Notificación automática del sistema.
        </div>
    </div>
</body>

</html>