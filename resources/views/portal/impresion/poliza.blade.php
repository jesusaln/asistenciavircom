<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Póliza de Servicio #{{ $poliza->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #374151;
            line-height: 1.6;
            font-size: 11px;
            margin: 0;
            padding: 40px 40px 40px 60px;
            /* Margen izq extra para la barra */
        }

        /* Barra lateral corporativa */
        .sidebar-brand {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 12px;
            background: linear-gradient(to bottom, #1e40af, #3b82f6);
            z-index: -1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }

        h1 {
            font-size: 26px;
            font-weight: 800;
            margin: 0;
            color: #111827;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #3b82f6;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #111827;
            border-left: 4px solid #3b82f6;
            padding-left: 10px;
            margin-bottom: 15px;
            letter-spacing: 0.5px;
        }

        .grid-info {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .grid-row {
            display: table-row;
        }

        .grid-cell {
            display: table-cell;
            padding-bottom: 8px;
            vertical-align: top;
        }

        .label {
            font-size: 9px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .value {
            font-size: 12px;
            font-weight: 600;
            color: #1f2937;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table th {
            text-align: left;
            padding: 10px 8px;
            background-color: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
            color: #4b5563;
            font-size: 9px;
        }

        .table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .clausulas-box {
            font-size: 10px;
            color: #4b5563;
            text-align: justify;
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .payment-box {
            background: linear-gradient(to right, #eff6ff, #ffffff);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #bfdbfe;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 50px;
            right: 40px;
            text-align: center;
            font-size: 9px;
            color: #d1d5db;
            padding-top: 10px;
            border-top: 1px solid #f3f4f6;
        }

        @media print {
            body {
                padding: 0;
            }

            .sidebar-brand {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar-brand"></div>

    <div class="header">
        <div>
            @if($logo)
                <img src="{{ $logo }}" alt="Logo" style="height: 45px;">
            @else
                <h2 style="margin:0; text-transform:uppercase;">{{ $empresa->nombre_comercial ?? 'ASISTENCIA VIRCOM' }}</h2>
            @endif
            <div style="margin-top:5px; color:#6b7280; font-size:10px;">
                {{ $empresa->email ?? '' }} / {{ $empresa->telefono ?? '' }}
            </div>
        </div>
        <div style="text-align: right;">
            <div class="subtitle">Contrato de Servicio</div>
            <h1>Póliza #{{ $poliza->folio ?? $poliza->id }}</h1>
            <div style="margin-top:5px;">
                <span
                    style="background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:12px; font-weight:700; font-size:10px; text-transform:uppercase;">
                    {{ str_replace('_', ' ', $poliza->estado) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Cliente y Vigencia -->
    <div class="section">
        <div class="grid-info">
            <div class="grid-row">
                <div class="grid-cell" style="width: 60%;">
                    <div class="label">Suscriptor / Cliente</div>
                    <div class="value" style="font-size:14px;">{{ $cliente->nombre_razon_social }}</div>
                    <div class="value" style="font-weight:400; color:#6b7280;">ID Fiscal: {{ $cliente->rfc ?: 'N/A' }}
                    </div>
                    <div class="value" style="font-weight:400; color:#6b7280; font-size:10px;">{{ $cliente->direccion }}
                    </div>
                </div>
                <div class="grid-cell" style="width: 40%; text-align:right;">
                    <div class="label">Periodo de Cobertura</div>
                    <div class="value">
                        Del {{ \Carbon\Carbon::parse($poliza->fecha_inicio)->format('d/m/Y') }}
                        al {{ \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Descripción Dinámica -->
    <div class="section">
        <div class="section-title">Alcance del Plan: {{ $poliza->nombre }}</div>
        <div style="margin-bottom: 10px; color: #374151;">
            {{ $poliza->descripcion }}
        </div>

        <!-- Bloque de Claridad (Evita contradicciones) -->
        <div
            style="background: #fffbeb; border-left: 3px solid #f59e0b; padding: 10px; font-size: 10px; color: #92400e;">
            <strong>NOTA DE COBERTURA:</strong> Independientemente de la descripción general, este contrato garantiza
            legalmente la disponibilidad mensual de
            <strong>{{ $poliza->limite_mensual_tickets }} tickets de soporte</strong> y
            <strong>{{ $poliza->visitas_sitio_mensuales ?? 0 }} visitas presenciales</strong>.
            Cualquier servicio adicional será facturado según tarifa preferencial vigente.
        </div>
    </div>

    <!-- Tabla de Límites (La Verdad Legal) -->
    <div class="section">
        <div class="section-title">Detalle de Servicios Incluidos</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">Servicio</th>
                    <th style="width: 30%;">Límite Mensual</th>
                    <th style="width: 20%; text-align:right;">Valor Extra</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Soporte Técnico Remoto</strong>
                        <div style="font-size:9px; color:#6b7280;">Resolución de incidentes vía escritorio remoto o
                            telefónica.</div>
                    </td>
                    <td>
                        {{ $poliza->limite_mensual_tickets > 900 ? 'ILIMITADO' : $poliza->limite_mensual_tickets . ' Tickets / Mes' }}
                    </td>
                    <td style="text-align:right; color:#9ca3af;">-</td>
                </tr>
                <tr>
                    <td>
                        <strong>Asistencia en Sitio</strong>
                        <div style="font-size:9px; color:#6b7280;">Visita de técnico especializado a instalaciones.
                        </div>
                    </td>
                    <td>
                        {{ $poliza->visitas_sitio_mensuales ?? 0 }} Visitas / Mes
                    </td>
                    <td style="text-align:right;">
                        ${{ number_format($poliza->costo_visita_sitio_extra ?? 0, 2) }}
                    </td>
                </tr>
                @if($poliza->horas_incluidas_mensual > 0)
                    <tr>
                        <td><strong>Bolsa de Horas de Ingeniería</strong></td>
                        <td>{{ $poliza->horas_incluidas_mensual }} Horas</td>
                        <td style="text-align:right;">${{ number_format($poliza->costo_hora_excedente, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Tiempo de Respuesta (SLA)</strong></td>
                    <td>Garantía de {{ $poliza->sla_horas_respuesta }} Horas Hábiles</td>
                    <td style="text-align:right; color:#9ca3af;">-</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Cláusulas -->
    <div class="section">
        <div class="section-title">Términos y Condiciones</div>
        <div class="clausulas-box">
            @if($poliza->clausulas_especiales)
                {!! nl2br(e($poliza->clausulas_especiales)) !!}
            @elseif($poliza->planPoliza && $poliza->planPoliza->clausulas)
                {!! nl2br(e($poliza->planPoliza->clausulas)) !!}
            @else
                <p style="margin-top:0;"><strong>1. OBJETO DEL SERVICIO:</strong> EL PROVEEDOR se compromete a brindar
                    servicios de asistencia técnica informática para mantener la operatividad de los equipos de EL CLIENTE.
                </p>
                <p><strong>2. CONFIDENCIALIDAD:</strong> Toda la información a la que se tenga acceso durante el soporte
                    será tratada bajo estricta confidencialidad y secreto profesional.</p>
                <p><strong>3. VIGENCIA Y RENOVACIÓN:</strong> El presente servicio se renovará automáticamente salvo
                    notificación por escrito con 30 días de antelación.</p>
                <p style="margin-bottom:0;"><strong>4. EXCLUSIONES:</strong> No incluye refacciones, software ilegal, ni
                    daños por desastres naturales o manipulación por terceros no autorizados.</p>
            @endif
        </div>
    </div>

    <!-- Equipos -->
    <div class="section">
        <div class="section-title">Infraestructura Protegida</div>
        @if($poliza->equipos && count($poliza->equipos) > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Serie</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($poliza->equipos as $equipo)
                        <tr>
                            <td>{{ $equipo->marca }} {{ $equipo->modelo }}</td>
                            <td>{{ $equipo->numero_serie }}</td>
                            <td>{{ $equipo->ubicacion_fisica }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div
                style="padding: 15px; border: 1px dashed #9ca3af; border-radius: 8px; background: #f9fafb; color: #4b5563;">
                <div style="font-weight: 700; margin-bottom: 5px; color: #1f2937;">COBERTURA ABIERTA EMPRESARIAL</div>
                Este contrato ampara el soporte técnico a la infraestructura informática operativa ubicada en el domicilio
                fiscal del cliente, sujeto a los límites de tickets y horas contratadas. No requiere registro individual de
                activos para la atención de incidentes generales.
            </div>
        @endif
    </div>

    <!-- Pago -->
    <div class="section">
        <div class="payment-box">
            <div class="grid-info">
                <div class="grid-row">
                    <div class="grid-cell" style="vertical-align: middle;">
                        <div class="label">Inversión Mensual</div>
                        <div style="font-size: 20px; font-weight: 900; color: #1e40af;">
                            ${{ number_format($poliza->monto_mensual, 2) }} <span
                                style="font-size:12px; font-weight:600; color:#6b7280;">MXN + IVA</span>
                        </div>
                    </div>
                    <div class="grid-cell"
                        style="vertical-align: middle; padding-left: 20px; border-left: 1px solid #bfdbfe;">
                        <div class="label">Condiciones de Pago</div>
                        <div style="font-size: 11px;">
                            Pagadero los primeros <strong>{{ $poliza->dia_cobro }} días</strong> de cada mes.<br>
                            Monto sujeto a cambios anuales según IPC.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        Este documento es un contrato de adhesión digital válido bajo los términos de comercio electrónico vigentes.<br>
        Firma Digital de Validación: {{ md5($poliza->id . $poliza->created_at . 'vircom-secure') }}
    </div>

</body>

</html>