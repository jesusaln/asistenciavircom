<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Factura {{ $cfdi->folio }}</title>
    @php
        use App\Helpers\SatCatalogosHelper;
        $moneda = $comprobante['Moneda'] ?? 'MXN';
    @endphp
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

        /* Layout principal con tablas para estabilidad en DomPDF */
        .w-100 {
            width: 100%;
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
                {{ $color_principal ?? '#2563eb' }}
            ;
        }

        .company-name {
            font-size: 18px;
            color:
                {{ $color_principal ?? '#2563eb' }}
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
            font-size: 14px;
            color:
                {{ $color_principal ?? '#2563eb' }}
            ;
            background-color:
                {{ $color_principal ?? '#2563eb' }}
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
            width: 110px;
        }

        .items-table th {
            background-color:
                {{ $color_principal ?? '#2563eb' }}
            ;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        .items-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 8px;
            font-size: 9px;
            vertical-align: top;
        }

        .totals-table td {
            padding: 4px 8px;
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 13px;
            background-color:
                {{ $color_principal ?? '#2563eb' }}
                15;
        }

        .bank-section {
            margin-top: 15px;
            padding: 10px;
            background-color: #e8f4fd;
            border-left: 4px solid
                {{ $color_principal ?? '#2563eb' }}
            ;
            border-radius: 4px;
        }

        .bank-title {
            margin: 0 0 5px 0;
            color:
                {{ $color_principal ?? '#2563eb' }}
            ;
            font-size: 10px;
            font-weight: bold;
        }

        .warranty-section {
            margin-top: 10px;
            padding: 8px;
            background-color: #f5f5f5;
            border-radius: 4px;
            font-size: 7px;
            line-height: 1.3;
            color: #555;
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
            width: auto;
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
            width: auto;
            margin-bottom: 5px;
        }

        .qr-container {
            float: left;
            width: 110px;
            margin-right: 15px;
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
                    <strong>Régimen:</strong> {{ $emisor['RegimenFiscal'] }} - {{ SatCatalogosHelper::regimenFiscal($emisor['RegimenFiscal'] ?? '') }}<br>
                    <strong>Lugar de Expedición:</strong> {{ $comprobante['LugarExpedicion'] }} (C.P.)
                </div>
            </td>
            <td style="width: 35%; vertical-align: bottom; text-align: right; padding-bottom: 10px;">
                <div class="invoice-box">
                    <h2>FACTURA (CFDI 4.0)</h2>
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
        <table class="data-table">
            <tr>
                <td class="label">RFC:</td>
                <td style="width: 35%;">{{ $receptor['Rfc'] }}</td>
                <td class="label">Nombre:</td>
                <td>{{ $receptor['Nombre'] }}</td>
            </tr>
            <tr>
                <td class="label">Uso CFDI:</td>
                <td>{{ $receptor['UsoCFDI'] }} - {{ SatCatalogosHelper::usoCfdi($receptor['UsoCFDI'] ?? '') }}</td>
                <td class="label">Régimen Fiscal:</td>
                <td>{{ $receptor['RegimenFiscalReceptor'] }} - {{ SatCatalogosHelper::regimenFiscal($receptor['RegimenFiscalReceptor'] ?? '') }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio Fiscal:</td>
                <td colspan="3">C.P. {{ $receptor['DomicilioFiscalReceptor'] }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 12%;">Clave SAT</th>
                <th style="width: 8%;" class="text-center">Cant.</th>
                <th style="width: 10%;">Unidad</th>
                <th>Descripción de Producto / Servicio</th>
                <th style="width: 15%; text-align: right;">P. Unitario</th>
                <th style="width: 15%; text-align: right;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comprobante['Conceptos'] as $con)
                <tr>
                    <td class="text-center">{{ $con['ClaveProdServ'] }}</td>
                    <td class="text-center">{{ number_format($con['Cantidad'], 2) }}</td>
                    <td>{{ $con['ClaveUnidad'] }}</td>
                    <td>{{ $con['Descripcion'] }}</td>
                    <td class="text-right">${{ number_format($con['ValorUnitario'], 2) }}</td>
                    <td class="text-right">${{ number_format($con['Importe'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 15px;">
        <tr>
            <td style="width: 55%; vertical-align: top; font-size: 9px; line-height: 1.5;">
                <div style="background-color: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e5e7eb;">
                    <strong>FORMA DE PAGO:</strong> {{ $comprobante['FormaPago'] ?? '' }} - {{ SatCatalogosHelper::formaPago($comprobante['FormaPago'] ?? '') }}<br>
                    <strong>MÉTODO DE PAGO:</strong> {{ $comprobante['MetodoPago'] ?? '' }} - {{ SatCatalogosHelper::metodoPago($comprobante['MetodoPago'] ?? '') }}<br>
                    <strong>MONEDA:</strong> {{ $moneda }}
                </div>

                @php $configuracion = \App\Models\EmpresaConfiguracion::getConfig(); @endphp
                @if($configuracion->banco || $configuracion->clabe)
                    <div class="bank-section">
                        <div class="bank-title">DATOS PARA DEPÓSITO O TRANSFERENCIA</div>
                        <table style="font-size: 9px;">
                            <tr>
                                <td><strong>Titular:</strong> {{ $configuracion->nombre_titular }}</td>
                                <td><strong>CLABE:</strong> {{ $configuracion->clabe }}</td>
                            </tr>
                        </table>
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="totals-table">
                    <tr>
                        <td style="color: #666;">Subtotal:</td>
                        <td class="bold">${{ number_format($comprobante['SubTotal'], 2) }}</td>
                    </tr>
                    @if(isset($comprobante['Descuento']) && $comprobante['Descuento'] > 0)
                        <tr>
                            <td style="color: #666;">Descuento:</td>
                            <td class="bold">-${{ number_format($comprobante['Descuento'], 2) }}</td>
                        </tr>
                    @endif
                    @php
                        $impuestosTraslados = null;
                        $totalImpTrasladados = null;
                        
                        if (isset($comprobante['Impuestos'])) {
                            $imp = $comprobante['Impuestos'];
                            if (isset($imp['@attributes']['TotalImpuestosTrasladados'])) {
                                $totalImpTrasladados = (float) $imp['@attributes']['TotalImpuestosTrasladados'];
                            } elseif (isset($imp['TotalImpuestosTrasladados'])) {
                                $totalImpTrasladados = (float) $imp['TotalImpuestosTrasladados'];
                            }
                            
                            if (isset($imp['cfdi:Traslados']['cfdi:Traslado'])) {
                                $impuestosTraslados = $imp['cfdi:Traslados']['cfdi:Traslado'];
                            } elseif (isset($imp['Traslados'])) {
                                $impuestosTraslados = $imp['Traslados'];
                            }
                        }
                    @endphp
                    @if($impuestosTraslados)
                        @php 
                            if (isset($impuestosTraslados['@attributes']) || isset($impuestosTraslados['Impuesto'])) {
                                $impuestosTraslados = [$impuestosTraslados];
                            }
                        @endphp
                        @foreach($impuestosTraslados as $t)
                            @php 
                                $tData = $t['@attributes'] ?? $t;
                                $impCode = $tData['Impuesto'] ?? '002';
                                $tasa = isset($tData['TasaOCuota']) ? (float)$tData['TasaOCuota'] : 0.16;
                                $importe = isset($tData['Importe']) ? (float)$tData['Importe'] : 0;
                            @endphp
                            <tr>
                                <td style="color: #666;">{{ SatCatalogosHelper::impuesto($impCode) }} ({{ number_format($tasa * 100, 0) }}%):</td>
                                <td class="bold">${{ number_format($importe, 2) }}</td>
                            </tr>
                        @endforeach
                    @elseif($totalImpTrasladados && $totalImpTrasladados > 0)
                        <tr>
                            <td style="color: #666;">IVA (16%):</td>
                            <td class="bold">${{ number_format($totalImpTrasladados, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td style="padding: 10px !important;">TOTAL:</td>
                        <td style="padding: 10px !important;">${{ number_format($comprobante['Total'], 2) }}
                            {{ $moneda }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="warranty-section">
        <strong>PÓLIZA DE GARANTÍA:</strong> Equipos: 365 días | Partes eléctricas: 3 meses.
        Soporte técnico: <strong>662-460-6840</strong> o <strong>climasdeldesierto.com/soporte</strong>.
        Para hacer válida la garantía es indispensable presentar este comprobante y cumplir con los requisitos de uso
        adecuado.
    </div>

    <div class="timbrado-table" style="position: relative;">
        <table width="100%" cellpadding="0" cellspacing="0" style="border: none;">
            <tr>
                <!-- Columna QR: Ancho fijo, alineada arriba -->
                <td style="width: 120px; vertical-align: top; padding-right: 10px; border: none;">
                    @if(!empty($qr_base64))
                        <img src="{{ $qr_base64 }}" style="width: 110px; height: 110px; border: none;">
                    @else
                        <img src="https://quickchart.io/qr?text={{ urlencode($qr_url ?? '') }}&size=150"
                            style="width: 110px; height: 110px; border: 1px solid #ddd;">
                    @endif
                </td>

                <!-- Columna Datos: Ocupa el resto, alineada arriba -->
                <td style="vertical-align: top; border: none;">

                    <!-- Tabla interna para Certificados (2 columnas) -->
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

                    <!-- Bloques de Texto apilados -->
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
        Este documento es una representación impresa de un CFDI (Versión 4.0).<br>
        <strong>Gracias por su preferencia - Climas del Desierto</strong>
    </div>
</body>

</html>