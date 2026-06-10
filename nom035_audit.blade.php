<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Matriz de Evidencias de Cumplimiento - NOM-035</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #1e293b; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #1e3a8a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 16px; color: #1e3a8a; text-transform: uppercase; }
        .header p { margin: 4px 0 0; font-size: 9px; color: #64748b; }

        .summary-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; margin-bottom: 20px; text-align: center; }
        .summary-box .compliance { font-size: 24px; font-weight: bold; color: #166534; }
        .summary-box .label-text { font-size: 8px; color: #64748b; text-transform: uppercase; }

        .section-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .section-table th { background-color: #1e3a8a; color: white; padding: 8px; font-size: 9px; text-transform: uppercase; text-align: left; }
        .section-table th.center { text-align: center; width: 50px; }
        .section-table td { padding: 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .section-table tr:nth-child(even) td { background-color: #f8fafc; }

        .status-pass { color: #16a34a; font-size: 14px; }
        .status-fail { color: #dc2626; font-size: 14px; }
        .numeral-badge { display: inline-block; background-color: #1e3a8a; color: white; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: bold; margin-right: 5px; }
        .evidence-text { font-size: 8px; color: #475569; margin-top: 3px; }
        .date-text { font-size: 7px; color: #94a3b8; font-style: italic; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }

        .legend { background-color: #f1f5f9; border-radius: 6px; padding: 10px; margin-bottom: 15px; font-size: 8px; }
        .legend strong { color: #1e293b; }

        .disclaimer { font-size: 7px; color: #94a3b8; margin-top: 40px; text-align: justify; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Matriz de Evidencias de Cumplimiento</h1>
        <p>{{ $empresa->nombre_empresa ?? 'Climas del Desierto' }} | NOM-035-STPS-2018 | {{ $fecha }}</p>
    </div>

    @php
        $passed = count(array_filter($sections, fn($s) => $s['status']));
        $total = count($sections);
        $pct = round(($passed / $total) * 100);
    @endphp

    <div class="summary-box">
        <span class="label-text">Nivel de Cumplimiento General</span>
        <div class="compliance">{{ $passed }}/{{ $total }} ({{ $pct }}%)</div>
    </div>

    <div class="legend">
        <strong>Leyenda:</strong>
        Numerales basados en la NOM-035-STPS-2018. Los estatus reflejan la evidencia documental disponible en el sistema al momento de la generación de este reporte.
        <strong>✅ = Cumple</strong> | <strong>❌ = Pendiente</strong>
    </div>

    <table class="section-table">
        <thead>
            <tr>
                <th>Numeral</th>
                <th>Requisito NOM-035</th>
                <th class="center">Status</th>
                <th>Resumen de Evidencia</th>
                <th>Última Actualización</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sections as $section)
            <tr>
                <td><span class="numeral-badge">{{ $section['numeral'] }}</span></td>
                <td><strong>{{ $section['name'] }}</strong></td>
                <td style="text-align:center;">
                    @if($section['status'])
                        <span class="status-pass">✅</span>
                    @else
                        <span class="status-fail">❌</span>
                    @endif
                </td>
                <td>
                    <div class="evidence-text">{{ $section['evidence'] }}</div>
                </td>
                <td><span class="date-text">{{ $section['last_update'] }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="disclaimer">
        <strong>NOTA LEGAL:</strong> Este documento constituye una evidencia documental del cumplimiento progresivo con la Norma Oficial Mexicana NOM-035-STPS-2018, Factores de Riesgo Psicosocial en el Trabajo — Identificación, Análisis y Prevención. Los estatus reflejados corresponden al momento de generación de este reporte. La empresa se reserva el derecho de complementar la evidencia en cualquier momento. Este documento puede ser presentado ante la autoridad laboral (STPS) como parte del expediente de cumplimiento normativo.
    </div>

    <div class="footer">
        Documento generado automáticamente por el Sistema de Gestión NOM-035 | {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }} | {{ $fecha }}
    </div>
</body>
</html>
