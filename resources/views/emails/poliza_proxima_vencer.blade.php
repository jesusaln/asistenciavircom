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
            background: linear-gradient(135deg,
                    {{ $empresa->color_principal ?? '#f59e0b' }}
                    0%,
                    {{ $empresa->color_secundario ?? '#d97706' }}
                    100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .alert-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 16px;
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
            background:
                {{ $empresa->color_principal ?? '#f59e0b' }}
            ;
            color: white !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            margin-top: 20px;
            box-shadow: 0 4px 12px
                {{ ($empresa->color_principal ?? '#f59e0b') }}
                40;
        }

        .btn-secondary {
            display: inline-block;
            padding: 12px 24px;
            background: #f1f5f9;
            color: #475569 !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            margin-top: 10px;
            border: 2px solid #e2e8f0;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }

        p {
            margin: 16px 0;
        }

        .info-box {
            background: #fefce8;
            border: 2px solid #fde047;
            padding: 20px;
            border-radius: 16px;
            margin: 24px 0;
        }

        .info-box.urgent {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #64748b;
            font-size: 14px;
        }

        .info-value {
            font-weight: bold;
            color: #1e293b;
        }

        .countdown {
            font-size: 48px;
            font-weight: 800;
            color:
                {{ $diasRestantes <= 7 ? '#dc2626' : '#f59e0b' }}
            ;
            text-align: center;
            margin: 20px 0;
        }

        .countdown-label {
            font-size: 14px;
            color: #64748b;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .highlight {
            color:
                {{ $empresa->color_principal ?? '#f59e0b' }}
            ;
            font-weight: bold;
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
            <div class="alert-badge">⚠️ Aviso de Vencimiento</div>
            <h1>Tu póliza está próxima a vencer</h1>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $cliente->nombre_razon_social }}</strong>,</p>

            <p>Te recordamos que tu póliza de servicio está próxima a vencer. Para evitar interrupciones en tu servicio,
                te invitamos a renovarla antes de la fecha límite.</p>

            <div class="info-box {{ $diasRestantes <= 7 ? 'urgent' : '' }}">
                <div class="countdown">{{ $diasRestantes }}</div>
                <div class="countdown-label">días para el vencimiento</div>
            </div>

            <div style="background: #f8fafc; border-radius: 16px; padding: 20px; margin: 24px 0;">
                <div class="info-row">
                    <span class="info-label">Póliza</span>
                    <span class="info-value">{{ $poliza->nombre }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Folio</span>
                    <span class="info-value">{{ $poliza->folio }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Vencimiento</span>
                    <span class="info-value">{{ $fechaVencimiento }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Inversión Mensual</span>
                    <span class="info-value">${{ number_format($poliza->monto_mensual, 2) }} + IVA</span>
                </div>
            </div>

            <p><strong>¿Qué sucede si no renuevo?</strong></p>
            <ul style="padding-left: 20px; color: #475569;">
                <li>Perderás acceso a soporte técnico prioritario</li>
                <li>Las horas de servicio incluidas se perderán</li>
                <li>Los tickets activos quedarán sin cobertura de póliza</li>
            </ul>

            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ route('portal.polizas.show', $poliza->id) }}" class="btn">Renovar mi Póliza</a>
                <br>
                <a href="{{ route('portal.dashboard') }}" class="btn-secondary">Ver mi Portal</a>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
                Si tienes alguna pregunta o necesitas ayuda con la renovación, no dudes en contactarnos respondiendo a
                este correo o llamando al {{ $empresa->telefono ?? 'nuestro número de contacto' }}.
            </p>
        </div>

        <div class="footer">
            <p style="margin: 0;">
                <strong>{{ $empresa->nombre_empresa ?? 'Soporte Técnico' }}</strong>
            </p>
            <p style="margin: 8px 0 0 0;">
                &copy; {{ date('Y') }} Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>

</html>