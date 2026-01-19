<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Póliza de Servicio {{ $poliza->folio }} - Beneficios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #334155;
            line-height: 1.5;
            background: #fff;
        }

        .container {
            padding: 40px;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info h1 {
            color: #1e40af;
            font-size: 22px;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .company-info p {
            color: #64748b;
            font-size: 10px;
            line-height: 1.4;
        }

        .poliza-info {
            text-align: right;
            position: absolute;
            top: 40px;
            right: 40px;
        }

        .poliza-info .titulo {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }

        .poliza-info .folio {
            font-size: 18px;
            color: #3b82f6;
            font-weight: bold;
            margin: 5px 0;
        }

        .poliza-info .fecha {
            color: #94a3b8;
            font-size: 9px;
        }

        /* Cliente Info */
        .cliente-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }

        .cliente-section h3 {
            color: #64748b;
            font-size: 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .cliente-nombre {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .cliente-detalle {
            color: #64748b;
            font-size: 10px;
            margin-top: 4px;
        }

        /* Vigencia */
        .vigencia-section {
            background: #1e40af;
            color: #fff;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 30px;
        }

        .vigencia-section h3 {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            opacity: 0.9;
        }

        .vigencia-fechas {
            font-size: 20px;
            font-weight: bold;
        }

        .vigencia-monto {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .vigencia-monto-label {
            font-size: 10px;
            opacity: 0.8;
            margin-bottom: 4px;
        }

        .vigencia-monto-valor {
            font-size: 26px;
            font-weight: bold;
        }

        /* Beneficios */
        .beneficios-section h2 {
            color: #0f172a;
            font-size: 16px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .beneficio-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #3b82f6;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 12px;
        }

        .beneficio-header {
            margin-bottom: 4px;
        }

        .beneficio-icono-circle {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #3b82f6;
            border-radius: 50%;
            margin-right: 12px;
        }

        .beneficio-titulo {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
        }

        .beneficio-descripcion {
            color: #64748b;
            font-size: 10px;
            margin-left: 26px;
        }

        /* Servicios e Info */
        .section-title-small {
            color: #0f172a;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            padding: 12px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
        }

        .detalle-row {
            padding: 8px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .detalle-label {
            color: #64748b;
            font-weight: normal;
        }

        .detalle-value {
            color: #1e293b;
            font-weight: bold;
            text-align: right;
            float: right;
        }

        /* Contacto */
        .contacto-section {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 30px;
        }

        .contacto-section h3 {
            color: #9a3412;
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .contacto-section p {
            color: #c2410c;
            font-size: 11px;
            margin-bottom: 12px;
        }

        .contacto-datos {
            font-weight: bold;
            color: #1e293b;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $empresa->nombre_empresa ?? 'Vircom' }}</h1>
                <p>
                    {{ $empresa->direccion_completa ?? '' }}<br>
                    Tel: {{ $empresa->telefono ?? '' }}<br>
                    {{ $empresa->email ?? '' }}
                </p>
            </div>
            <div class="poliza-info">
                <div class="titulo">PÓLIZA DE SERVICIO</div>
                <div class="folio">{{ $poliza->folio }}</div>
                <div class="fecha">Generado: {{ $fecha_generacion }}</div>
            </div>
        </div>

        <!-- Cliente Info -->
        <div class="cliente-section">
            <h3>CLIENTE BENEFICIARIO</h3>
            <div class="cliente-nombre">{{ $poliza->cliente->nombre_razon_social ?? 'N/A' }}</div>
            @if($poliza->cliente?->email || $poliza->cliente?->telefono)
                <div class="cliente-detalle">
                    {{ $poliza->cliente->email ?? '' }}
                    @if($poliza->cliente->email && $poliza->cliente->telefono) | @endif
                    {{ $poliza->cliente->telefono ?? '' }}
                </div>
            @endif
        </div>

        <!-- Vigencia y Monto -->
        <div class="vigencia-section">
            <h3>Vigencia de la Póliza</h3>
            <div class="vigencia-fechas">
                {{ $poliza->fecha_inicio->format('d/m/Y') }}
                @if($poliza->fecha_fin)
                    — {{ $poliza->fecha_fin->format('d/m/Y') }}
                @else
                    — Vigencia Indefinida
                @endif
            </div>
            <div class="vigencia-monto">
                <div class="vigencia-monto-label">Inversión Mensual</div>
                <div class="vigencia-monto-valor">${{ number_format($poliza->monto_mensual, 2) }} MXN</div>
            </div>
        </div>

        <!-- Beneficios -->
        <div class="beneficios-section">
            <h2>Beneficios de tu Póliza</h2>

            @foreach($beneficios as $beneficio)
                <div class="beneficio-card">
                    <div class="beneficio-header">
                        <span class="beneficio-icono-circle"></span>
                        <span class="beneficio-titulo">{{ $beneficio['titulo'] }}</span>
                    </div>
                    <div class="beneficio-descripcion">{{ $beneficio['descripcion'] }}</div>
                </div>
            @endforeach
        </div>

        <!-- Análisis de Ahorro -->
        <div class="section-title-small">Análisis de Ahorro y Valor de Mercado</div>
        <table style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th style="text-align: right;">P. Unitario</th>
                    <th style="text-align: right;">Cant.</th>
                    <th style="text-align: right;">Total Real</th>
                </tr>
            </thead>
            <tbody>
                @php $totalValorReal = 0; @endphp
                @foreach($poliza->servicios as $servicio)
                    @php 
                        $precioLista = $servicio->precio ?? 450;
                        $subtotalReal = $precioLista * ($servicio->pivot->cantidad ?? 1);
                        $totalValorReal += $subtotalReal;
                    @endphp
                    <tr>
                        <td>{{ $servicio->nombre }}</td>
                        <td style="text-align: right;">${{ number_format($precioLista, 2) }}</td>
                        <td style="text-align: right;">{{ $servicio->pivot->cantidad ?? 1 }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($subtotalReal, 2) }}</td>
                    </tr>
                @endforeach
                @if($poliza->visitas_sitio_mensuales > 0)
                    @php 
                        $costoVisita = 850;
                        $valorVisitas = $poliza->visitas_sitio_mensuales * $costoVisita;
                        $totalValorReal += $valorVisitas;
                    @endphp
                    <tr>
                        <td>Visitas Técnicas Presenciales</td>
                        <td style="text-align: right;">${{ number_format($costoVisita, 2) }}</td>
                        <td style="text-align: right;">{{ $poliza->visitas_sitio_mensuales }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($valorVisitas, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @php
            $ahorro = max(0, $totalValorReal - $poliza->monto_mensual);
            $porcentaje = $totalValorReal > 0 ? ($ahorro / $totalValorReal) * 100 : 0;
        @endphp

        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 25px; color: white; text-align: center; margin-bottom: 30px;">
            <div style="font-size: 10px; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; opacity: 0.9;">Ahorro Mensual Estimado</div>
            <div style="font-size: 32px; font-weight: bold; margin: 10px 0;">${{ number_format($ahorro, 2) }} MXN</div>
            <div style="display: inline-block; background: rgba(255, 255, 255, 0.2); padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold;">
                EQUIVALE A UN {{ round($porcentaje) }}% DE DESCUENTO
            </div>
            <p style="margin-top: 15px; font-size: 11px; opacity: 0.9;">
                Su inversión eficiente de <strong>${{ number_format($poliza->monto_mensual, 2) }}</strong> asegura la continuidad de su negocio.
            </p>
        </div>

        <!-- Detalles Técnicos -->
        <div class="section-title-small">Detalles de la Cobertura</div>
        <div style="margin-bottom: 30px;">
            @if($poliza->sla_horas_respuesta)
                <div class="detalle-row">
                    <span class="detalle-label">Tiempo de Respuesta (SLA):</span>
                    <span class="detalle-value">{{ $poliza->sla_horas_respuesta }} horas</span>
                </div>
            @endif
            @if($poliza->horas_incluidas_mensual)
                <div class="detalle-row">
                    <span class="detalle-label">Horas Incluidas/Mes:</span>
                    <span class="detalle-value">{{ $poliza->horas_incluidas_mensual }} horas</span>
                </div>
            @endif
            @if($poliza->limite_mensual_tickets)
                <div class="detalle-row">
                    <span class="detalle-label">Tickets Incluidos/Mes:</span>
                    <span class="detalle-value">{{ $poliza->limite_mensual_tickets }} solicitudes</span>
                </div>
            @endif
            @if($poliza->costo_hora_excedente)
                <div class="detalle-row">
                    <span class="detalle-label">Costo Hora Adicional:</span>
                    <span class="detalle-value">${{ number_format($poliza->costo_hora_excedente, 2) }} MXN</span>
                </div>
            @endif
            <div class="detalle-row">
                <span class="detalle-label">Día de Facturación:</span>
                <span class="detalle-value">Día {{ $poliza->dia_cobro }} de cada mes</span>
            </div>
        </div>

        <!-- Contacto -->
        <div class="contacto-section">
            <h3>¿Necesitas ayuda?</h3>
            <p>Estamos disponibles para atenderte y resolver cualquier duda sobre tu póliza.</p>
            <div class="contacto-datos">
                Tel: {{ $empresa->telefono ?? 'N/A' }} | Email: {{ $empresa->email ?? 'N/A' }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es informativo y forma parte de tu contrato de servicios.</p>
            <p>{{ $empresa->nombre_empresa ?? 'Vircom' }} - Documento generado el {{ $fecha_generacion }}</p>
        </div>
    </div>
</body>

</html>