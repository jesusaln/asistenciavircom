<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Balanza de Comprobación - {{ $mes_nombre }} {{ $anio }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; line-height: 1.5; font-size: 8px; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 5px; background: #4f46e5; }
        .content { padding: 30px 40px; }
        .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 18px; font-weight: 800; color: #0f172a; text-align: right; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; color: #475569; font-size: 7.5px; padding: 8px 6px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-weight: bold; }
        td { padding: 6px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .level-1 { font-weight: bold; background-color: #f8fafc; }
        .level-2 { padding-left: 15px; }
        .level-3 { padding-left: 25px; font-style: italic; color: #64748b; }
        .footer { position: fixed; bottom: 20px; left: 40px; right: 40px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="sidebar"></div>
    <div class="content">
        <div class="header">
            <table style="width: 100%; margin: 0;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <span class="company-name">JESUS ALBERTO LOPEZ NORIEGA</span><br>
                        <span style="font-size: 11px; font-weight: bold; color: #475569;">CLIMAS DEL DESIERTO</span><br>
                        <span style="color: #64748b; font-size: 9px;">RFC: LONJ880321KMA</span>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="report-title">Balanza de Comprobación</div>
                        <div style="font-size: 12px; font-weight: bold; color: #64748b;">{{ $mes_nombre }} {{ $anio }}</div>
                        <div style="font-size: 8px; color: #94a3b8; text-transform: uppercase;">Normativa NIF y Anexo 24 SAT</div>
                    </td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 28%;">Cuenta Contable</th>
                    <th style="width: 10%; text-align: right;">Inicial Deudor</th>
                    <th style="width: 10%; text-align: right;">Inicial Acreedor</th>
                    <th style="width: 10%; text-align: right;">Cargos (Debe)</th>
                    <th style="width: 10%; text-align: right;">Abonos (Haber)</th>
                    <th style="width: 10%; text-align: right;">Final Deudor</th>
                    <th style="width: 10%; text-align: right;">Final Acreedor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $cta)
                    <tr class="{{ $cta['nivel'] == 1 ? 'level-1' : '' }}">
                        <td style="font-family: monospace; color: #64748b;">{{ $cta['codigo'] }}</td>
                        <td class="font-bold">
                            <div class="{{ $cta['nivel'] == 2 ? 'level-2' : ($cta['nivel'] == 3 ? 'level-3' : '') }}">
                                {{ $cta['nombre'] }}
                            </div>
                        </td>
                        <td class="text-right" style="font-family: monospace;">
                            @if($cta['naturaleza'] == 'deudora' && abs($cta['saldo_inicial']) > 0.009)
                                ${{ number_format($cta['saldo_inicial'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right" style="font-family: monospace;">
                            @if($cta['naturaleza'] == 'acreedora' && abs($cta['saldo_inicial']) > 0.009)
                                ${{ number_format($cta['saldo_inicial'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right" style="font-family: monospace; color: #166534;">
                            {{ $cta['cargos'] > 0 ? '$' . number_format($cta['cargos'], 2) : '-' }}
                        </td>
                        <td class="text-right" style="font-family: monospace; color: #991b1b;">
                            {{ $cta['abonos'] > 0 ? '$' . number_format($cta['abonos'], 2) : '-' }}
                        </td>
                        <td class="text-right font-bold" style="font-family: monospace; color: #166534;">
                            @if($cta['naturaleza'] == 'deudora' && abs($cta['saldo_final']) > 0.009)
                                ${{ number_format($cta['saldo_final'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right font-bold" style="font-family: monospace; color: #4338ca;">
                            @if($cta['naturaleza'] == 'acreedora' && abs($cta['saldo_final']) > 0.009)
                                ${{ number_format($cta['saldo_final'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: bold; font-size: 8.5px; border-top: 2px solid #cbd5e1;">
                    <td colspan="2" class="text-right" style="color: #334155; text-transform: uppercase;">
                        Sumas Iguales:
                    </td>
                    <td class="text-right" style="font-family: monospace; color: #0f172a;">${{ number_format($totales['inicial_deudor'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; color: #0f172a;">${{ number_format($totales['inicial_acreedor'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; color: #166534;">${{ number_format($totales['cargos'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; color: #991b1b;">${{ number_format($totales['abonos'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; color: #166534; text-decoration: underline;">${{ number_format($totales['final_deudor'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-family: monospace; color: #4338ca; text-decoration: underline;">${{ number_format($totales['final_acreedor'] ?? 0, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            Balanza de Comprobación Anexo 24 | © {{ date('Y') }} {{ $empresa->nombre_empresa ?? 'Climas del Desierto' }}<br>
            Generado el {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
