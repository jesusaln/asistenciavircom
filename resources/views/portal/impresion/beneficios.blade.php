<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe de Beneficios y Ahorro - Póliza #{{ $poliza->folio }}</title>
    <style>
        @page {
            margin: 0;
        }
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            color: #374151; 
            margin: 0; 
            padding: 0; 
            font-size: 11px; 
            background-color: #ffffff;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 10px;
            background: {{ $empresa['color_principal'] ?? '#1e40af' }};
        }
        .content {
            margin-left: 30px;
            padding: 40px;
        }
        .header { 
            border-bottom: 2px solid #f3f4f6; 
            padding-bottom: 20px; 
            margin-bottom: 30px; 
        }
        .logo-text { 
            font-size: 24px; 
            font-weight: bold; 
            color: {{ $empresa['color_principal'] ?? '#1e40af' }}; 
            margin: 0;
        }
        .report-type {
            font-size: 9px;
            font-weight: bold;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        h1 { 
            font-size: 24px; 
            color: #111827; 
            margin: 5px 0 0 0; 
            font-weight: 800;
        }
        
        .client-info {
            background-color: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #f3f4f6;
        }
        .client-info table {
            width: 100%;
        }
        .info-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: bold;
        }
        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
        }

        .card { 
            margin-bottom: 30px; 
        }
        .section-title { 
            font-size: 13px; 
            font-weight: bold; 
            color: #111827; 
            margin-bottom: 15px; 
            padding-left: 10px;
            border-left: 4px solid {{ $empresa['color_principal'] ?? '#1e40af' }};
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .table th { 
            text-align: left; 
            padding: 12px 8px; 
            background: #f8fafc; 
            color: #475569; 
            font-size: 9px; 
            text-transform: uppercase; 
            border-bottom: 2px solid #e2e8f0; 
        }
        .table td { 
            padding: 12px 8px; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        .saving-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 16px;
            padding: 30px;
            color: white;
            text-align: center;
            margin-top: 20px;
        }
        .saving-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 2px;
            opacity: 0.9;
        }
        .saving-value {
            font-size: 32px;
            font-weight: 800;
            margin: 10px 0;
        }
        .saving-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .benefit-item {
            display: inline-block;
            width: 45%;
            margin-bottom: 15px;
            vertical-align: top;
        }
        .benefit-icon {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 2px;
            margin-right: 8px;
        }
        
        .footer { 
            text-align: center; 
            color: #9ca3af; 
            font-size: 9px; 
            margin-top: 50px; 
            border-top: 1px solid #f3f4f6; 
            padding-top: 20px; 
        }
        .price-list {
            color: #9ca3af;
            font-size: 9px;
            text-decoration: line-through;
        }
    </style>
</head>
<body>
    <div class="sidebar"></div>
    
    <div class="content">
        <div class="header">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="border: none; padding: 0;">
                        <p class="logo-text">{{ $empresa['nombre_comercial_config'] ?? 'Vircom' }}</p>
                        <p class="report-type">Análisis de Retorno de Inversión</p>
                    </td>
                    <td style="border: none; padding: 0; text-align: right;">
                        <span class="report-type">Documento Folio</span>
                        <h1>P{{ str_pad($poliza->id, 5, '0', STR_PAD_LEFT) }}</h1>
                    </td>
                </tr>
            </table>
        </div>

        <div class="client-info">
            <table>
                <tr>
                    <td style="width: 50%;">
                        <div class="info-label">Suscrito a nombre de:</div>
                        <div class="info-value">{{ $cliente->nombre_razon_social }}</div>
                    </td>
                    <td style="width: 25%;">
                        <div class="info-label">Plan Activo:</div>
                        <div class="info-value">{{ $poliza->nombre }}</div>
                    </td>
                    <td style="width: 25%; text-align: right;">
                        <div class="info-label">Vigencia hasta:</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <div class="section-title">Valor de Mercado vs. Costo de Póliza</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción del Servicio</th>
                        <th class="text-right">Precio Mercado</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Valor Total Real</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalValorReal = 0; @endphp
                    @foreach($poliza->servicios as $servicio)
                        @php 
                            $precioLista = $servicio->precio;
                            $subtotalReal = $precioLista * $servicio->pivot->cantidad;
                            $totalValorReal += $subtotalReal;
                        @endphp
                        <tr>
                            <td>
                                <div class="font-bold">{{ $servicio->nombre }}</div>
                                <div style="font-size:9px; color:#6b7280;">{{ $servicio->descripcion }}</div>
                            </td>
                            <td class="text-right">${{ number_format($precioLista, 2) }}</td>
                            <td class="text-right">{{ $servicio->pivot->cantidad }}</td>
                            <td class="text-right font-bold">${{ number_format($subtotalReal, 2) }}</td>
                        </tr>
                    @endforeach

                    @if($poliza->visitas_sitio_mensuales > 0)
                        @php 
                            $costoVisitaPromedio = 850;
                            $valorVisitas = $poliza->visitas_sitio_mensuales * $costoVisitaPromedio;
                            $totalValorReal += $valorVisitas;
                        @endphp
                        <tr>
                            <td>
                                <div class="font-bold">Visitas en Sitio Programadas</div>
                                <div style="font-size:9px; color:#6b7280;">Cobertura física preventiva y correctiva</div>
                            </td>
                            <td class="text-right">${{ number_format($costoVisitaPromedio, 2) }}</td>
                            <td class="text-right">{{ $poliza->visitas_sitio_mensuales }}</td>
                            <td class="text-right font-bold">${{ number_format($valorVisitas, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right" style="padding-top: 15px; font-weight: bold; color: #6b7280;">VALOR TOTAL DE SERVICIOS INDIVIDUALES</td>
                        <td class="text-right" style="padding-top: 15px; font-weight: bold; font-size: 13px;">${{ number_format($totalValorReal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right" style="border: none; color: #6b7280;">COSTO PREFERENCIAL POR PÓLIZA</td>
                        <td class="text-right" style="border: none; font-weight: bold;">- ${{ number_format($poliza->monto_mensual, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="section-title">Resumen de Beneficios Adicionales</div>
        <div style="margin-bottom: 30px;">
            <div class="benefit-item">
                <div class="benefit-icon"></div>
                <strong>Respuesta Prioritaria:</strong> SLA de {{ $poliza->sla_horas_respuesta }}h garantizado.
            </div>
            <div class="benefit-item">
                <div class="benefit-icon"></div>
                <strong>Portal 24/7:</strong> Gestión de tickets y reportes online.
            </div>
            <div class="benefit-item">
                <div class="benefit-icon"></div>
                <strong>Consultoría:</strong> Asesoría técnica en nuevos proyectos.
            </div>
            <div class="benefit-item">
                <div class="benefit-icon"></div>
                <strong>Descuentos:</strong> Tarifas especiales en horas extra.
            </div>
        </div>

        <div class="saving-card">
            <div class="saving-label">Ahorro Mensual Garantizado</div>
            @php
                $costoPoliza = $poliza->monto_mensual;
                $ahorro = max(0, $totalValorReal - $costoPoliza);
                $porcentaje = $totalValorReal > 0 ? ($ahorro / $totalValorReal) * 100 : 0;
            @endphp
            <div class="saving-value">${{ number_format($ahorro, 2) }} MXN</div>
            <div class="saving-badge">EQUIVALE A UN {{ round($porcentaje) }}% DE DESCUENTO</div>
            
            <p style="margin-top: 15px; font-size: 11px; opacity: 0.9;">
                Su inversión eficiente de <strong>${{ number_format($costoPoliza, 2) }}</strong> asegura la continuidad de su negocio.
            </p>
        </div>

        <div class="footer">
            Este análisis financiero es generado por el sistema de gestión de pólizas de {{ $empresa['nombre_empresa'] }}.<br>
            Los precios de mercado son estimaciones basadas en tarifas promedio de servicios bajo demanda (Out-of-pocket).
        </div>
    </div>
</body>
</html>
