<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago {{ $cfdi->folio }}</title>
    @php
        $moneda = $comprobante['Moneda'] ?? 'XXX';
    @endphp
    <style>
        @page { margin: 0.8cm; }
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .w-100 { width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .header-table { margin-bottom: 20px; border-bottom: 2px solid {{ $color_principal ?? '#2563eb' }}; }
        .company-name { font-size: 18px; color: {{ $color_principal ?? '#2563eb' }}; margin: 0 0 5px 0; }
        .company-details { font-size: 9px; color: #555; line-height: 1.2; }
        .invoice-box { text-align: right; }
        .invoice-box h2 { margin: 0; font-size: 14px; color: {{ $color_principal ?? '#2563eb' }}; background-color: {{ $color_principal ?? '#2563eb' }}15; padding: 6px 12px; border-radius: 4px; display: inline-block; }
        .folio-text { font-size: 14px; font-weight: bold; margin: 8px 0 2px 0; }
        .uuid-text { font-size: 8px; color: #666; font-family: monospace; }
        .section-header { background-color: #f8fafc; padding: 6px 10px; font-weight: bold; border: 1px solid #e5e7eb; border-bottom: none; color: #4b5563; text-transform: uppercase; font-size: 10px; border-radius: 4px 4px 0 0; }
        .section-content { padding: 10px; border: 1px solid #e5e7eb; border-radius: 0 0 4px 4px; margin-bottom: 15px; }
        .label { font-weight: bold; color: #6b7280; width: 110px; }
        .items-table th { background-color: {{ $color_principal ?? '#2563eb' }}; color: white; padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .items-table td { border-bottom: 1px solid #f3f4f6; padding: 8px; font-size: 8px; vertical-align: top; }
        .timbrado-table { margin-top: 15px; border: 1px solid #e5e7eb; border-radius: 6px; background-color: #f9fafb; padding: 12px; page-break-inside: avoid; width: 100%; }
        .sello-title { font-weight: bold; font-size: 7px; color: #374151; margin-top: 4px; border-bottom: 1px solid #d1d5db; text-transform: uppercase; width: auto; letter-spacing: 0.5px; }
        .sello-text { font-family: monospace; font-size: 6px; color: #4b5563; word-wrap: break-word; word-break: break-all; white-space: normal; line-height: 1.2; display: block; width: auto; margin-bottom: 5px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8px; color: #9ca3af; padding: 10px 0; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 65%; vertical-align: bottom; padding-bottom: 10px;">
                @if(!empty($empresa['logo_base64']))
                    <img src="{{ $empresa['logo_base64'] }}" style="max-height: 55px; width: auto; object-fit: contain; margin-bottom: 8px;">
                @endif
                <div class="company-name">{{ $emisor['Nombre'] }}</div>
                <div class="company-details">
                    <strong>RFC:</strong> {{ $emisor['Rfc'] }}<br>
                    <strong>Régimen:</strong> {{ $emisor['RegimenFiscal'] }}<br>
                    <strong>Lugar de Expedición:</strong> {{ $comprobante['LugarExpedicion'] }}
                </div>
            </td>
            <td style="width: 35%; vertical-align: bottom; text-align: right; padding-bottom: 10px;">
                <div class="invoice-box">
                    <h2>COMPLEMENTO DE PAGO</h2>
                    <div class="folio-text">{{ $cfdi->serie }}-{{ $cfdi->folio }}</div>
                    <div class="uuid-text"><strong>FOLIO FISCAL (UUID):</strong><br>{{ $cfdi->uuid }}</div>
                    <div style="margin-top: 8px; font-size: 10px;"><strong>Emisión:</strong> {{ $comprobante['Fecha'] }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-header">Datos del Cliente</div>
    <div class="section-content">
        <table class="data-table">
            <tr>
                <td class="label">RFC:</td>
                <td>{{ $receptor['Rfc'] }}</td>
                <td class="label">Nombre:</td>
                <td>{{ $receptor['Nombre'] }}</td>
            </tr>
            <tr>
                <td class="label">Uso CFDI:</td>
                <td>{{ $receptor['UsoCFDI'] }}</td>
                <td class="label">Régimen Fiscal:</td>
                <td>{{ $receptor['RegimenFiscalReceptor'] }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio Fiscal:</td>
                <td colspan="3">{{ $receptor['DomicilioFiscalReceptor'] }}</td>
            </tr>
        </table>
    </div>

    @if(isset($comprobante['ComplementoPago']['Pago']))
        @foreach($comprobante['ComplementoPago']['Pago'] as $pago)
            @php
                $pagoAttrs = $pago['@attributes'] ?? $pago;
            @endphp
            <div class="section-header">Información del Pago</div>
            <div class="section-content">
                <table class="data-table">
                    <tr>
                        <td class="label">Fecha Pago:</td>
                        <td>{{ $pagoAttrs['FechaPago'] ?? '' }}</td>
                        <td class="label">Forma Pago:</td>
                        <td>{{ $pagoAttrs['FormaDePagoP'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Moneda:</td>
                        <td>{{ $pagoAttrs['MonedaP'] ?? '' }}</td>
                        <td class="label">Monto:</td>
                        <td><strong>${{ number_format((float)($pagoAttrs['Monto'] ?? 0), 2) }}</strong></td>
                    </tr>
                    @if(isset($pagoAttrs['RfcEmisorCtaBen']))
                    <tr>
                        <td class="label">RFC Banco:</td>
                        <td>{{ $pagoAttrs['RfcEmisorCtaBen'] }}</td>
                        <td class="label">Cta. Benef.:</td>
                        <td>{{ $pagoAttrs['CtaBeneficiario'] ?? '' }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            @php
                // Detectar documentos relacionados (pueden venir en namespace pago10 o pago20)
                $docs = [];
                if (isset($pago['pago10:DoctoRelacionado'])) $docs = $pago['pago10:DoctoRelacionado'];
                elseif (isset($pago['pago20:DoctoRelacionado'])) $docs = $pago['pago20:DoctoRelacionado'];
                elseif (isset($pago['DoctoRelacionado'])) $docs = $pago['DoctoRelacionado'];

                // Normalizar a array si es uno solo
                if (isset($docs['@attributes'])) $docs = [$docs];
            @endphp

            @if(!empty($docs))
            <div class="section-header" style="background-color: #e2e8f0;">Documentos Relacionados</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Folio/UUID</th>
                        <th>Moneda</th>
                        <th>Método Pago</th>
                        <th class="text-right">Saldo Ant.</th>
                        <th class="text-right">Pagado</th>
                        <th class="text-right">Saldo Insoluto</th>
                        <th class="text-center">Parcialidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($docs as $doc)
                        @php $docAttrs = $doc['@attributes'] ?? $doc; @endphp
                        <tr>
                            <td>
                                {{ $docAttrs['Serie'] ?? '' }} {{ $docAttrs['Folio'] ?? '' }}<br>
                                <span style="font-size: 7px;">{{ $docAttrs['IdDocumento'] ?? '' }}</span>
                            </td>
                            <td class="text-center">{{ $docAttrs['MonedaDR'] ?? '' }}</td>
                            <td class="text-center">{{ $docAttrs['MetodoDePagoDR'] ?? '' }}</td>
                            <td class="text-right">${{ number_format((float)($docAttrs['ImpSaldoAnt'] ?? 0), 2) }}</td>
                            <td class="text-right">${{ number_format((float)($docAttrs['ImpPagado'] ?? 0), 2) }}</td>
                            <td class="text-right">${{ number_format((float)($docAttrs['ImpSaldoInsoluto'] ?? 0), 2) }}</td>
                            <td class="text-center">{{ $docAttrs['NumParcialidad'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
            <br>
        @endforeach
    @endif

    <div class="timbrado-table">
        <table width="100%" cellpadding="0" cellspacing="0" style="border: none;">
            <tr>
                <td style="width: 120px; vertical-align: top; padding-right: 10px; border: none;">
                    @if(!empty($qr_base64))
                        <img src="{{ $qr_base64 }}" style="width: 110px; height: 110px; border: none;">
                    @else
                        <img src="https://quickchart.io/qr?text={{ urlencode($qr_url ?? '') }}&size=150" style="width: 110px; height: 110px; border: 1px solid #ddd;">
                    @endif
                </td>
                <td style="vertical-align: top; border: none;">
                    <div class="sello-title">Sello Digital del Emisor</div>
                    <div class="sello-text">{{ chunk_split($data['selloCFDI'] ?? '', 130, ' ') }}</div>
                    <div class="sello-title">Sello Digital del SAT</div>
                    <div class="sello-text">{{ chunk_split($data['selloSAT'] ?? '', 130, ' ') }}</div>
                    <div class="sello-title">Cadena Original del SAT</div>
                    <div class="sello-text">{{ chunk_split($data['cadenaOriginal'] ?? '', 130, ' ') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Este documento es una representación impresa de un CFDI de Pagos (Versión 4.0).<br>
        <strong>Gracias por su preferencia - Climas del Desierto</strong>
    </div>
</body>
</html>
