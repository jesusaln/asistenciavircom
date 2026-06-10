<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Catálogo de Cuentas - {{ $empresa->nombre_comercial ?? $empresa->nombre_empresa }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1e293b; line-height: 1.5; font-size: 8px; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 5px; background: #f59e0b; }
        .content { padding: 30px 40px; }
        .header { border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px; }
        .company-name { font-size: 14px; font-weight: bold; color: #0f172a; }
        .report-title { font-size: 16px; font-weight: 800; color: #0f172a; text-align: right; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #f8fafc; color: #475569; font-size: 7px; padding: 6px 8px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-weight: bold; }
        td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .footer { position: fixed; bottom: 20px; left: 40px; right: 40px; text-align: center; font-size: 7px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px; }
        .nivel-2 { padding-left: 15px; }
        .nivel-3 { padding-left: 30px; }
        .nivel-4 { padding-left: 45px; }
        .bg-slate-50 { background-color: #f8fafc; }
        .text-emerald { color: #059669; }
        .text-rose { color: #e11d48; }
    </style>
</head>
<body>
    <div class="sidebar"></div>
    <div class="content">
        <div class="header">
            <table style="width: 100%; margin: 0;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <span class="company-name">{{ $empresa->nombre_empresa }}</span><br>
                        <span style="font-size: 10px; font-weight: bold; color: #475569;">{{ $empresa->nombre_comercial }}</span><br>
                        <span style="color: #64748b; font-size: 8px;">RFC: {{ $empresa->rfc }}</span>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <div class="report-title">Catálogo de Cuentas</div>
                        <div style="font-size: 10px; font-weight: bold; color: #64748b;">Saldos al {{ $fecha }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Código</th>
                    <th style="width: 45%;">Nombre de la Cuenta</th>
                    <th style="width: 12%; text-align: right;">Debe</th>
                    <th style="width: 12%; text-align: right;">Haber</th>
                    <th style="width: 16%; text-align: right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($catalog as $cta)
                    <tr class="{{ $cta->nivel == 1 ? 'bg-slate-50 font-bold' : '' }}">
                        <td style="font-family: monospace; {{ $cta->nivel == 1 ? 'font-weight: bold;' : '' }}">
                            {{ $cta->codigo }}
                        </td>
                        <td class="nivel-{{ $cta->nivel }}">
                            {{ $cta->nombre }}
                        </td>
                        <td class="text-right">
                            ${{ number_format($cta->debe, 2) }}
                        </td>
                        <td class="text-right">
                            ${{ number_format($cta->haber, 2) }}
                        </td>
                        <td class="text-right {{ $cta->saldo >= 0 ? 'text-emerald' : 'text-rose' }}" style="font-weight: bold;">
                            ${{ number_format($cta->saldo, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 900; font-size: 9px;">
                    <td colspan="2" class="text-right">TOTALES DEL CATÁLOGO:</td>
                    <td class="text-right">${{ number_format($catalog->where('nivel', 1)->sum('debe'), 2) }}</td>
                    <td class="text-right">${{ number_format($catalog->where('nivel', 1)->sum('haber'), 2) }}</td>
                    <td class="text-right">${{ number_format($catalog->where('nivel', 1)->sum('saldo'), 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            Catálogo de Cuentas | © {{ date('Y') }} {{ $empresa->nombre_empresa }}<br>
            Este documento es para uso informativo y contable interno.
        </div>
    </div>
</body>
</html>
