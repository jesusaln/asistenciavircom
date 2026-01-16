<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Horas por Técnico - {{ ucfirst($periodo) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-left {
            flex: 1;
        }

        .header-right {
            text-align: right;
        }

        .logo {
            max-height: 60px;
            max-width: 200px;
        }

        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .report-subtitle {
            color: #64748b;
            font-size: 14px;
        }

        .company-info {
            font-size: 11px;
            color: #64748b;
        }

        /* Summary Cards */
        .summary-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .summary-card {
            flex: 1;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .summary-card.primary {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .summary-value {
            font-size: 28px;
            font-weight: bold;
            color: #1e293b;
        }

        .summary-card.primary .summary-value {
            color: #3b82f6;
        }

        .summary-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        /* Técnico Cards */
        .tecnico-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .tecnico-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tecnico-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .tecnico-info {
            flex: 1;
        }

        .tecnico-name {
            font-weight: bold;
            color: #1e293b;
            font-size: 14px;
        }

        .tecnico-email {
            color: #64748b;
            font-size: 11px;
        }

        .tecnico-stats {
            text-align: right;
        }

        .tecnico-hours {
            font-size: 20px;
            font-weight: bold;
            color: #3b82f6;
        }

        .tecnico-tickets {
            font-size: 11px;
            color: #64748b;
        }

        /* Table */
        .table-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        th {
            background: #f1f5f9;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:hover {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .text-blue {
            color: #3b82f6;
        }

        .text-green {
            color: #22c55e;
        }

        .font-mono {
            font-family: 'Courier New', monospace;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-orange {
            background: #ffedd5;
            color: #9a3412;
        }

        /* Chart placeholder */
        .chart-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 150px;
        }

        .bar-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .bar {
            width: 100%;
            background: linear-gradient(180deg, #3b82f6, #1d4ed8);
            border-radius: 5px 5px 0 0;
            min-height: 10px;
            transition: height 0.3s;
        }

        .bar-label {
            font-size: 11px;
            color: #64748b;
            margin-top: 8px;
            text-align: center;
        }

        .bar-value {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
        }

        /* Print styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .container {
                max-width: 100%;
                padding: 10px;
            }

            .no-print {
                display: none !important;
            }
        }

        /* Print button */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .print-btn:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir / PDF</button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="report-title">Reporte de Horas Trabajadas</div>
                <div class="report-subtitle">
                    Período: {{ ucfirst($periodo) }}
                    @if($usuario)
                        | Técnico: {{ $usuario->name }}
                    @endif
                </div>
            </div>
            <div class="header-right">
                @if($empresa->logo_url)
                    <img src="{{ $empresa->logo_url }}" alt="Logo" class="logo">
                @endif
                <div class="company-info">
                    <strong>{{ $empresa->nombre_empresa }}</strong><br>
                    {{ $empresa->email }} | {{ $empresa->telefono }}
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card primary">
                <div class="summary-value">{{ number_format($totalGeneral, 1) }}h</div>
                <div class="summary-label">Total Horas Trabajadas</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $totalTickets }}</div>
                <div class="summary-label">Tickets Completados</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">
                    {{ $totalTickets > 0 ? number_format($totalGeneral / $totalTickets, 1) : 0 }}h</div>
                <div class="summary-label">Promedio por Ticket</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $resumenPorTecnico->count() }}</div>
                <div class="summary-label">Técnicos Activos</div>
            </div>
        </div>

        <!-- Técnicos Summary -->
        @if($resumenPorTecnico->count() > 0)
            <div class="table-section">
                <div class="section-title">⏱️ Resumen por Técnico</div>
                <div class="tecnico-grid">
                    @foreach($resumenPorTecnico as $tecnico)
                        <div class="tecnico-card">
                            <div class="tecnico-avatar">{{ substr($tecnico->asignado->name ?? '?', 0, 1) }}</div>
                            <div class="tecnico-info">
                                <div class="tecnico-name">{{ $tecnico->asignado->name ?? 'Sin asignar' }}</div>
                                <div class="tecnico-email">{{ $tecnico->asignado->email ?? '' }}</div>
                            </div>
                            <div class="tecnico-stats">
                                <div class="tecnico-hours">{{ number_format($tecnico->total_horas, 1) }}h</div>
                                <div class="tecnico-tickets">{{ $tecnico->total_tickets }} tickets</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="chart-section">
                <div class="section-title">📊 Comparativa de Horas</div>
                <div class="bar-chart">
                    @php $maxHoras = $resumenPorTecnico->max('total_horas') ?: 1; @endphp
                    @foreach($resumenPorTecnico as $tecnico)
                        <div class="bar-item">
                            <div class="bar" style="height: {{ ($tecnico->total_horas / $maxHoras) * 100 }}%"></div>
                            <div class="bar-label">{{ Str::limit($tecnico->asignado->name ?? '?', 10) }}</div>
                            <div class="bar-value">{{ number_format($tecnico->total_horas, 1) }}h</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Tickets Detail Table -->
        <div class="table-section">
            <div class="section-title">📋 Detalle de Tickets</div>
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Título</th>
                        <th>Cliente</th>
                        <th>Técnico</th>
                        <th>Póliza</th>
                        <th>Fecha</th>
                        <th class="text-right">Horas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="font-mono text-blue">{{ $ticket->numero }}</td>
                            <td>{{ Str::limit($ticket->titulo, 30) }}</td>
                            <td>{{ Str::limit($ticket->cliente->nombre_razon_social ?? '-', 20) }}</td>
                            <td>{{ $ticket->asignado->name ?? '-' }}</td>
                            <td>
                                @if($ticket->poliza)
                                    <span class="badge badge-green">{{ $ticket->poliza->folio }}</span>
                                @else
                                    <span class="badge badge-orange">Sin póliza</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($ticket->updated_at)->format('d/m/Y') }}</td>
                            <td class="text-right font-bold">{{ number_format($ticket->horas_trabajadas, 2) }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">
                                No hay tickets con horas registradas en este período
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($tickets->count() > 0)
                    <tfoot>
                        <tr style="background: #f1f5f9;">
                            <td colspan="6" class="font-bold">TOTAL GENERAL</td>
                            <td class="text-right font-bold" style="font-size: 14px; color: #3b82f6;">
                                {{ number_format($totalGeneral, 2) }}h</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            Reporte generado el {{ now()->format('d/m/Y H:i') }} | {{ $empresa->nombre_empresa }}<br>
            Este documento es para uso interno y evaluación de productividad del equipo de soporte.
        </div>
    </div>
</body>

</html>