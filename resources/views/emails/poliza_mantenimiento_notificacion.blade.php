<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .content {
            padding: 30px;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #111827;
        }

        .info-card {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #3b82f6;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 700;
            width: 140px;
            color: #64748b;
            font-size: 12px;
            text-transform: uppercase;
        }

        .info-value {
            flex: 1;
            color: #1e293b;
            font-weight: 500;
        }

        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            background-color: #f8fafc;
        }

        .folio-badge {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Mantenimiento Preventivo</h1>
            <p>Servicio Programado Automáticamente</p>
        </div>
        <div class="content">
            <div class="greeting">Hola, {{ $cliente->nombre_razon_social }}</div>

            <p>Le informamos que, siguiendo los términos de su póliza de servicio, hemos programado su próximo
                <strong>mantenimiento preventivo</strong>.</p>

            <div class="info-card">
                <div class="info-row">
                    <div class="info-label">Póliza:</div>
                    <div class="info-value">{{ $poliza->nombre }} <span class="folio-badge">{{ $poliza->folio }}</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Ticket de Servicio:</div>
                    <div class="info-value">#{{ $ticket->folio }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha Programada:</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value">Asignado / Programado</div>
                </div>
            </div>

            <p>Nuestro equipo técnico realizará las actividades preventivas necesarias para garantizar el óptimo
                funcionamiento de sus equipos cubiertos bajo contrato.</p>

            <div style="text-align: center; margin-top: 30px;">
                <p style="font-size: 14px; color: #64748b;">Si necesita reprogramar esta visita o tiene alguna duda, por
                    favor contáctenos.</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $empresa->nombre_comercial ?? config('app.name') }}. Todos los derechos
                reservados.</p>
            <p>Este es un mensaje automático, por favor no responda directamente a este correo.</p>
        </div>
    </div>
</body>

</html>