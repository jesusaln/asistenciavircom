<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Póliza de Servicio {{ $poliza->folio }} - Beneficios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
        }

        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #3B82F6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            color: #1E40AF;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .company-info p {
            color: #666;
            font-size: 11px;
            line-height: 1.5;
        }

        .poliza-info {
            text-align: right;
        }

        .poliza-info .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #1E40AF;
            margin-bottom: 5px;
        }

        .poliza-info .folio {
            font-size: 16px;
            color: #3B82F6;
            font-family: monospace;
        }

        .poliza-info .fecha {
            color: #666;
            font-size: 10px;
            margin-top: 5px;
        }

        /* Cliente Info */
        .cliente-section {
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .cliente-section h3 {
            color: #1E40AF;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .cliente-nombre {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .cliente-detalle {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }

        /* Poliza Detalles */
        .poliza-detalles {
            background: #FAFAFA;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .poliza-detalles h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #E5E7EB;
        }

        .detalle-grid {
            display: table;
            width: 100%;
        }

        .detalle-row {
            display: table-row;
        }

        .detalle-label {
            display: table-cell;
            color: #666;
            padding: 8px 0;
            width: 180px;
            font-size: 11px;
        }

        .detalle-value {
            display: table-cell;
            color: #333;
            font-weight: 600;
            padding: 8px 0;
        }

        /* Beneficios Section */
        .beneficios-section {
            margin-bottom: 30px;
        }

        .beneficios-section h2 {
            color: #1E40AF;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .beneficio-card {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-left: 4px solid #3B82F6;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 12px;
        }

        .beneficio-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .beneficio-icono {
            font-size: 20px;
            margin-right: 10px;
        }

        .beneficio-titulo {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .beneficio-descripcion {
            color: #666;
            font-size: 11px;
            margin-left: 30px;
            line-height: 1.5;
        }

        /* Equipos Cubiertos */
        .equipos-section {
            background: #F0FDF4;
            border: 1px solid #10B981;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .equipos-section h3 {
            color: #047857;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .equipo-item {
            background: #fff;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .equipo-nombre {
            font-weight: bold;
            color: #333;
        }

        .equipo-serie {
            color: #666;
            font-family: monospace;
        }

        /* Servicios Incluidos */
        .servicios-section {
            margin-bottom: 25px;
        }

        .servicios-section h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #1E40AF;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 11px;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        /* Vigencia */
        .vigencia-section {
            background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);
            color: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .vigencia-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .vigencia-fechas {
            font-size: 18px;
            font-weight: bold;
        }

        .vigencia-monto {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.3);
        }

        .vigencia-monto-label {
            font-size: 11px;
            opacity: 0.8;
        }

        .vigencia-monto-valor {
            font-size: 28px;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #999;
            font-size: 10px;
        }

        /* Contacto */
        .contacto-section {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }

        .contacto-section h3 {
            color: #B45309;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .contacto-section p {
            color: #92400E;
            font-size: 12px;
        }

        .contacto-datos {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="company-info">
                    <h1>{{ $empresa->nombre ?? 'Nuestra Empresa' }}</h1>
                    <p>
                        {{ $empresa->direccion ?? '' }}<br>
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
        </div>

        <!-- Cliente Info -->
        <div class="cliente-section">
            <h3>Cliente Beneficiario</h3>
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
            <h2>✨ Beneficios de tu Póliza</h2>

            @foreach($beneficios as $beneficio)
                <div class="beneficio-card">
                    <div class="beneficio-header">
                        <span class="beneficio-icono">{{ $beneficio['icono'] }}</span>
                        <span class="beneficio-titulo">{{ $beneficio['titulo'] }}</span>
                    </div>
                    <div class="beneficio-descripcion">{{ $beneficio['descripcion'] }}</div>
                </div>
            @endforeach
        </div>

        <!-- Equipos Cubiertos -->
        @if($poliza->equipos && count($poliza->equipos) > 0)
            <div class="equipos-section">
                <h3>🔧 Equipos Cubiertos por esta Póliza</h3>
                @foreach($poliza->equipos as $equipo)
                    <div class="equipo-item">
                        <span class="equipo-nombre">{{ $equipo->nombre }}</span>
                        @if($equipo->serie)
                            <span class="equipo-serie"> | S/N: {{ $equipo->serie }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Servicios Incluidos -->
        @if($poliza->servicios && count($poliza->servicios) > 0)
            <div class="servicios-section">
                <h3>📋 Servicios Incluidos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Cantidad Incluida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($poliza->servicios as $servicio)
                            <tr>
                                <td>{{ $servicio->nombre }}</td>
                                <td>{{ $servicio->pivot->cantidad ?? 'Ilimitado' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Detalles Técnicos -->
        <div class="poliza-detalles">
            <h3>📊 Detalles de la Cobertura</h3>
            <div class="detalle-grid">
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
        </div>

        <!-- Contacto -->
        <div class="contacto-section">
            <h3>¿Necesitas ayuda?</h3>
            <p>Estamos disponibles para atenderte y resolver cualquier duda sobre tu póliza.</p>
            <div class="contacto-datos">
                📞 {{ $empresa->telefono ?? 'N/A' }} | ✉️ {{ $empresa->email ?? 'N/A' }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es informativo y forma parte de tu contrato de servicios.</p>
            <p>{{ $empresa->nombre ?? 'Nuestra Empresa' }} - Documento generado el {{ $fecha_generacion }}</p>
        </div>
    </div>
</body>

</html>