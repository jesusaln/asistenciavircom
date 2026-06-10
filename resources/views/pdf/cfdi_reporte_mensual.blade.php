<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte Fiscal Mensual - {{ $mes_nombre }} {{ $anio }}</title>
    <style>
        @page { 
            margin: 1cm; 
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #334155; 
            line-height: 1.1; 
            font-size: 8px; 
            margin: 0; 
            padding: 0; 
        }
        
        .pagenum:before { content: counter(page); }
        .pagecount:before { content: counter(pages); }

        .header-table { width: 100%; margin-bottom: 15px; border-bottom: 2px solid #FF6B35; padding-bottom: 10px; }
        .logo-placeholder { width: 140px; height: 60px; background: #f8fafc; border: 1px dashed #cbd5e1; text-align: center; line-height: 60px; color: #94a3b8; font-weight: bold; }
        .logo-img { max-width: 160px; max-height: 60px; }
        
        .grid-table { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin-bottom: 15px; }
        .card { 
            background: #ffffff; 
            border: 1px solid #e2e8f0; 
            padding: 8px; 
            border-radius: 6px; 
            vertical-align: top;
        }
        .card-header { 
            font-size: 8.5px; 
            font-weight: 800; 
            color: #1e293b; 
            text-transform: uppercase; 
            border-bottom: 1.5px solid #f1f5f9; 
            padding-bottom: 4px; 
            margin-bottom: 6px; 
        }
        
        .stats-table { width: 100%; border-collapse: collapse; }
        .stats-table td { padding: 2px 0; font-size: 8px; }
        .stat-label { color: #64748b; text-align: left; }
        .stat-value { font-weight: bold; text-align: right; color: #1e293b; }
        .stat-divider { border-top: 1px dashed #e2e8f0; height: 1px; margin: 4px 0; }
        .total-row { background: #f8fafc; font-weight: 900; font-size: 9px; }

        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }

        .data-table { width: 100%; border-collapse: collapse; margin-top: 5px; table-layout: fixed; }
        .data-table thead { display: table-header-group; }
        .data-table th { 
            background-color: #1e293b; 
            color: white; 
            padding: 5px 3px; 
            text-align: left; 
            font-size: 7px; 
            text-transform: uppercase;
        }
        .data-table td { 
            padding: 4px 3px; 
            border-bottom: 0.5px solid #e2e8f0; 
            font-size: 7px; 
            vertical-align: middle;
            word-wrap: break-word;
        }
        .row-alt { background-color: #f8fafc; }
        
        .badge { padding: 1px 3px; border-radius: 2px; font-size: 6px; font-weight: 800; }
        .badge-I { background: #dcfce7; color: #166534; }
        .badge-E { background: #fee2e2; color: #991b1b; }
        .badge-P { background: #e0f2fe; color: #0369a1; }
        .badge-N { background: #fef3c7; color: #92400e; }

        .poliza-badge {
            background: #f1f5f9;
            color: #475569;
            border: 0.5px solid #cbd5e1;
            padding: 1px 2px;
            border-radius: 2px;
            font-family: monospace;
            font-size: 6.5px;
        }

        .footer { 
            position: fixed; 
            bottom: -0.5cm; 
            left: 0; 
            right: 0; 
            font-size: 7px; 
            color: #94a3b8; 
            text-align: center; 
            border-top: 0.5px solid #f1f5f9; 
            padding-top: 3px; 
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if($empresa && $empresa->logo_reportes_url)
                    <img src="{{ $empresa->logo_reportes_url }}" class="logo-img">
                @else
                    <div class="logo-placeholder">SIN LOGO</div>
                @endif
                <div style="margin-top: 5px;">
                    <strong style="font-size: 9px; color: #1e293b;">{{ $empresa->razon_social ?? 'JESUS ALBERTO LOPEZ NORIEGA' }}</strong><br>
                    <span style="color: #64748b;">RFC: {{ $empresa->rfc ?? 'LONJ880321KMA' }}</span><br>
                    <span style="color: #94a3b8; font-size: 7px;">{{ $empresa->direccion_completa ?? 'OPATAS No. 115, Pueblitos, C.P. 83117, Sonora, México' }}</span>
                </div>
            </td>
            <td style="width: 45%; text-align: right; vertical-align: top;">
                <h1 style="font-size: 16px; font-weight: 900; color: #1e293b; margin: 0;">INFORME FISCAL MENSUAL</h1>
                <div style="font-size: 11px; font-weight: bold; color: #FF6B35; margin-top: 2px;">PERIODO: {{ strtoupper($mes_nombre) }} {{ $anio }}</div>
                <div style="margin-top: 5px; font-size: 7px; color: #94a3b8;">
                    Generado el: {{ date('d/m/Y H:i') }}<br>
                    Conciliación CFDI vs Pólizas
                </div>
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <tr>
            <td style="width: 33%;">
                <div class="card" style="border-top: 3px solid #10b981;">
                    <div class="card-header">Flujos de Efectivo</div>
                    <table class="stats-table">
                        <tr>
                            <td class="stat-label">Cobrado (Ingresos):</td>
                            <td class="stat-value text-success">${{ number_format($stats['pagos_recibidos']['monto_total'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label">Pagado (Egresos):</td>
                            <td class="stat-value text-danger">-${{ number_format($stats['pagos_realizados']['monto_total'], 2) }}</td>
                        </tr>
                        <tr><td colspan="2"><div class="stat-divider"></div></td></tr>
                        <tr>
                            <td class="stat-label">Nóminas Pagadas:</td>
                            <td class="stat-value">${{ number_format($stats['total_sueldos'], 2) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="stat-label" style="color: #1e293b; padding-top: 4px;">FLUJO NETO:</td>
                            <td class="stat-value {{ ($stats['pagos_recibidos']['monto_total'] - $stats['pagos_realizados']['monto_total']) >= 0 ? 'text-success' : 'text-danger' }}" style="padding-top: 4px;">
                                ${{ number_format($stats['pagos_recibidos']['monto_total'] - $stats['pagos_realizados']['monto_total'], 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="card" style="border-top: 3px solid #8b5cf6;">
                    <div class="card-header">Conciliación IVA</div>
                    <table class="stats-table">
                        <tr>
                            <td class="stat-label">IVA Trasladado:</td>
                            <td class="stat-value">${{ number_format($stats['iva_trasladado'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label">IVA Acreditable:</td>
                            <td class="stat-value text-success">-${{ number_format($stats['iva_acreditable'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label">IVA Devoluciones:</td>
                            <td class="stat-value text-danger">+${{ number_format($stats['iva_devoluciones_gastos'], 2) }}</td>
                        </tr>
                        <tr><td colspan="2"><div class="stat-divider"></div></td></tr>
                        <tr class="total-row" style="background: #f5f3ff;">
                            <td class="stat-label" style="color: #5b21b6; padding-top: 4px;">IVA A PAGAR:</td>
                            <td class="stat-value" style="color: #7c3aed; padding-top: 4px;">
                                ${{ number_format($stats['iva_pagar'], 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="card" style="border-top: 3px solid #ef4444;">
                    <div class="card-header">Determinación ISR</div>
                    <table class="stats-table">
                        <tr>
                            <td class="stat-label">Ingreso RESICO:</td>
                            <td class="stat-value">${{ number_format($stats['ingresos_brutos'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label">Tasa Aplicada:</td>
                            <td class="stat-value">{{ number_format($stats['isr_tasa'], 2) }}%</td>
                        </tr>
                        <tr>
                            <td class="stat-label">ISR Bruto (Subtotal):</td>
                            <td class="stat-value">${{ number_format($stats['isr_pagar'], 2) }}</td>
                        </tr>
                        <tr><td colspan="2"><div class="stat-divider"></div></td></tr>
                        <tr>
                            <td class="stat-label">ISR Retenido (-):</td>
                            <td class="stat-value text-success">-${{ number_format($stats['isr_retenido_clientes'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label" style="font-size: 6.5px; color: #64748b; padding-left: 6px;">• Ret. Contado (PUE):</td>
                            <td class="stat-value text-success" style="font-size: 6.5px;">${{ number_format($stats['detalle_flujo']['retenciones_pue'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="stat-label" style="font-size: 6.5px; color: #64748b; padding-left: 6px;">• Ret. Cobranza (REP):</td>
                            <td class="stat-value text-success" style="font-size: 6.5px;">${{ number_format($stats['detalle_flujo']['retenciones_rep'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="total-row" style="background: #fef2f2;">
                            <td class="stat-label" style="color: #991b1b; padding-top: 4px;">ISR A PAGAR:</td>
                            <td class="stat-value" style="color: #dc2626; padding-top: 4px;">
                                ${{ number_format($stats['isr_neto_pagar'], 2) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div style="background: #f8fafc; border: 0.5px solid #e2e8f0; padding: 6px; border-radius: 4px; text-align: center; margin-bottom: 10px;">
        <span style="font-size: 7.5px; color: #64748b;">
            Este reporte integra <strong>{{ $cfdis->count() }}</strong> documentos fiscales. 
            Conciliación digital entre CFDI (SAT) y <strong>Pólizas Contables</strong> automáticas.
        </span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 7%;">FECHA</th>
                <th style="width: 10%;">FOLIO</th>
                <th style="width: 9%;">PÓLIZA</th>
                <th style="width: 4%;">T/C</th>
                <th style="width: 35%;">EMISOR / RECEPTOR</th>
                <th style="width: 12%; text-align: right;">SUBTOTAL</th>
                <th style="width: 12%; text-align: right;">IVA</th>
                <th style="width: 12%; text-align: right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cfdis as $index => $cfdi)
                @php
                    $totalFlujo = (float) $cfdi->total;
                    if ($cfdi->tipo_comprobante == 'P' && !empty($cfdi->complementos['pagos'])) {
                        $totalFlujo = collect($cfdi->complementos['pagos'])->sum(fn($p) => (float)($p['monto'] ?? 0));
                    }
                @endphp
                <tr class="{{ $index % 2 == 0 ? '' : 'row-alt' }}">
                    <td>{{ \Carbon\Carbon::parse($cfdi->fecha_emision)->format('d/m/y') }}</td>
                    <td style="font-weight: bold;">{{ $cfdi->serie }}{{ $cfdi->folio }}</td>
                    <td>
                        @if($cfdi->poliza_referencia)
                            <span class="poliza-badge">{{ $cfdi->poliza_referencia }}</span>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $cfdi->tipo_comprobante }}">{{ $cfdi->tipo_comprobante }}</span>
                    </td>
                    <td>
                        <div style="font-weight: bold; white-space: nowrap; overflow: hidden;">{{ $cfdi->direccion == 'emitido' ? ($cfdi->nombre_receptor ?: 'PÚBLICO GENERAL') : $cfdi->nombre_emisor }}</div>
                        <div style="color: #94a3b8; font-size: 6px;">{{ $cfdi->direccion == 'emitido' ? $cfdi->rfc_receptor : $cfdi->rfc_emisor }}</div>
                    </td>
                    <td style="text-align: right;">${{ number_format($cfdi->subtotal, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($cfdi->total_impuestos_trasladados, 2) }}</td>
                    <td style="text-align: right; font-weight: bold; background: #f1f5f9;">${{ number_format($totalFlujo, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $empresa->razon_social ?? 'CLIMAS DEL DESIERTO' }} - 
        Página <span class="pagenum"></span> de <span class="pagecount"></span>
    </div>
</body>
</html>
