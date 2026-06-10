<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Liquidación de Comisiones #{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #334155;
            background: #ffffff;
            line-height: 1.4;
        }
        .container { padding: 40px; max-width: 800px; margin: 0 auto; }
        
        /* Header */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-logo { display: table-cell; width: 120px; vertical-align: top; padding-right: 15px; }
        .company-logo img { max-width: 120px; max-height: 80px; }
        .header-left { display: table-cell; vertical-align: top; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: top; }
        
        .company-name { font-size: 22px; font-weight: bold; color: #0f172a; margin-bottom: 5px; text-transform: uppercase; }
        .company-details { color: #64748b; font-size: 10px; }
        
        .document-title { font-size: 18px; font-weight: bold; color: #1e3a8a; margin-bottom: 5px; letter-spacing: 1px; }
        .document-meta { font-size: 12px; color: #475569; }
        .document-meta strong { color: #0f172a; }
        
        /* Info Blocks */
        .info-container { display: table; width: 100%; margin-bottom: 30px; }
        .info-box {
            display: table-cell;
            width: 48%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            vertical-align: top;
        }
        .info-spacer { display: table-cell; width: 4%; }
        
        .box-title { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        .info-row { margin-bottom: 4px; }
        .info-label { color: #64748b; display: inline-block; width: 80px; }
        .info-value { color: #0f172a; font-weight: bold; }
        
        /* Table */
        .table-title { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 10px; border-left: 3px solid #1e3a8a; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #1e293b; color: #ffffff; padding: 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        th.text-right { text-align: right; }
        th.text-center { text-align: center; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        td.text-right { text-align: right; }
        td.text-center { text-align: center; }
        
        .venta-row { background: #ffffff; }
        .venta-row td { font-weight: bold; color: #0f172a; }
        
        /* Subtable for items */
        .items-row td { padding: 0 !important; border-bottom: 2px solid #cbd5e1 !important; }
        .subtable { width: 100%; margin: 0; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .subtable th { background: #f1f5f9; color: #475569; padding: 6px 10px; font-size: 9px; border-bottom: 1px solid #e2e8f0; }
        .subtable td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight: normal; color: #334155; font-size: 10px; }
        .subtable tr:last-child td { border-bottom: none; }
        
        .tag-servicio { color: #7c3aed; font-weight: bold; }
        .tag-producto { color: #2563eb; font-weight: bold; }
        .comision-verde { color: #059669; font-weight: bold; }
        
        /* Totals */
        .totals-container { display: table; width: 100%; margin-bottom: 40px; }
        .totals-notes { display: table-cell; width: 60%; padding-right: 20px; vertical-align: top; }
        .totals-summary { display: table-cell; width: 40%; vertical-align: top; }
        
        .notes-box { background: #f1f5f9; padding: 15px; border-radius: 6px; font-size: 10px; color: #475569; }
        .notes-box strong { color: #0f172a; display: block; margin-bottom: 5px; }
        
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .summary-table tr:last-child td { border-bottom: none; }
        .summary-label { color: #64748b; }
        .summary-value { text-align: right; font-weight: bold; color: #0f172a; font-size: 12px; }
        
        .summary-total td { background: #1e3a8a; color: #ffffff; font-size: 14px; font-weight: bold; border-radius: 4px; }
        .summary-total .summary-label { color: #e0e7ff; }
        .summary-total .summary-value { color: #ffffff; }
        
        /* Signatures */
        .signatures { display: table; width: 100%; margin-top: 60px; text-align: center; }
        .signature-box { display: table-cell; width: 50%; padding: 0 40px; }
        .signature-line { border-top: 1px solid #0f172a; padding-top: 8px; margin-top: 40px; font-weight: bold; color: #0f172a; }
        .signature-role { color: #64748b; font-size: 10px; }
        
        /* Footer */
        .footer { margin-top: 40px; border-top: 1px solid #e2e8f0; padding-top: 15px; text-align: center; color: #94a3b8; font-size: 9px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if($empresa['logo_base64'])
            <div class="company-logo">
                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
            </div>
            @endif
            <div class="header-left">
                <div class="company-name">{{ $empresa['nombre'] ?? 'SISTEMA CDD' }}</div>
                <div class="company-details">
                    @if($empresa['razon_social'] && $empresa['razon_social'] !== $empresa['nombre'])
                        {{ $empresa['razon_social'] }}<br>
                    @endif
                    RFC: {{ $empresa['rfc'] ?? 'N/D' }}<br>
                    {{ $empresa['direccion'] ?? 'Dirección no especificada' }}<br>
                    Tel: {{ $empresa['telefono'] ?? 'N/D' }} | Email: {{ $empresa['email'] ?? 'N/D' }}
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">COMPROBANTE DE LIQUIDACIÓN</div>
                <div class="document-meta">
                    Recibo Folio: <strong>#{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                    Fecha de Emisión: <strong>{{ $pago->fecha_pago?->format('d/m/Y') ?? now()->format('d/m/Y') }}</strong><br>
                    Estado: <strong style="color: #059669;">{{ strtoupper($pago->estado) }}</strong>
                </div>
            </div>
        </div>

        <!-- Info Blocks -->
        <div class="info-container">
            <!-- Beneficiario -->
            <div class="info-box">
                <div class="box-title">Datos del Beneficiario</div>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value">{{ $pago->vendedor?->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Puesto/Rol:</span>
                    <span class="info-value">{{ $pago->vendedor_type === 'App\\Models\\User' && $pago->vendedor?->es_tecnico ? 'Técnico Instalador' : 'Ejecutivo de Ventas' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">ID Sistema:</span>
                    <span class="info-value">{{ $pago->vendedor_id }}</span>
                </div>
            </div>
            
            <div class="info-spacer"></div>
            
            <!-- Detalles del Pago -->
            <div class="info-box">
                <div class="box-title">Detalles de la Operación</div>
                <div class="info-row">
                    <span class="info-label">Periodo:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($pago->periodo_inicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($pago->periodo_fin)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Método:</span>
                    <span class="info-value">{{ ucfirst($pago->metodo_pago ?? 'Efectivo') }}</span>
                </div>
                @if($pago->referencia_pago || $pago->cuentaBancaria)
                <div class="info-row">
                    <span class="info-label">Referencia:</span>
                    <span class="info-value">
                        {{ $pago->referencia_pago ? $pago->referencia_pago . ' ' : '' }}
                        {{ $pago->cuentaBancaria ? '('.$pago->cuentaBancaria->banco.')' : '' }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Tabla de Ventas Liquidadas -->
        @if(!empty($pago->detalles_ventas) && count($pago->detalles_ventas) > 0)
        <div class="table-title">Ventas Liquidadas en este Comprobante ({{ count($pago->detalles_ventas) }})</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">Venta #</th>
                    <th width="15%" class="text-center">Fecha</th>
                    <th width="40%">Cliente</th>
                    <th width="15%" class="text-right">Monto Venta</th>
                    <th width="15%" class="text-right">Comisión</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pago->detalles_ventas as $detalle)
                <tr class="venta-row">
                    <td>{{ $detalle['numero_venta'] ?? '-' }}</td>
                    <td class="text-center">{{ isset($detalle['fecha']) ? \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/y') : '-' }}</td>
                    <td>{{ $detalle['cliente'] ?? '-' }}</td>
                    <td class="text-right">${{ number_format($detalle['total_venta'] ?? 0, 2) }}</td>
                    <td class="text-right comision-verde">${{ number_format($detalle['comision_total'] ?? 0, 2) }}</td>
                </tr>
                @if(!empty($detalle['items']))
                <tr class="items-row">
                    <td colspan="5">
                        <table class="subtable">
                            <thead>
                                <tr>
                                    <th width="50%">Concepto / Partida</th>
                                    <th width="15%" class="text-center">Tipo</th>
                                    <th width="10%" class="text-center">Cant.</th>
                                    <th width="25%" class="text-right">Comisión Base</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalle['items'] as $item)
                                <tr>
                                    <td>{{ $item['nombre'] }}</td>
                                    <td class="text-center">
                                        <span class="{{ $item['tipo'] === 'Servicio' ? 'tag-servicio' : 'tag-producto' }}">{{ $item['tipo'] }}</span>
                                    </td>
                                    <td class="text-center">{{ $item['cantidad'] }}</td>
                                    <td class="text-right comision-verde">${{ number_format($item['comision'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Totals & Notes -->
        <div class="totals-container">
            <div class="totals-notes">
                @if($pago->notas)
                <div class="notes-box">
                    <strong>Observaciones:</strong>
                    {{ $pago->notas }}
                </div>
                @endif
            </div>
            <div class="totals-summary">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Ventas Procesadas:</td>
                        <td class="summary-value">{{ $pago->num_ventas }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Monto de Ventas:</td>
                        <td class="summary-value">${{ number_format($pago->total_ventas, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="summary-label">Comisión Calculada:</td>
                        <td class="summary-value">${{ number_format($pago->monto_comision, 2) }}</td>
                    </tr>
                    <tr class="summary-total">
                        <td class="summary-label">TOTAL LIQUIDADO:</td>
                        <td class="summary-value">${{ number_format($pago->monto_pagado, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">{{ $pago->pagadoPorUser?->name ?? 'Firma Autorizada' }}</div>
                <div class="signature-role">DEPARTAMENTO DE ADMINISTRACIÓN</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">{{ $pago->vendedor?->name ?? 'Nombre del Beneficiario' }}</div>
                <div class="signature-role">RECIBÍ DE CONFORMIDAD</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Este documento constituye un comprobante interno de liquidación de comisiones.
            Emitido desde {{ $empresa['nombre'] ?? 'CDD App' }} el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }} hrs.
        </div>
    </div>
</body>
</html>

