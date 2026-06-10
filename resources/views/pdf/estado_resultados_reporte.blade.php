<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Estado de Resultados - {{ $mes_nombre }} {{ $anio }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; line-height: 1.5; font-size: 10px; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 5px; background: #4f46e5; }
        .content { padding: 40px 50px; }
        .header { border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 30px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 20px; font-weight: 900; color: #4f46e5; text-align: right; text-transform: uppercase; }
        
        .summary-box { margin-bottom: 30px; }
        .summary-table { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
        .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; }
        .summary-label { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .summary-value { font-size: 14px; font-weight: 900; color: #1e293b; }

        table.detail-table { width: 100%; border-collapse: collapse; }
        th { background-color: #f1f5f9; color: #475569; font-size: 9px; padding: 10px; text-align: left; text-transform: uppercase; font-weight: bold; }
        td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .section-header { background: #f8fafc; font-weight: bold; font-size: 9px; color: #4f46e5; border-bottom: 1px solid #e2e8f0; }
        .total-row { background: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0; }
        
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .ml-4 { padding-left: 25px; color: #64748b; }
        
        .utilidad-box { margin-top: 30px; padding: 25px; border-radius: 20px; text-align: center; color: white; }
        .utilidad-positive { background: #0f172a; }
        .utilidad-negative { background: #e11d48; }
        
        .footer { position: fixed; bottom: 30px; left: 50px; right: 50px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="sidebar"></div>
    <div class="content">
        <div class="header">
            <table style="width: 100%; margin: 0;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <span class="company-name">{{ $empresa->nombre_comercial ?? 'JESUS ALBERTO LOPEZ NORIEGA' }}</span><br>
                        <span style="font-size: 11px; font-weight: bold; color: #475569;">Estado de Resultados (P&L)</span><br>
                        <span style="color: #64748b; font-size: 9px;">RFC: {{ $empresa->rfc ?? 'N/A' }}</span>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="report-title">Estado de Resultados</div>
                        <div style="font-size: 14px; font-weight: bold; color: #64748b;">{{ $mes_nombre }} {{ $anio }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Resumen de Márgenes -->
        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td class="summary-card">
                        <div class="summary-label">Margen Bruto</div>
                        <div class="summary-value">{{ number_format($reportData['resumen']['margen_bruto'], 1) }}%</div>
                    </td>
                    <td class="summary-card">
                        <div class="summary-label">Margen Operativo</div>
                        <div class="summary-value">{{ number_format($reportData['resumen']['margen_operativo'], 1) }}%</div>
                    </td>
                    <td class="summary-card">
                        <div class="summary-label">Margen Neto</div>
                        <div class="summary-value" style="color: {{ $reportData['resumen']['utilidad_neta'] >= 0 ? '#10b981' : '#e11d48' }}">
                            {{ number_format($reportData['resumen']['margen_neto'], 1) }}%
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Código</th>
                    <th style="width: 55%;">Concepto</th>
                    <th style="width: 15%; text-align: right;">Monto</th>
                    <th style="width: 15%; text-align: right;">% Ventas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['secciones'] as $seccion)
                    @if(count($seccion['items']) > 0)
                        <tr class="section-header">
                            <td colspan="4">{{ strtoupper($seccion['titulo']) }}</td>
                        </tr>
                        @foreach($seccion['items'] as $item)
                            <tr>
                                <td style="font-family: monospace; color: #94a3b8;">{{ $item['codigo'] }}</td>
                                <td class="{{ $item['nivel'] > 2 ? 'ml-4' : 'font-bold' }}">
                                    {{ $item['nombre'] }}
                                </td>
                                <td class="text-right font-bold">${{ number_format($item['monto'], 2) }}</td>
                                <td class="text-right" style="color: #94a3b8;">{{ number_format($item['porcentaje'], 1) }}%</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="2" class="text-right">TOTAL {{ strtoupper($seccion['titulo']) }}</td>
                            <td class="text-right">${{ number_format($seccion['total'], 2) }}</td>
                            <td class="text-right">{{ number_format(($seccion['total'] / max(1, $reportData['total_ingresos'])) * 100, 1) }}%</td>
                        </tr>
                        <tr style="height: 10px;"><td colspan="4"></td></tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="utilidad-box {{ $reportData['resumen']['utilidad_neta'] >= 0 ? 'utilidad-positive' : 'utilidad-negative' }}">
            <div style="font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; margin-bottom: 10px;">
                {{ $reportData['resumen']['utilidad_neta'] >= 0 ? 'Utilidad Neta del Ejercicio' : 'Pérdida Neta del Ejercicio' }}
            </div>
            <div style="font-size: 32px; font-weight: 900;">
                ${{ number_format($reportData['resumen']['utilidad_neta'], 2) }}
            </div>
            <div style="font-size: 10px; margin-top: 10px; font-weight: bold; opacity: 0.9;">
                MARGEN DE RENTABILIDAD FINAL: {{ number_format($reportData['resumen']['margen_neto'], 1) }}%
            </div>
        </div>

        <div class="footer">
            Estado de Resultados | {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}<br>
            Generado el {{ now()->format('d/m/Y H:i') }} | Página 1 de 1
        </div>
    </div>
</body>
</html>
