<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte Mensual Póliza {{ $poliza->folio }} - {{ $mes_nombre }} {{ $anio }}</title>
    <style>
        @page {
            margin: 40px;
        }

        body {
            font-family: 'Helvetica', Arial, sans-serif;
            color: #333;
            line-height: 1.5;
            font-size: 11px;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo {
            width: 140px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }

        .report-title {
            font-size: 22px;
            font-weight: 800;
            color: #111;
            text-align: right;
            text-transform: uppercase;
        }

        .meta-grid {
            width: 100%;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #2563eb;
            text-transform: uppercase;
            margin-bottom: 12px;
            border-left: 4px solid #2563eb;
            padding-left: 10px;
        }

        .stats-grid {
            width: 100%;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
            display: block;
        }

        .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-top: 4px;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-size: 9px;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .equipment-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 9px;
            color: #475569;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 0;">
                    <span class="company-name">{{ $empresa->nombre_empresa ?? 'Vircom' }}</span><br>
                    <span style="color: #64748b;">{{ $empresa->email ?? 'contacto@asistenciavircom.com' }}</span>
                </td>
                <td style="border: none; padding: 0; text-align: right;">
                    <div class="report-title">Resumen de Servicio</div>
                    <div style="font-size: 14px; font-weight: bold; color: #2563eb;">{{ $mes_nombre }} {{ $anio }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-grid">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 60%;">
                    <div
                        style="font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                        Cliente</div>
                    <div style="font-size: 16px; font-weight: bold; color: #111;">
                        {{ $poliza->cliente->nombre_razon_social }}</div>
                </td>
                <td style="border: none; text-align: right;">
                    <div
                        style="font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">
                        Póliza Activa</div>
                    <div style="font-size: 14px; font-weight: bold; color: #1e40af; font-family: monospace;">
                        {{ $poliza->folio }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="stats-grid">
        <tr>
            <td style="width: 33%; border: none; padding: 0 10px 0 0;">
                <div class="stat-card">
                    <span class="stat-value">{{ $tickets->count() }}</span>
                    <span class="stat-label">Solicitudes Atendidas</span>
                </div>
            </td>
            <td style="width: 33%; border: none; padding: 0 10px;">
                <div class="stat-card">
                    <span class="stat-value">{{ number_format($total_horas, 2) }}</span>
                    <span class="stat-label">Horas Trabajadas</span>
                </div>
            </td>
            <td style="width: 33%; border: none; padding: 0 0 0 10px;">
                <div class="stat-card">
                    <span class="stat-value">{{ $tickets_resueltos }}</span>
                    <span class="stat-label">Casos Cerrados</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Actividad de Soporte Mensual</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Fecha</th>
                <th style="width: 10%;">Folio</th>
                <th style="width: 45%;">Descripción del Servicio</th>
                <th style="width: 15%;">Categoría</th>
                <th style="width: 15%;">Horas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $ticket->folio }}</td>
                    <td>
                        <strong>{{ $ticket->titulo }}</strong><br>
                        <span style="color: #64748b; font-size: 9px;">{{ Str::limit($ticket->description, 100) }}</span>
                    </td>
                    <td><span class="badge badge-info">{{ $ticket->categoria->nombre ?? 'Soporte' }}</span></td>
                    <td style="font-weight: bold;">{{ number_format($ticket->horas_trabajadas, 2) }}</td>
                </tr>
            @endforeach
            @if($tickets->isEmpty())
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">No se registraron tickets de
                        soporte en este periodo.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Inventario Bajo Cobertura</div>
    <div style="background: #f8fafc; border-radius: 8px; padding: 15px; border: 1px solid #e2e8f0;">
        @foreach($poliza->equipos as $equipo)
            <div class="equipment-tag">
                <strong>{{ $equipo->nombre }}</strong> (S/N: {{ $equipo->numero_serie ?? '---' }})
            </div>
        @endforeach
        @foreach($poliza->condiciones_especiales['equipos_cliente'] ?? [] as $equipo)
            <div class="equipment-tag">
                <strong>{{ $equipo['nombre'] }}</strong> (S/N: {{ $equipo['serie'] ?? '---' }})
            </div>
        @endforeach
        @if(empty($poliza->equipos) && empty($poliza->condiciones_especiales['equipos_cliente']))
            <span style="color: #94a3b8;">No se han detallado equipos específicos para esta póliza.</span>
        @endif
    </div>

    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 70%;">
                    <p style="font-size: 9px; color: #64748b; font-style: italic;">
                        Este reporte es un resumen del valor entregado a través de su póliza de soporte técnico.
                        Mantener su infraestructura al día es nuestra prioridad.
                    </p>
                </td>
                <td style="border: none; text-align: right;">
                    <div style="font-size: 10px; font-weight: bold; color: #111;">{{ $empresa->nombre_empresa }}</div>
                    <div style="font-size: 8px; color: #64748b;">Generado automáticamente el {{ $fecha_generacion }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        © {{ date('Y') }} {{ $empresa->nombre_empresa }} | Soporte Técnico Especializado | Documento Confidencial para
        el Cliente
    </div>
</body>

</html>