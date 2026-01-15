<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $is_pos ? 'Propuesta Técnica POS' : 'Reporte Técnico Climatización' }}</title>
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

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.2;
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
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .section-header {
            background-color: var(--primary);
            color: white;
            padding: 4px 10px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .result-table {
            text-align: center;
            padding: 10px;
        }

        .big-number {
            font-size: 18px;
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
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            width: 33.33%;
            vertical-align: top;
        }

        .label {
            display: block;
            font-size: 8px;
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

        .coupon-box {
            margin-top: 15px;
            border: 1px dashed #fbbf24;
            background-color: #fffbeb;
            padding: 8px;
            text-align: center;
            border-radius: 6px;
        }

        .hw-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .hw-item {
            display: inline-block;
            width: 48%;
            margin-bottom: 5px;
            font-size: 9px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td width="60%">
                @if($empresa['logo_base64'])
                    <img src="{{ $empresa['logo_base64'] }}" class="logo">
                @else
                    <div class="company-name text-primary">{{ $empresa['nombre'] }}</div>
                @endif
            </td>
            <td width="40%" align="right">
                <div class="company-name">{{ $empresa['nombre'] }}</div>
                <div class="company-details">{{ $empresa['email'] }}</div>
                <div class="company-details">Tel: {{ $empresa['telefono'] }}</div>
                <div style="font-size: 8px; color: #999; margin-top: 5px;">
                    Fecha: {{ $fecha }} | Folio: {{ strtoupper(substr(md5($fecha . $btu), 0, 8)) }}
                </div>
            </td>
        </tr>
    </table>

    @if($is_pos)
        <!-- REPORTE POS -->
        <div class="section-box" style="border-color: var(--primary);">
            <div class="section-header">Propuesta Técnica de Punto de Venta</div>
            <table class="result-table">
                <tr>
                    <td>
                        <div class="sub-text">Sistema Recomendado</div>
                        <div class="big-number">{{ $rec }}</div>
                        <div class="sub-text">Software: <span
                                class="highlight">{{ $form['software'] ?? 'Eleventa / SoftRestaurant' }}</span></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-box">
            <div class="section-header" style="background-color: #f1f5f9; color: #333;">Detalles del Negocio</div>
            <table class="specs-table">
                <tr>
                    <td>
                        <span class="label">Giro Comercial</span>
                        <span class="value">{{ strtoupper($form['giro'] ?? 'Comercio') }}</span>
                    </td>
                    <td>
                        <span class="label">Volumen Ventas</span>
                        <span class="value">{{ ucfirst($form['volumen_ventas'] ?? '-') }}</span>
                    </td>
                    <td>
                        <span class="label">Estaciones de Cobro</span>
                        <span class="value">{{ $form['sucursales'] ?? '1' }} Caja(s)</span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-box">
            <div class="section-header" style="background-color: #f1f5f9; color: #333;">Equipamiento Seleccionado</div>
            <div style="padding: 10px;">
                <div class="hw-list">
                    @if($form['necesita_computadora_completa'] ?? false)
                    <div class="hw-item">✅ Computadora Completa</div> @endif
                    @if($form['necesita_cpu'] ?? false)
                    <div class="hw-item">✅ Solo CPU</div> @endif
                    @if($form['necesita_monitor'] ?? false)
                    <div class="hw-item">✅ Monitor Adicional</div> @endif
                    @if($form['necesita_cajon_dinero'] ?? false)
                    <div class="hw-item">✅ Cajón de Dinero</div> @endif
                    @if($form['necesita_impresora_tickets'] ?? false)
                    <div class="hw-item">✅ Impresora de Tickets</div> @endif
                    @if($form['necesita_bascula'] ?? false)
                    <div class="hw-item">✅ Báscula Digital</div> @endif
                    @if($form['necesita_lector_codigos'] ?? false)
                    <div class="hw-item">✅ Lector de Códigos</div> @endif
                    @if($form['necesita_etiquetadora'] ?? false)
                    <div class="hw-item">✅ Etiquetadora de Precios</div> @endif
                    @if($form['necesita_monitor_touch'] ?? false)
                    <div class="hw-item">✅ Monitor Touch Screen</div> @endif
                </div>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 10px; background-color: #eff6ff; border-radius: 8px;">
            <div style="font-weight: bold; color: #1e40af; margin-bottom: 5px;">ANÁLISIS DEL EXPERTO:</div>
            <p style="font-size: 10px; color: #1e3a8a; margin: 0;">
                Para un negocio de <b>{{ $form['giro'] }}</b>, la combinación de hardware seleccionada junto con el software
                <b>{{ $form['software'] ?? 'recomendado' }}</b>
                garantiza un control total de inventarios y una agilidad superior en el punto de cobro. Esta configuración
                está preparada para escalar conforme crezca su operación.
            </p>
        </div>

    @else
        <!-- REPORTE CLIMATIZACIÓN -->
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

        <div class="section-box">
            <div class="section-header" style="background-color: #f1f5f9; color: #333;">Detalles del Espacio</div>
            <table class="specs-table">
                <tr>
                    <td><span class="label">Habitación</span><span
                            class="value">{{ ucfirst($form['habitacion'] ?? '-') }}</span></td>
                    <td><span class="label">Dimensiones</span><span class="value">{{ $form['area'] }}m² (Alt:
                            {{ $form['altura'] }}m)</span></td>
                    <td><span class="label">Zona</span><span class="value">{{ ucfirst($form['zona'] ?? '-') }}</span></td>
                </tr>
            </table>
        </div>
    @endif

    <div class="coupon-box">
        <div style="color: {{ $is_pos ? '#2563eb' : '#d97706' }}; font-weight: bold; font-size: 11px;">OFERTA EXCLUSIVA
        </div>
        <p style="font-size: 10px; margin: 5px 0;">Obtén un <b>10% DE DESCUENTO</b> en tu kit POS o instalación de AC
            presentando este folio en sucursal.</p>
    </div>

    <div class="footer">
        Soluciones Integrales en Tecnología y Confort.<br>
        Este documento es una propuesta informativa. Precios y disponibilidad sujetos a cambios.
    </div>
</body>

</html>