<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte Técnico</title>
    <style>
        :root {
            --primary:
                {{ $empresa['color_principal'] ?? '#3B82F6' }}
            ;
            --secondary:
                {{ $empresa['color_secundario'] ?? '#64748B' }}
            ;
        }

        @page {
            margin: 25px;
        }

        /* Margen de hoja reducido */
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            /* Letra base más pequeña */
            color: #333;
            line-height: 1.2;
            /* Menor interlineado */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo {
            height: 45px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #111;
        }

        .company-details {
            font-size: 9px;
            color: #555;
        }

        .section-box {
            margin-top: 10px;
            /* Margen superior reducido */
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .section-header {
            background-color: var(--primary);
            color: white;
            padding: 4px 10px;
            /* Padding reducido */
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .result-table {
            text-align: center;
            padding: 10px;
        }

        .big-number {
            font-size: 20px;
            font-weight: bold;
            color: #111;
            margin-bottom: 2px;
        }

        .sub-text {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        .highlight {
            color: var(--primary);
            font-weight: bold;
        }

        .specs-table td {
            padding: 4px 8px;
            /* Celdas más compactas */
            border-bottom: 1px solid #eee;
            width: 33.33%;
            vertical-align: top;
        }

        .specs-table td:last-child {
            border-right: none;
        }

        .label {
            display: block;
            font-size: 8px;
            /* Labels más pequeños */
            text-transform: uppercase;
            color: #888;
            margin-bottom: 1px;
            font-weight: bold;
        }

        .value {
            font-size: 10px;
            font-weight: bold;
            color: #111;
        }

        .savings-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 8px;
            /* Padding reducido */
            border-radius: 6px;
            margin-top: 10px;
            text-align: center;
        }

        .coupon-box {
            margin-top: 15px;
            border: 1px dashed #fbbf24;
            background-color: #fffbeb;
            padding: 8px;
            text-align: center;
            border-radius: 6px;
        }

        .coupon-title {
            color: #d97706;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 5px;
            font-size: 8px;
            color: #999;
        }

        .signature {
            width: 180px;
            margin: 20px auto 0;
            border-top: 1px solid #ccc;
            text-align: center;
            padding-top: 2px;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td width="60%">
                @if($empresa['logo_base64'])
                    <img src="{{ $empresa['logo_base64'] }}" class="logo">
                @elseif($empresa['logo_url'])
                    <img src="{{ public_path($empresa['logo_url']) }}" class="logo">
                @else
                    <div class="company-name">{{ $empresa['nombre'] }}</div>
                @endif
            </td>
            <td width="40%" align="right">
                <div class="company-name">{{ $empresa['nombre'] }}</div>
                <div class="company-details">{{ $empresa['email'] }}</div>
                <div class="company-details">Tel: {{ $empresa['telefono'] }}</div>
                <div style="font-size: 9px; color: #999; margin-top: 5px;">
                    Fecha: {{ $fecha }} <br>
                    Folio: {{ strtoupper(substr(md5($fecha . $btu), 0, 8)) }} <br>
                    Vigencia: 15 días
                </div>
            </td>
        </tr>
    </table>

    <!-- Resultado Principal -->
    <div class="section-box" style="border-color: var(--primary);">
        <div class="section-header">Estimación de Carga Térmica</div>
        <table class="result-table">
            <tr>
                <td>
                    <div class="sub-text">Capacidad Recomendada</div>
                    <div class="big-number">{{ $rec }}</div>
                    <div class="sub-text">Carga: <span class="highlight">{{ number_format($btu) }} BTU/h</span></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Detalles del Espacio (Tabla 3 columnas) -->
    <div class="section-box">
        <div class="section-header" style="background-color: #f1f5f9; color: #333; border-bottom: 1px solid #ddd;">
            Detalles del Espacio
        </div>
        <table class="specs-table">
            <tr>
                <td>
                    <span class="label">Habitación</span>
                    <span class="value">{{ ucfirst($form['habitacion'] ?? '-') }}</span>
                </td>
                <td>
                    <span class="label">Dimensiones</span>
                    <span class="value">{{ $form['area'] }}m² (Alt: {{ $form['altura'] }}m)</span>
                </td>
                <td style="border-right: none;">
                    <span class="label">Zona</span>
                    <span class="value">{{ ucfirst($form['zona'] ?? '-') }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Ocupantes</span>
                    <span class="value">{{ $form['personas'] }} Personas</span>
                </td>
                <td>
                    <span class="label">Techo Directo</span>
                    <span class="value">{{ ($form['techo_directo'] ?? false) ? 'Sí' : 'No' }}</span>
                </td>
                <td style="border-right: none;">
                    <span class="label">Ventanales</span>
                    <span class="value">{{ ($form['ventanales'] ?? false) ? 'Sí' : 'No' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Especificaciones Técnicas -->
    <div class="section-box">
        <div class="section-header" style="background-color: #f1f5f9; color: #333; border-bottom: 1px solid #ddd;">
            Especificaciones Técnicas
        </div>
        <table class="specs-table">
            <tr>
                <td>
                    <span class="label">Tecnología</span>
                    <span class="value">{{ ucfirst($form['tecnologia'] ?? 'Inverter') }}</span>
                </td>
                <td>
                    <span class="label">Voltaje</span>
                    <span class="value">{{ $form['voltaje'] }} V</span>
                </td>
                <td style="border-right: none;">
                    <span class="label">Operación</span>
                    <span class="value">{{ $form['funcion'] == 'dual' ? 'Frío/Calor' : 'Solo Frío' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Aislamiento</span>
                    <span class="value">{{ ucfirst($form['aislamiento'] ?? '-') }}</span>
                </td>
                <td>
                    <span class="label">Cable Recomendado</span>
                    <span class="value">{{ $elec_cable }}</span>
                </td>
                <td style="border-right: none;">
                    <span class="label">Térmico Sugerido</span>
                    <span class="value">{{ $elec_breaker }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Ahorro -->
    @if($ahorro > 0)
        <div class="savings-box">
            <div style="font-weight: bold; font-size: 11px; margin-bottom: 3px;">PROYECCIÓN DE AHORRO</div>
            <div>
                Con tecnología <b>Inverter</b>, ahorrarías aproximadamente
                <span style="font-size: 13px; font-weight: bold; color: #166534;">${{ number_format($ahorro) }} MXN
                    mensuales</span>.
            </div>
        </div>
    @endif

    <div class="signature">
        Asesor Técnico<br>
        {{ $empresa['nombre'] }}
    </div>

    <!-- Guía de Instalación FIDE -->
    <div class="section-box">
        <div class="section-header"
            style="background-color: #f8fafc; color: #475569; border-bottom: 1px solid #ddd; font-size: 10px;">
            Guía de Ubicación Sugerida (Criterios FIDE)
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
            <tr>
                <td style="width: 50%; padding: 6px; vertical-align: top; border-right: 1px solid #eee;">
                    <div style="color: #166534; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">[OK]
                        Dónde Instalar (Recomendado)</div>
                    <ul style="margin: 0; padding-left: 15px; list-style-type: square; color: #333;">
                        <li>En la pared más fresca (Norte/Este).</li>
                        <li>Al centro del espacio.</li>
                        <li>Altura: 2.0 a 2.5m del suelo.</li>
                    </ul>
                </td>
                <td style="width: 50%; padding: 6px; vertical-align: top;">
                    <div style="color: #991b1b; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">[X]
                        Dónde NO Instalar (Evitar)</div>
                    <ul style="margin: 0; padding-left: 15px; list-style-type: square; color: #333;">
                        <li>Sobre puertas o ventanas.</li>
                        <li>Cerca de fuentes de calor.</li>
                        <li>Donde se bloquee el flujo de aire.</li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    <!-- Preparación y Mantenimiento -->
    <div class="section-box" style="margin-top: 10px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
            <tr>
                <td style="width: 60%; padding: 6px; vertical-align: top; border-right: 1px solid #eee;">
                    <div style="color: #333; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">[LISTA]
                        Checklist para Instalación</div>
                    <ul style="margin: 0; padding-left: 15px; color: #555;">
                        <li>Identificar centro de carga (Breakers).</li>
                        <li>Despejar área de trabajo.</li>
                        <li>Acceso a techo/patio exterior.</li>
                    </ul>
                </td>
                <td style="width: 40%; padding: 6px; vertical-align: top; background-color: #eff6ff;">
                    <div style="color: #1e3a8a; font-weight: bold; margin-bottom: 4px; text-transform: uppercase;">[TIP]
                        Mantenimiento</div>
                    <div style="color: #1e40af;">
                        Programa servicio preventivo <b>cada 6 meses</b>.
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Cupón -->
    <div class="coupon-box">
        <div class="coupon-title">CUPÓN DE DESCUENTO ESPECIAL</div>
        <div style="margin: 5px 0; font-size: 12px; color: #92400e;">
            Obtén un <span style="background-color: #fbbf24; padding: 2px 5px; font-weight: bold;">10% DE
                DESCUENTO</span>
            en instalación presentando este reporte.
        </div>
        <div style="font-size: 9px; color: #b45309;">Válido por 15 días. Aplican restricciones.</div>
    </div>

    <div class="footer">
        <div style="margin-bottom: 3px; font-weight: bold; color: #475569; letter-spacing: 0.5px;">
            EMPRESA CERTIFICADA A NIVEL NACIONAL • VALIDACIÓN STPS Y SEP
        </div>
        Reporte generado el {{ $fecha }}. Estimación basada en criterios de dimensionamiento térmico del FIDE (México).
        <br>Este documento es una referencia preliminar y se recomienda validación en sitio.
    </div>
</body>

</html>