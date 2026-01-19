<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte Mensual Póliza {{ $poliza->folio }} - {{ $mes_nombre }} {{ $anio }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #334155;
            line-height: 1.5;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 8px;
            background: #2563eb;
        }

        .content {
            padding: 40px 50px;
        }

        .header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }

        .report-title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-align: right;
            text-transform: uppercase;
        }

        .section-title {
            font-size: 11px;
            font-weight: 800;
            color: #1e40af;
            text-transform: uppercase;
            margin-bottom: 15px;
            border-left: 4px solid #2563eb;
            padding-left: 10px;
        }

        .stats-grid {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: separate;
            border-spacing: 15px 0;
            margin-left: -15px;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            display: block;
        }

        .stat-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 800;
            margin-top: 5px;
            letter-spacing: 1px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        table.data-table th {
            background-color: #f8fafc;
            color: #475569;
            text-transform: uppercase;
            font-size: 8px;
            padding: 12px 10px;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 800;
        }

        table.data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-info {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 50px;
            right: 50px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }

        .equipment-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 6px;
            margin-right: 8px;
            margin-bottom: 8px;
            font-size: 9px;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .folio-box {
            font-family: 'DejaVu Sans Mono', monospace;
            font-weight: bold;
            color: #2563eb;
        }
    </style>
</head>

<body>
    <div class="sidebar"></div>

    <div class="content">
        <div class="header">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <span class="company-name">{{ $empresa->nombre_empresa ?? 'Vircom' }}</span><br>
                        <span style="color: #64748b; font-size: 9px;">{{ $empresa->email ??
                            'contacto@asistenciavircom.com' }}</span>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="report-title">Reporte de Servicio</div>
                        <div style="font-size: 13px; font-weight: bold; color: #2563eb;">{{ $mes_nombre }} {{ $anio }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 30px;">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; width: 65%;">
                        <div
                            style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">
                            Titular del Servicio</div>
                        <div style="font-size: 16px; font-weight: bold; color: #0f172a;">
                            {{ $poliza->cliente->nombre_razon_social }}</div>
                        <div style="font-size: 9px; color: #64748b; margin-top: 2px;">RFC:
                            {{ $poliza->cliente->rfc ?: 'N/A' }}</div>
                    </td>
                    <td style="border: none; text-align: right; vertical-align: top;">
                        <div
                            style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">
                            Identificador Póliza</div>
                        <div class="folio-box" style="font-size: 14px;">{{ $poliza->folio }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="stats-grid">
            <tr>
                <td style="border: none; padding: 0;">
                    <div class="stat-card">
                        <span class="stat-value">{{ $tickets->count() }}</span>
                        <span class="stat-label">Solicitudes</span>
                    </div>
                </td>
                <td style="border: none; padding: 0;">
                    <div class="stat-card">
                        <span class="stat-value">{{ number_format($total_horas, 1) }}</span>
                        <span class="stat-label">Horas Totales</span>
                    </div>
                </td>
                <td style="border: none; padding: 0;">
                    <div class="stat-card">
                        <span class="stat-value">{{ $tickets_resueltos }}</span>
                        <span class="stat-label">Completados</span>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Detalle de Actividad Mensual</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Fecha</th>
                    <th style="width: 12%;">Ticket</th>
                    <th style="width: 50%;">Descripción del Servicio</th>
                    <th style="width: 15%;">Categoría</th>
                    <th style="width: 11%; text-align: right;">Horas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
                        <td class="folio-box">{{ $ticket->folio }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $ticket->titulo }}</strong><br>
                            <div style="color: #64748b; font-size: 9px; margin-top: 4px;">
                                {{ Str::limit($ticket->description, 120) }}</div>
                        </td>
                        <td><span class="badge badge-info">{{ $ticket->categoria->nombre ?? 'Soporte' }}</span></td>
                        <td style="font-weight: bold; text-align: right; color: #0f172a;">
                            {{ number_format($ticket->horas_trabajadas, 1) }}</td>
                    </tr>
                @endforeach
                @if($tickets->isEmpty())
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">No se registraron
                            actividades de soporte en este periodo mensual.</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if($poliza->equipos->isNotEmpty())
            <div class="section-title">Infraestructura Bajo Cobertura</div>
            <div style="margin-bottom: 30px;">
                @foreach($poliza->equipos as $equipo)
                    <div class="equipment-tag">
                        <strong style="color: #334155;">{{ $equipo->nombre }}</strong>
                        <span style="color: #94a3b8; font-size: 8px;">| S/N: {{ $equipo->numero_serie ?? 'N/A' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="footer">
            Este reporte es confidencial y para uso exclusivo de {{ $poliza->cliente->nombre_razon_social }}.<br>
            © {{ date('Y') }} {{ $empresa->nombre_empresa ?? 'Vircom' }} | Soporte Técnico Especializado | Generado el
            {{ $fecha_generacion }}
        </div>
    </div>
</body>

</html>