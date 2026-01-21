<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>REP {{ $cfdi->folio }}</title>
    <style>
        @page {
            margin: 0.8cm;
        }

        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .header-table {
            margin-bottom: 20px;
            border-bottom: 2px solid
                {{ $color_principal ?? '#7c3aed' }}
            ;
        }

        .company-name {
            font-size: 18px;
            color:
                {{ $color_principal ?? '#7c3aed' }}
            ;
            margin: 0 0 5px 0;
        }

        .company-details {
            font-size: 9px;
            color: #555;
            line-height: 1.2;
        }

        .invoice-box {
            text-align: right;
        }

        .invoice-box h2 {
            margin: 0;
            font-size: 12px;
            color:
                {{ $color_principal ?? '#7c3aed' }}
            ;
            background-color:
                {{ $color_principal ?? '#7c3aed' }}
                15;
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
        }

        .folio-text {
            font-size: 14px;
            font-weight: bold;
            margin: 8px 0 2px 0;
        }

        .uuid-text {
            font-size: 8px;
            color: #666;
            font-family: monospace;
        }

        .section-header {
            background-color: #f8fafc;
            padding: 6px 10px;
            font-weight: bold;
            border: 1px solid #e5e7eb;
            border-bottom: none;
            color: #4b5563;
            text-transform: uppercase;
            font-size: 10px;
            border-radius: 4px 4px 0 0;
        }

        .section-content {
            padding: 10px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 4px 4px;
            margin-bottom: 15px;
        }

        .label {
            font-weight: bold;
            color: #6b7280;
            width: 130px;
        }

        .payment-box {
            background-color: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .payment-title {
            font-size: 14px;
            font-weight: bold;
            color: #15803d;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .payment-amount {
            font-size: 28px;
            font-weight: bold;
            color: #15803d;
        }

        .related-doc {
            background-color: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 12px;
            margin-top: 15px;
        }

        .related-title {
            font-size: 10px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .timbrado-table {
            margin-top: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background-color: #f9fafb;
            padding: 12px;
            page-break-inside: avoid;
            width: 100%;
        }

        .sello-title {
            font-weight: bold;
            font-size: 7px;
            color: #374151;
            margin-top: 4px;
            border-bottom: 1px solid #d1d5db;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sello-text {
            font-family: monospace;
            font-size: 6px;
            color: #4b5563;
            word-wrap: break-word;
            word-break: break-all;
            white-space: normal;
            line-height: 1.2;
            display: block;
            margin-bottom: 5px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            padding: 10px 0;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>

<body>
    @if(in_array(($cfdi->estatus ?? ''), ['cancelado', 'cancelado_con_acuse'], true))
        <div
            style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-35deg); font-size: 90px; font-weight: bold; color: rgba(220, 38, 38, 0.20); z-index: 1000; pointer-events: none; letter-spacing: 10px; white-space: nowrap;">
            CANCELADO
        </div>
        <div
            style="background-color: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
            <div style="font-weight: bold; color: #dc2626; font-size: 12px; margin-bottom: 5px;">[!] DOCUMENTO CANCELADO
            </div>
            @php
                $motivos = [
                    '01' => '01 - Comprobantes emitidos con errores con relación',
                    '02' => '02 - Comprobantes emitidos con errores sin relación',
                    '03' => '03 - No se llevó a cabo la operación',
                    '04' => '04 - Operación nominativa relacionada en factura global'
                ];
                $motivoClave = $cfdi->motivo_cancelacion ?? '';
                $motivoDescripcion = $motivos[$motivoClave] ?? $motivoClave;
            @endphp
            <div style="font-size: 10px; color: #991b1b;">
                <strong>Motivo:</strong> {{ $motivoDescripcion }}<br>
                <strong>Fecha de Cancelación:</strong>
                {{ $cfdi->fecha_cancelacion ? \Carbon\Carbon::parse($cfdi->fecha_cancelacion)->format('d/m/Y H:i') : 'N/A' }}
                @if($cfdi->folio_sustitucion)
                    <br><strong>Sustituido por:</strong> {{ $cfdi->folio_sustitucion }}
                @endif
            </div>
        </div>
    @endif
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: bottom; padding-bottom: 10px;">
                @if(!empty($empresa['logo_base64']))
                    <img src="{{ $empresa['logo_base64'] }}"
                        style="max-height: 55px; width: auto; object-fit: contain; margin-bottom: 8px;">
                @endif
                <div class="company-name">{{ $emisor['Nombre'] }}</div>
                <div class="company-details">
                    <strong>RFC:</strong> {{ $emisor['Rfc'] }}<br>
                    <strong>Régimen:</strong> {{ $emisor['RegimenFiscal'] }} -
                    {{ $emisor['RegimenFiscalNombre'] ?? '' }}<br>
                    <strong>Lugar de Expedición:</strong> {{ $comprobante['LugarExpedicion'] }} (C.P.)
                </div>
            </td>
            <td style="width: 35%; vertical-align: bottom; text-align: right; padding-bottom: 10px;">
                <div class="invoice-box">
                    <h2>COMPLEMENTO DE PAGO (REP 2.0)</h2>
                    <div class="folio-text">{{ $cfdi->serie }}-{{ $cfdi->folio }}</div>
                    <div class="uuid-text"><strong>FOLIO FISCAL (UUID):</strong><br>{{ $cfdi->uuid }}</div>
                    <div style="margin-top: 8px; font-size: 10px;"><strong>Emisión:</strong> {{ $comprobante['Fecha'] }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-header">Datos del Receptor / Cliente</div>
    <div class="section-content">
        <table>
            <tr>
                <td class="label">RFC:</td>
                <td style="width: 35%;">{{ $receptor['Rfc'] }}</td>
                <td class="label">Nombre:</td>
                <td>{{ $receptor['Nombre'] }}</td>
            </tr>
            <tr>
                <td class="label">Uso CFDI:</td>
                <td>{{ $receptor['UsoCFDI'] }} - {{ $receptor['UsoCFDINombre'] ?? 'Pagos' }}</td>
                <td class="label">Régimen Fiscal:</td>
                <td>{{ $receptor['RegimenFiscalReceptor'] ?? '' }} -
                    {{ $receptor['RegimenFiscalReceptorNombre'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio Fiscal:</td>
                <td colspan="3">C.P. {{ $receptor['DomicilioFiscalReceptor'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-box">
        <div class="payment-title">✓ Información del Pago Recibido</div>
        <table>
            <tr>
                <td style="width: 50%;">
                    <strong style="color: #15803d;">Monto del Pago:</strong><br>
                    <span class="payment-amount">${{ number_format($pago['Monto'] ?? 0, 2) }} MXN</span>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <table style="font-size: 10px;">
                        <tr>
                            <td class="label">Fecha de Pago:</td>
                            <td>{{ $pago['FechaPago'] ?? $comprobante['Fecha'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Forma de Pago:</td>
                            <td>{{ $pago['FormaDePagoP'] ?? '' }} - {{ $pago['FormaPagoNombre'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Moneda:</td>
                            <td>{{ $pago['MonedaP'] ?? 'MXN' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($doctoRelacionado))
        <div class="related-doc">
            <div class="related-title">📄 Documento Relacionado (Factura Original)</div>
            <table style="font-size: 10px;">
                <tr>
                    <td class="label">UUID Factura:</td>
                    <td colspan="3" style="font-family: monospace; font-size: 9px;">
                        {{ $doctoRelacionado['IdDocumento'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Serie/Folio:</td>
                    <td>{{ $doctoRelacionado['Serie'] ?? '' }}{{ $doctoRelacionado['Folio'] ?? '' }}</td>
                    <td class="label">Parcialidad:</td>
                    <td>{{ $doctoRelacionado['NumParcialidad'] ?? '1' }}</td>
                </tr>
                <tr>
                    <td class="label">Saldo Anterior:</td>
                    <td>${{ number_format($doctoRelacionado['ImpSaldoAnt'] ?? 0, 2) }}</td>
                    <td class="label">Importe Pagado:</td>
                    <td>${{ number_format($doctoRelacionado['ImpPagado'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Saldo Insoluto:</td>
                    <td colspan="3"><strong>${{ number_format($doctoRelacionado['ImpSaldoInsoluto'] ?? 0, 2) }}</strong>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="timbrado-table" style="position: relative;">
        <table width="100%" cellpadding="0" cellspacing="0" style="border: none;">
            <tr>
                <td style="width: 120px; vertical-align: top; padding-right: 10px; border: none;">
                    @if(!empty($qr_base64))
                        <img src="{{ $qr_base64 }}" style="width: 110px; height: 110px; border: none;">
                    @else
                        <img src="https://quickchart.io/qr?text={{ urlencode($qr_url ?? '') }}&size=150"
                            style="width: 110px; height: 110px; border: 1px solid #ddd;">
                    @endif
                </td>

                <td style="vertical-align: top; border: none;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border: none; margin-bottom: 5px;">
                        <tr>
                            <td style="width: 50%; vertical-align: top; padding-right: 5px; border: none;">
                                <div class="sello-title" style="margin-top: 0;">No. Certificado SAT</div>
                                <div class="sello-text">{{ $data['noCertificadoSAT'] ?? '' }}</div>
                            </td>
                            <td style="width: 50%; vertical-align: top; border: none;">
                                <div class="sello-title" style="margin-top: 0;">Fecha de Certificación</div>
                                <div class="sello-text">{{ $data['fechaTimbrado'] ?? '' }}</div>
                            </td>
                        </tr>
                    </table>

                    <div class="sello-title">Sello Digital del Emisor</div>
                    <div class="sello-text" style="margin-bottom: 4px;">
                        {{ chunk_split($data['selloCFDI'] ?? '', 130, ' ') }}</div>

                    <div class="sello-title">Sello Digital del SAT</div>
                    <div class="sello-text" style="margin-bottom: 4px;">
                        {{ chunk_split($data['selloSAT'] ?? '', 130, ' ') }}</div>

                    <div class="sello-title">Cadena Original del SAT</div>
                    <div class="sello-text">{{ chunk_split($data['cadenaOriginal'] ?? '', 130, ' ') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento es una representación impresa de un CFDI de Pago (REP 2.0 - Versión 4.0).<br>
        <strong>Gracias por su pago - Asistencia Vircom</strong>
    </div>
</body>

</html>