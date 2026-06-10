<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Consumo - {{ $poliza->folio }}</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #f97316;
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

        /* Cliente Info */
        .client-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #f97316;
        }

        .client-name {
            font-size: 18px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .poliza-info {
            color: #64748b;
            font-size: 13px;
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

        .summary-card.warning {
            border-color: #fbbf24;
            background: #fffbeb;
        }

        .summary-card.danger {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .summary-card.success {
            border-color: #22c55e;
            background: #f0fdf4;
        }

        .summary-value {
            font-size: 28px;
            font-weight: bold;
            color: #1e293b;
        }

        .summary-card.danger .summary-value {
            color: #ef4444;
        }

        .summary-card.warning .summary-value {
            color: #f59e0b;
        }

        .summary-card.success .summary-value {
            color: #22c55e;
        }

        .summary-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }

        /* Progress Bar */
        .progress-section {
            margin-bottom: 30px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .progress-bar {
            height: 20px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s;
        }

        .progress-fill.success {
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        /* Alert Box */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert.warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .alert.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert-icon {
            font-size: 20px;
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

        .font-bold {
            font-weight: bold;
        }

        .text-blue {
            color: #3b82f6;
        }

        .font-mono {
            font-family: 'Courier New', monospace;
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
            background: #f97316;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .print-btn:hover {
            background: #ea580c;
        }
    </style>
</head>

<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir / PDF</button>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="report-title">Reporte de Consumo de Póliza</div>
                <div class="report-subtitle">Período: {{ ucfirst($periodo) }}</div>
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

        <!-- Cliente Info -->
        <div class="client-box">
            <div class="client-name">{{ $poliza->cliente->nombre_razon_social }}</div>
            <div class="poliza-info">
                <strong>Póliza:</strong> {{ $poliza->nombre }} ({{ $poliza->folio }})<br>
                <strong>Vigencia:</strong> {{ \Carbon\Carbon::parse($poliza->fecha_inicio)->format('d/m/Y') }} -
                {{ \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y') }}
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div
                class="summary-card {{ $porcentajeUso >= 100 ? 'danger' : ($porcentajeUso >= 80 ? 'warning' : 'success') }}">
                <div class="summary-value">{{ number_format($totalHoras, 1) }}h</div>
                <div class="summary-label">Horas Consumidas</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $horasIncluidas }}h</div>
                <div class="summary-label">Horas Incluidas</div>
            </div>
            <div class="summary-card {{ $exceso > 0 ? 'danger' : 'success' }}">
                <div class="summary-value">{{ $exceso > 0 ? '+' . number_format($exceso, 1) : '0' }}h</div>
                <div class="summary-label">Exceso</div>
            </div>
            <div class="summary-card">
                <div class="summary-value">{{ $tickets->count() }}</div>
                <div class="summary-label">Tickets Atendidos</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-section">
            <div class="progress-label">
                <span>Uso de la Póliza</span>
                <span class="font-bold">{{ $porcentajeUso }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill {{ $porcentajeUso >= 100 ? 'danger' : ($porcentajeUso >= 80 ? 'warning' : 'success') }}"
                    style="width: {{ min($porcentajeUso, 100) }}%"></div>
            </div>
        </div>

        <!-- Alert if exceeded -->
        @if($exceso > 0)
            <div class="alert danger">
                <span class="alert-icon">⚠️</span>
                <div>
                    <strong>Atención:</strong> Se han excedido las horas incluidas en la póliza por
                    <strong>{{ number_format($exceso, 1) }} horas</strong>.
                    Se recomienda revisar el plan contratado y considerar un ajuste para el siguiente período.
                </div>
            </div>
        @elseif($porcentajeUso >= 80)
            <div class="alert warning">
                <span class="alert-icon">📊</span>
                <div>
                    <strong>Aviso:</strong> Se ha utilizado el {{ $porcentajeUso }}% de las horas incluidas.
                    Quedan aproximadamente <strong>{{ number_format($horasIncluidas - $totalHoras, 1) }} horas</strong>
                    disponibles este mes.
                </div>
            </div>
        @else
            <div class="alert success">
                <span class="alert-icon">✅</span>
                <div>
                    El consumo de horas está dentro de lo contratado. Quedan
                    <strong>{{ number_format($horasIncluidas - $totalHoras, 1) }} horas</strong> disponibles.
                </div>
            </div>
        @endif

        <!-- Tickets Table -->
        <div class="table-section">
            <div class="section-title">Detalle de Tickets Atendidos</div>
            <table>
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Técnico</th>
                        <th>Fecha</th>
                        <th class="text-right">Horas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="font-mono text-blue">{{ $ticket->numero }}</td>
                            <td>{{ Str::limit($ticket->titulo, 35) }}</td>
                            <td>{{ $ticket->categoria->nombre ?? '-' }}</td>
                            <td>{{ $ticket->asignado->name ?? '-' }}</td>
                            <td>{{ \Carbon\Carbon::parse($ticket->updated_at)->format('d/m/Y') }}</td>
                            <td class="text-right font-bold">{{ number_format($ticket->horas_trabajadas, 2) }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">
                                No hay tickets registrados en este período
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($tickets->count() > 0)
                    <tfoot>
                        <tr style="background: #f1f5f9;">
                            <td colspan="5" class="font-bold">TOTAL</td>
                            <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($totalHoras, 2) }}h
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Footer -->
        <div class="footer">
            Reporte generado el {{ now()->format('d/m/Y H:i') }} | {{ $empresa->nombre_empresa }}<br>
            Este documento es informativo y muestra el consumo de servicios bajo póliza.
        </div>
    </div>
</body>

</html>