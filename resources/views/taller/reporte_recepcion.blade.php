<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Recepción - {{ $orden->folio }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #334155;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 15px;
            background-color: #fff;
        }
        .header {
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 22px;
            font-weight: 900;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: -1px;
            margin: 0;
        }
        .report-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 2px;
        }
        .folio-badge {
            background-color: #fff7ed;
            border: 2px solid #fbbf24;
            padding: 8px 15px;
            border-radius: 12px;
            text-align: center;
        }
        .folio-label {
            font-size: 8px;
            font-weight: 900;
            color: #b45309;
            text-transform: uppercase;
        }
        .folio-number {
            font-size: 18px;
            font-weight: 900;
            color: #1e293b;
        }
        .grid {
            width: 100%;
            margin-bottom: 10px;
        }
        .grid td {
            vertical-align: top;
            padding: 5px;
        }
        .section-card {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 10px;
        }
        .section-title {
            font-size: 9px;
            font-weight: 900;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .info-table {
            width: 100%;
        }
        .info-table td {
            padding: 2px 0;
        }
        .label {
            font-weight: 800;
            color: #94a3b8;
            font-size: 8px;
            text-transform: uppercase;
            width: 80px;
        }
        .value {
            font-weight: 600;
            color: #1e293b;
        }
        .highlight-box {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 10px;
            color: #475569;
            font-size: 9px;
            min-height: 30px;
        }
        .signature-area {
            margin-top: 20px;
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #1e293b;
            width: 220px;
            margin: 40px auto 0;
            padding-top: 5px;
            font-weight: 900;
            font-size: 10px;
            text-transform: uppercase;
        }
        .signature-img {
            max-height: 60px;
            margin-bottom: -40px;
            position: relative;
            z-index: 10;
        }
        .terms {
            margin-top: 15px;
            font-size: 7px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.4;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 65%;">
                        <h1 class="company-name">Climas del Desierto</h1>
                        <p class="report-title">Recepción de Servicio Técnico</p>
                        <div style="font-size: 10px; color: #94a3b8; margin-top: 10px;">
                            <strong>Fecha:</strong> {{ $orden->fecha_recepcion->format('d/m/Y H:i') }}<br>
                            <strong>Atendido por:</strong> {{ $orden->recepcionista->name }}
                        </div>
                    </td>
                    <td style="width: 35%; text-align: right;">
                        <div class="folio-badge">
                            <div class="folio-label">Folio de Seguimiento</div>
                            <div class="folio-number">{{ $orden->folio }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="grid">
            <tr>
                <td style="width: 50%;">
                    <div class="section-card">
                        <h2 class="section-title">Información del Cliente</h2>
                        <table class="info-table">
                            <tr>
                                <td class="label">Nombre:</td>
                                <td class="value">{{ $orden->cliente->nombre_razon_social ?? $orden->nombre_cliente }}</td>
                            </tr>
                            <tr>
                                <td class="label">Teléfono:</td>
                                <td class="value">{{ $orden->telefono_cliente }}</td>
                            </tr>
                            <tr>
                                <td class="label">Correo:</td>
                                <td class="value">{{ $orden->cliente->email ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="section-card">
                        <h2 class="section-title">Datos del Equipo</h2>
                        <table class="info-table">
                            <tr>
                                <td class="label">Marca/Mod:</td>
                                <td class="value">{{ $orden->equipo_marca }} {{ $orden->equipo_modelo }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. Serie:</td>
                                <td class="value">{{ $orden->equipo_serie ?: 'S/N' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Accesorios:</td>
                                <td class="value">{{ is_array($orden->accesorios) ? implode(', ', $orden->accesorios) : ($orden->accesorios ?: 'Ninguno') }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-card" style="margin-bottom: 20px;">
            <h2 class="section-title">Diagnóstico de Recepción</h2>
            <div class="grid" style="margin-bottom: 0;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; padding: 0 10px 0 0;">
                            <p class="label" style="margin-bottom: 5px;">Falla Reportada:</p>
                            <div class="highlight-box">{{ $orden->problema_reportado }}</div>
                        </td>
                        <td style="width: 50%; padding: 0 0 0 10px;">
                            <p class="label" style="margin-bottom: 5px;">Estado Físico del Equipo:</p>
                            <div class="highlight-box">{{ $orden->estado_fisico ?: 'Se recibe para revisión general.' }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="section-card" style="margin-bottom: 20px; background-color: #fffbeb; border-color: #fef3c7;">
            <h2 class="section-title" style="color: #b45309; border-color: #fde68a;">Resumen Financiero</h2>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 33%;">
                        @if($orden->venta)
                            <span class="label">Total de Venta:</span><br>
                            <span class="value" style="font-size: 14px; color: #059669;">${{ number_format($orden->venta->total, 2) }}</span>
                            <div style="font-size: 7px; color: #059669; font-weight: bold; margin-top: 2px;">Folio Venta: {{ $orden->venta->numero_venta }}</div>
                        @else
                            <span class="label">Costo Estimado:</span><br>
                            <span class="value" style="font-size: 14px; color: #b45309;">${{ number_format($orden->costo_estimado ?: 0, 2) }}</span>
                        @endif
                    </td>
                    <td style="width: 33%;">
                        <span class="label">Fecha Compromiso:</span><br>
                        <span class="value">{{ $orden->fecha_compromiso ? $orden->fecha_compromiso->format('d/m/Y') : 'Pendiente' }}</span>
                    </td>
                    <td style="width: 33%;">
                        <span class="label">Estado Actual:</span><br>
                        <span class="value" style="text-transform: uppercase;">{{ str_replace('_', ' ', $orden->estado) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="signature-area">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%; text-align: center; padding-top: 15px;">
                        @if($orden->firma_recepcion)
                            @php
                                $path = storage_path('app/public/taller/firmas/' . $orden->firma_recepcion);
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_exists($path) ? file_get_contents($path) : null;
                                $base64 = $data ? 'data:image/' . $type . ';base64,' . base64_encode($data) : null;
                            @endphp
                            @if($base64)
                                <img src="{{ $base64 }}" class="signature-img">
                            @endif
                        @endif
                        <div class="signature-line">Firma Cliente (Recepción)</div>
                    </td>
                    <td style="width: 50%; text-align: center; padding-top: 15px;">
                        @if($orden->firma_entrega)
                            @php
                                $pathE = storage_path('app/public/taller/firmas/' . $orden->firma_entrega);
                                $typeE = pathinfo($pathE, PATHINFO_EXTENSION);
                                $dataE = file_exists($pathE) ? file_get_contents($pathE) : null;
                                $base64E = $dataE ? 'data:image/' . $typeE . ';base64,' . base64_encode($dataE) : null;
                            @endphp
                            @if($base64E)
                                <img src="{{ $base64E }}" class="signature-img">
                            @endif
                        @endif
                        <div class="signature-line">Firma Cliente (Entrega)</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center; padding-top: 15px;">
                        <div style="height: 20px;"></div>
                        <div class="signature-line" style="width: 300px;">{{ $orden->tecnico->name ?? $orden->recepcionista->name }}<br><span style="font-size: 8px; font-weight: 400;">Firma de Responsable / Técnico</span></div>
                    </td>
                </tr>
            </table>
            <p style="font-size: 8px; color: #94a3b8; margin-top: 15px;">Al firmar, el cliente acepta los términos y condiciones de servicio descritos en este documento.</p>
        </div>

        <div class="folio-footer" style="text-align: center; margin-top: 30px; border-top: 1px dashed #e2e8f0; padding-top: 20px;">
            <div style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Folio de Referencia</div>
            <div style="font-size: 24px; font-weight: 900; color: #1e293b; letter-spacing: 2px;">{{ $orden->folio }}</div>
        </div>

        <div class="terms">
            <strong>Términos:</strong> Diagnóstico estimado 24-48h. Equipos no reclamados en 30 días causan abandono. 
            Garantía según reparación. Es obligatorio presentar este folio para entrega.
        </div>

        <div class="footer">
            Climas del Desierto - Calle Falsa 123, Col. Centro - Hermosillo, Sonora<br>
            Tel: 662-460-6840 - www.climasdeldesierto.com
        </div>
    </div>
</body>
</html>
