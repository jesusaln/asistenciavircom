<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        body {
            margin: 0;
            padding: 0;
            color: rgba(255, 255, 255, 0.9);
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            background: #0b1c34;
            width: 100%;
        }

        * {
            box-sizing: border-box;
        }

        img {
            max-width: 100%;
        }

        .page {
            position: relative;
            page-break-after: always;
            width: 100%;
        }

        .page.last {
            page-break-after: auto;
        }

        .panel,
        .cover-shell {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            box-sizing: border-box;
        }

        .panel {
            background: linear-gradient(180deg, #0f2746 0%, #143761 100%);
            padding: 15mm 15mm 11mm;
        }

        .cover-shell {
            background: linear-gradient(135deg, #081426 0%, #0d2340 52%, #16365f 100%);
            color: #ffffff;
            padding: 20mm 15mm 15mm;
        }

        .accent-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: #0a1a30;
        }

        .shape-circle {
            position: absolute;
            border-radius: 999px;
            background: #173b67;
            opacity: 0.09;
        }

        .shape-square {
            position: absolute;
            background: #173b67;
            opacity: 0.09;
            transform: rotate(18deg);
        }

        .shape-outline {
            position: absolute;
            border: 2px solid #244d80;
            opacity: 0.16;
            transform: rotate(-14deg);
        }

        .shape-diamond {
            position: absolute;
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.22);
            transform: rotate(45deg);
        }

        .shape-line {
            position: absolute;
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.32) 50%, rgba(255, 255, 255, 0) 100%);
        }

        .cover-shell:before {
            content: "";
            position: absolute;
            top: -110px;
            right: -70px;
            width: 320px;
            height: 320px;
            border-radius: 999px;
            background: #ffffff;
            opacity: 0.13;
        }

        .cover-shell:after {
            content: "";
            position: absolute;
            bottom: -120px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 999px;
            background: #ffffff;
            opacity: 0.10;
        }

        .cover-logo-box {
            display: block;
            margin: 0 auto 34px;
            text-align: center;
            width: 100%;
        }

        .cover-logo-box img {
            max-height: 165px;
            max-width: 460px;
            filter: brightness(0) invert(1);
        }

        .cover-title {
            font-size: 42px;
            line-height: 1.08;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 18px;
            letter-spacing: 2.6px;
            text-align: center;
        }

        .cover-subtitle {
            font-size: 15px;
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.88);
            margin: 0 auto;
            max-width: 540px;
            text-align: center;
        }

        .cover-divider {
            width: 82px;
            height: 2px;
            margin: 0 auto 24px;
            background: rgba(255, 255, 255, 0.55);
        }

        .cover-band {
            position: absolute;
            left: 30px;
            right: 30px;
            bottom: 22px;
            z-index: 2;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding-top: 10px;
            font-size: 9px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #cbd5e1;
            text-align: center;
        }

        .header-table,
        .service-table,
        .kpi-table,
        .dual-table,
        .info-table,
        .contact-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            position: relative;
            z-index: 2;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.14);
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #ffffff;
        }

        .header-subtitle {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.72);
            text-transform: uppercase;
            letter-spacing: 1.8px;
            padding-top: 4px;
        }

        .icon-line {
            position: relative;
            z-index: 2;
            margin: 6px 0 10px;
            text-align: left;
        }

        .icon-chip {
            display: inline-block;
            min-width: 28px;
            height: 28px;
            line-height: 28px;
            margin-right: 8px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.20);
            border-radius: 999px;
            color: rgba(255, 255, 255, 0.88);
            font-size: 10px;
            font-weight: bold;
            background: rgba(255, 255, 255, 0.05);
        }

        .header-logo {
            width: 140px;
            text-align: right;
            vertical-align: middle;
        }

        .header-logo img {
            max-height: 46px;
            filter: drop-shadow(0 0 1px rgba(0, 0, 0, 0.2));
        }

        .hero-block,
        .card,
        .service-card,
        .kpi-card {
            position: relative;
            z-index: 2;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            border-radius: 16px;
        }

        .hero-block,
        .card {
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        .service-card {
            overflow: hidden;
            margin-bottom: 8px;
        }

        .kpi-card {
            padding: 10px;
            min-height: 82px;
        }

        .hero-title,
        .section-title,
        .service-title,
        .kpi-number,
        .contact-name {
            color: #ffffff;
        }

        .hero-title {
            font-size: 19px;
            line-height: 1.2;
            margin: 0 0 6px;
            font-weight: bold;
        }

        .section-label {
            display: inline-block;
            margin-bottom: 8px;
            padding: 5px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            color: rgba(255, 255, 255, 0.92);
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .text,
        .hero-text,
        .kpi-text,
        .list li,
        .contact-line {
            color: #d7e8fb;
        }

        .text,
        .hero-text {
            margin: 0 0 7px;
            text-align: justify;
        }

        .kpi-table td {
            width: 33.33%;
            padding-right: 10px;
            vertical-align: top;
        }

        .kpi-table td:last-child {
            padding-right: 0;
        }

        .kpi-icon {
            width: 26px;
            height: 26px;
            line-height: 26px;
            text-align: center;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .kpi-caption,
        .label,
        .contact-role,
        .service-topline {
            color: rgba(255, 255, 255, 0.72);
            text-transform: uppercase;
        }

        .kpi-caption,
        .label {
            font-size: 9px;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .value {
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
        }

        .info-table td {
            padding: 8px 4px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.14);
            vertical-align: top;
        }

        .info-table tr:last-child td {
            border-bottom: 0;
        }

        .dual-table td {
            width: 50%;
            vertical-align: top;
        }

        .dual-table td:first-child {
            padding-right: 8px;
        }

        .dual-table td:last-child {
            padding-left: 8px;
        }

        .service-image {
            display: block;
            width: 100%;
            height: 112px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255, 255, 255, 0.14);
        }

        .service-body {
            padding: 10px 12px 12px;
        }

        .service-topline {
            font-size: 9px;
            letter-spacing: 1.3px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .service-title {
            font-size: 14px;
            margin: 0 0 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .service-points span,
        .tag {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .mini-grid {
            position: relative;
            z-index: 2;
            font-size: 0;
            margin-top: 6px;
        }

        .mini-card {
            display: inline-block;
            width: 31.33%;
            margin: 0 1% 8px;
            vertical-align: top;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.07);
            padding: 10px;
            font-size: 10px;
        }

        .mini-title {
            color: #ffffff;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .mini-text {
            color: #d7e8fb;
            line-height: 1.45;
        }

        .list {
            margin: 8px 0 0 16px;
            padding: 0;
        }

        .logo-grid,
        .client-grid {
            position: relative;
            z-index: 2;
            font-size: 0;
        }

        .logo-item {
            display: inline-block;
            width: 23%;
            margin: 0 1% 10px;
            vertical-align: top;
            text-align: center;
            padding: 12px 8px;
            min-height: 72px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 10px;
            color: #ffffff;
        }

        .logo-item img {
            max-height: 34px;
        }

        .client-item {
            display: inline-block;
            width: 31.33%;
            margin: 0 1% 10px;
            padding: 12px 8px;
            vertical-align: top;
            text-align: center;
            min-height: 68px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.16);
            font-size: 10px;
            color: #ffffff;
            font-weight: bold;
        }

        .client-item img {
            max-height: 30px;
        }

        .contact-box {
            padding: 14px 16px;
            text-align: center;
        }

        .contact-name {
            font-size: 20px;
            line-height: 1.15;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .contact-role {
            font-size: 10px;
            letter-spacing: 1.3px;
            margin-bottom: 14px;
        }

        .footer-note,
        .page-footer-left,
        .page-footer-center,
        .page-footer-right {
            color: rgba(255, 255, 255, 0.90);
            text-transform: uppercase;
        }

        .footer-note {
            position: relative;
            z-index: 2;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 8px;
            text-align: center;
            letter-spacing: 1.2px;
        }

        .page-footer {
            position: relative;
            z-index: 2;
            margin-top: 8px;
            padding: 8px 10px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        .page-footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .page-footer-left,
        .page-footer-center,
        .page-footer-right {
            font-size: 8px;
            letter-spacing: 1px;
            vertical-align: middle;
        }

        .page-footer-center {
            text-align: center;
        }

        .page-footer-right {
            text-align: right;
        }
    </style>
</head>
<body class="theme-bg">
    <div class="page">
        <div class="cover-shell">
            <div class="shape-diamond" style="top: 36px; right: 144px;"></div>
            <div class="shape-diamond" style="top: 64px; right: 118px; width: 10px; height: 10px;"></div>
            <div class="shape-line" style="top: 108px; right: 56px; width: 120px;"></div>
            <div class="shape-line" style="bottom: 108px; left: 48px; width: 150px;"></div>
            <div style="text-align: center; margin-top: 52px; position: relative; z-index: 2;">
                @if($empresa['logo_base64'])
                    <div class="cover-logo-box">
                        <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                    </div>
                @endif
                <h1 class="cover-title">{{ $empresa['nombre'] }}</h1>
                <div class="cover-divider"></div>
                <p class="cover-subtitle">
                    Curriculum empresarial de soluciones en seguridad electronica, infraestructura TI, automatizacion comercial y soporte tecnico.
                </p>
            </div>
            <div class="cover-band">{{ $empresa['sitio_web'] }}</div>
        </div>
    </div>

    <div class="page">
        <div class="panel">
            <div class="accent-bar"></div>
            <div class="shape-circle" style="top: 44px; right: -30px; width: 140px; height: 140px;"></div>
            <div class="shape-square" style="bottom: 60px; right: 34px; width: 70px; height: 70px;"></div>
            <div class="shape-diamond" style="top: 118px; right: 70px;"></div>
            <div class="shape-line" style="bottom: 136px; left: 38px; width: 150px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="header-title">Perfil Ejecutivo</div>
                            <div class="header-subtitle">Quienes somos, que resolvemos y como operamos</div>
                        </td>
                        <td class="header-logo">
                            @if($empresa['logo_base64'])
                                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="icon-line">
                <span class="icon-chip">01</span>
                <span class="icon-chip">02</span>
                <span class="icon-chip">03</span>
            </div>

            <div class="hero-block">
                <div class="section-label">Perfil Empresarial</div>
                <div class="hero-title">Soluciones tecnicas con enfoque operativo, comercial y de continuidad.</div>
                <p class="hero-text">
                    <strong>{{ $empresa['nombre'] }}</strong> desarrolla e implementa soluciones para organizaciones que requieren mayor control, seguridad, productividad y soporte especializado. Nuestro enfoque combina diagnostico, ejecucion, puesta en marcha y acompanamiento posterior.
                </p>
            </div>

            <table class="kpi-table">
                <tr>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">01</div>
                            <div class="kpi-caption">Fundacion</div>
                            <div class="kpi-number">{{ $empresa['fundacion'] }}</div>
                            <div class="kpi-text">Inicio y consolidacion de la empresa.</div>
                        </div>
                    </td>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">02</div>
                            <div class="kpi-caption">Cobertura</div>
                            <div class="kpi-number">Regional</div>
                            <div class="kpi-text">{{ $empresa['cobertura'] }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">03</div>
                            <div class="kpi-caption">Relacion</div>
                            <div class="kpi-number">Directa</div>
                            <div class="kpi-text">Seguimiento comercial y tecnico puntual.</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="height: 12px;"></div>

            <table class="dual-table">
                <tr>
                    <td>
                        <div class="card">
                            <div class="section-label">Propuesta de Valor</div>
                            <p class="text">
                                Atendemos proyectos para sector privado, corporativo e institucional con soluciones escalables y foco en operacion real. No solo instalamos tecnologia: estructuramos soluciones utilizables, medibles y sostenibles.
                            </p>
                            <p class="text">
                                Nuestro trabajo integra levantamiento, recomendacion tecnica, implementacion, capacitacion y soporte para asegurar adopcion y continuidad.
                            </p>
                        </div>
                    </td>
                    <td>
                        <div class="card">
                            <div class="section-label">Valores</div>
                            <div class="tag-list">
                                @foreach(($empresa['valores'] ?? []) as $valor)
                                    <span class="tag">{{ $valor }}</span>
                                @endforeach
                            </div>
                            <div style="height: 8px;"></div>
                            <div class="section-label">Direccion General</div>
                            <p class="text"><strong>{{ $directivo['nombre'] }}</strong><br>{{ $directivo['puesto'] }}</p>
                            <p class="text">{{ $directivo['telefono'] }}<br>{{ $directivo['email'] }}</p>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="card">
                <div class="section-label">Beneficios para el Cliente</div>
                <div class="tag-list">
                    <span class="tag">Mayor control operativo</span>
                    <span class="tag">Reduccion de riesgos</span>
                    <span class="tag">Continuidad del negocio</span>
                    <span class="tag">Trazabilidad</span>
                </div>
            </div>

            <div class="footer-note">Presentacion institucional | {{ $empresa['direccion'] }}</div>
            <div class="page-footer">
                <table class="page-footer-table">
                    <tr>
                        <td class="page-footer-left">{{ $empresa['nombre'] }}</td>
                        <td class="page-footer-center">Quienes Somos</td>
                        <td class="page-footer-right">01</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="panel">
            <div class="accent-bar"></div>
            <div class="shape-outline" style="top: 84px; right: 26px; width: 92px; height: 92px;"></div>
            <div class="shape-circle" style="bottom: 50px; left: -24px; width: 120px; height: 120px;"></div>
            <div class="shape-diamond" style="bottom: 132px; right: 62px;"></div>
            <div class="shape-line" style="top: 96px; left: 34px; width: 140px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="header-title">Identidad y Respaldo</div>
                            <div class="header-subtitle">Base legal, mision corporativa y cumplimiento</div>
                        </td>
                        <td class="header-logo">
                            @if($empresa['logo_base64'])
                                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="icon-line">
                <span class="icon-chip">RFC</span>
                <span class="icon-chip">SAT</span>
                <span class="icon-chip">REPSE</span>
            </div>

            <table class="dual-table">
                <tr>
                    <td>
                        <div class="card">
                            <div class="section-label">Datos Corporativos</div>
                            <table class="info-table">
                                <tr>
                                    <td class="label">Razon social</td>
                                    <td class="value">{{ $empresa['razon_social'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">RFC / CURP</td>
                                    <td class="value">{{ $empresa['rfc'] }} / {{ $empresa['curp'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Giro</td>
                                    <td class="value">{{ $empresa['giro'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Telefono / Email</td>
                                    <td class="value">{{ $empresa['telefono'] }} | {{ $empresa['email'] }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Sitio web</td>
                                    <td class="value">{{ $empresa['sitio_web'] }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td>
                        <div class="card dark-card">
                            <div class="section-label">Cumplimiento</div>
                            <ul class="list">
                                <li><strong>SAT:</strong> {{ $certificaciones['SAT'] }}</li>
                                <li><strong>REPSE:</strong> {{ $certificaciones['REPSE'] }}</li>
                                <li><strong>IMSS:</strong> {{ $certificaciones['IMSS'] }}</li>
                                <li><strong>Operacion:</strong> Procesos formales de instalacion, soporte y seguimiento.</li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="dual-table">
                <tr>
                    <td>
                        <div class="card">
                            <div class="section-label">Mision</div>
                            <p class="text">{{ $empresa['mision'] }}</p>
                        </div>
                    </td>
                    <td>
                        <div class="card">
                            <div class="section-label">Vision</div>
                            <p class="text">{{ $empresa['vision'] }}</p>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="card">
                <div class="section-label">Sectores Atendidos</div>
                <div class="tag-list">
                    <span class="tag">Retail</span>
                    <span class="tag">Corporativo</span>
                    <span class="tag">Gobierno</span>
                    <span class="tag">Industrial</span>
                    <span class="tag">Servicios</span>
                </div>
            </div>

            <div class="footer-note">Documento emitido el {{ $fecha }}</div>
            <div class="page-footer">
                <table class="page-footer-table">
                    <tr>
                        <td class="page-footer-left">{{ $empresa['nombre'] }}</td>
                        <td class="page-footer-center">Ficha Corporativa</td>
                        <td class="page-footer-right">02</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="panel">
            <div class="accent-bar"></div>
            <div class="shape-circle" style="top: 30px; left: -28px; width: 130px; height: 130px;"></div>
            <div class="shape-square" style="top: 180px; right: 32px; width: 60px; height: 60px;"></div>
            <div class="shape-diamond" style="top: 84px; right: 88px;"></div>
            <div class="shape-line" style="bottom: 88px; right: 40px; width: 150px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="header-title">Soluciones y Sectores</div>
                            <div class="header-subtitle">Portafolio principal y aplicaciones por tipo de operacion</div>
                        </td>
                        <td class="header-logo">
                            @if($empresa['logo_base64'])
                                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="icon-line">
                <span class="icon-chip">CCTV</span>
                <span class="icon-chip">POS</span>
                <span class="icon-chip">BIO</span>
            </div>

            <table class="service-table">
                <tr>
                    <td style="width: 50%; padding-right: 8px; vertical-align: top;">
                        <div class="service-card">
                            @if($imagenes_servicios['seguridad'])
                                <img src="{{ $imagenes_servicios['seguridad'] }}" class="service-image" alt="Seguridad">
                            @endif
                            <div class="service-body">
                                <div class="service-topline">Linea 01</div>
                                <div class="service-title">CCTV y Seguridad Electronica</div>
                                <p class="text">Videovigilancia, alarmas, monitoreo y control perimetral para mayor proteccion operativa.</p>
                                <div class="service-points">
                                    <span>Videovigilancia</span>
                                    <span>Alarmas</span>
                                    <span>Monitoreo</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 8px; vertical-align: top;">
                        <div class="service-card">
                            @if($imagenes_servicios['pos'])
                                <img src="{{ $imagenes_servicios['pos'] }}" class="service-image" alt="POS">
                            @endif
                            <div class="service-body">
                                <div class="service-topline">Linea 02</div>
                                <div class="service-title">Puntos de Venta</div>
                                <p class="text">Ecosistemas POS para retail y servicios con control comercial, cajas e inventario.</p>
                                <div class="service-points">
                                    <span>Retail</span>
                                    <span>Inventario</span>
                                    <span>Facturacion</span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="service-card">
                @if($imagenes_servicios['biometricos'])
                    <img src="{{ $imagenes_servicios['biometricos'] }}" class="service-image" alt="Biometricos">
                @endif
                <div class="service-body">
                    <div class="service-topline">Linea 03</div>
                    <div class="service-title">Control de Asistencia Biometrico</div>
                    <p class="text">Terminales faciales y dactilares con registro de asistencia, incidencias y soporte a procesos administrativos.</p>
                    <div class="service-points">
                        <span>Biometria</span>
                        <span>Nube</span>
                        <span>Asistencia</span>
                        <span>Nomina</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="section-label">Sectores de Aplicacion</div>
                <div class="tag-list">
                    <span class="tag">Tiendas y cadenas</span>
                    <span class="tag">Oficinas corporativas</span>
                    <span class="tag">Dependencias publicas</span>
                    <span class="tag">Parques industriales</span>
                    <span class="tag">Operacion multisucursal</span>
                </div>
            </div>

            <div class="footer-note">Soluciones orientadas a seguridad, control y productividad</div>
            <div class="page-footer">
                <table class="page-footer-table">
                    <tr>
                        <td class="page-footer-left">{{ $empresa['nombre'] }}</td>
                        <td class="page-footer-center">Portafolio y Sectores</td>
                        <td class="page-footer-right">03</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="panel">
            <div class="accent-bar"></div>
            <div class="shape-outline" style="bottom: 44px; right: 26px; width: 100px; height: 100px;"></div>
            <div class="shape-circle" style="top: 38px; right: -26px; width: 120px; height: 120px;"></div>
            <div class="shape-diamond" style="top: 126px; left: 52px;"></div>
            <div class="shape-line" style="bottom: 124px; left: 42px; width: 130px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="header-title">Metodo y Ejecucion</div>
                            <div class="header-subtitle">Proceso de trabajo, capacidad tecnica y experiencia aplicada</div>
                        </td>
                        <td class="header-logo">
                            @if($empresa['logo_base64'])
                                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="icon-line">
                <span class="icon-chip">DX</span>
                <span class="icon-chip">IMP</span>
                <span class="icon-chip">SUP</span>
            </div>

            <table class="dual-table">
                <tr>
                    <td>
                        <div class="service-card">
                            @if($imagenes_servicios['equipo'])
                                <img src="{{ $imagenes_servicios['equipo'] }}" class="service-image" alt="Equipo">
                            @endif
                            <div class="service-body">
                                <div class="service-topline">Equipo Tecnico</div>
                                <div class="service-title">Personal calificado y acompanamiento continuo</div>
                                <p class="text">Contamos con tecnicos e ingenieros para instalacion, diagnostico, mantenimiento y soporte especializado en campo.</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="card">
                            <div class="section-label">Metodologia</div>
                            <ul class="list">
                                <li>Levantamiento y diagnostico.</li>
                                <li>Propuesta tecnica y comercial.</li>
                                <li>Implementacion e instalacion.</li>
                                <li>Capacitacion y entrega operativa.</li>
                                <li>Soporte y mantenimiento posterior.</li>
                            </ul>
                        </div>
                        <div class="card dark-card">
                            <div class="section-label">Experiencia Relevante</div>
                            <ul class="list">
                                <li><strong>Sector privado:</strong> {{ $experiencia_top['Sector Privado'] }}</li>
                                <li><strong>Sector publico:</strong> {{ $experiencia_top['Sector Público'] }}</li>
                                <li><strong>Infraestructura TI:</strong> Redes, soporte y crecimiento tecnologico para operaciones empresariales.</li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>


            <table class="kpi-table">
                <tr>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">A</div>
                            <div class="kpi-caption">Respuesta</div>
                            <div class="kpi-number">Agil</div>
                            <div class="kpi-text">Seguimiento oportuno conforme a prioridad y necesidad del cliente.</div>
                        </div>
                    </td>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">B</div>
                            <div class="kpi-caption">Implementacion</div>
                            <div class="kpi-number">Integral</div>
                            <div class="kpi-text">Hardware, software, red y puesta en marcha bajo una misma coordinacion.</div>
                        </div>
                    </td>
                    <td>
                        <div class="kpi-card">
                            <div class="kpi-icon">C</div>
                            <div class="kpi-caption">Soporte</div>
                            <div class="kpi-number">Continuo</div>
                            <div class="kpi-text">Acompanamiento tecnico para evolucion y estabilidad del proyecto.</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">Ejecucion tecnica respaldada por procesos y experiencia</div>
            <div class="page-footer">
                <table class="page-footer-table">
                    <tr>
                        <td class="page-footer-left">{{ $empresa['nombre'] }}</td>
                        <td class="page-footer-center">Capacidad Operativa</td>
                        <td class="page-footer-right">04</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="page last">
        <div class="panel">
            <div class="accent-bar"></div>
            <div class="shape-circle" style="bottom: 24px; left: -24px; width: 126px; height: 126px;"></div>
            <div class="shape-square" style="top: 64px; right: 24px; width: 56px; height: 56px;"></div>
            <div class="shape-diamond" style="top: 108px; right: 92px;"></div>
            <div class="shape-line" style="bottom: 142px; right: 48px; width: 145px;"></div>

            <div class="header">
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="header-title">Referencias y Cierre Comercial</div>
                            <div class="header-subtitle">Marcas, clientes, casos de exito y contacto directo</div>
                        </td>
                        <td class="header-logo">
                            @if($empresa['logo_base64'])
                                <img src="{{ $empresa['logo_base64'] }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="icon-line">
                <span class="icon-chip">REF</span>
                <span class="icon-chip">CAS</span>
                <span class="icon-chip">CTA</span>
            </div>

            @if(count($marcas) > 0)
                <div class="section-label">Marcas y Distribuciones</div>
                <div class="logo-grid">
                    @foreach($marcas as $marca)
                        @php
                            $logoPath = $marca->logo ? str_replace('storage/', '', $marca->logo) : null;
                            $path = $logoPath ? storage_path('app/public/' . $logoPath) : null;
                            $base64 = null;
                            if ($path && file_exists($path)) {
                                $data = file_get_contents($path);
                                $mime = mime_content_type($path) ?: null;
                                if ($data !== false && $mime && str_starts_with($mime, 'image/')) {
                                    $base64 = 'data:' . $mime . ';base64,' . base64_encode($data);
                                }
                            }
                        @endphp
                        <div class="logo-item">
                            @if($base64)
                                <img src="{{ $base64 }}" alt="{{ $marca->nombre }}">
                            @else
                                {{ $marca->nombre }}
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($logos) > 0)
                <div class="section-label">Clientes y Referencias Visuales</div>
                <div class="client-grid">
                    @foreach($logos->take(4) as $logo)
                        @php
                            $logoClientePath = $logo->logo ? str_replace('storage/', '', $logo->logo) : null;
                            $clientePath = $logoClientePath ? storage_path('app/public/' . $logoClientePath) : null;
                            $clienteBase64 = null;
                            if ($clientePath && file_exists($clientePath)) {
                                $data = file_get_contents($clientePath);
                                $mime = mime_content_type($clientePath) ?: null;
                                if ($data !== false && $mime && str_starts_with($mime, 'image/')) {
                                    $clienteBase64 = 'data:' . $mime . ';base64,' . base64_encode($data);
                                }
                            }
                        @endphp
                        <div class="client-item">
                            @if($clienteBase64)
                                <img src="{{ $clienteBase64 }}" alt="{{ $logo->nombre }}">
                            @else
                                {{ $logo->nombre }}
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card">
                <div class="section-label">Casos de Exito</div>
                <ul class="list">
                    <li><strong>Retail:</strong> Integracion de punto de venta, inventario y monitoreo para control diario.</li>
                    <li><strong>Gobierno:</strong> Atencion de proyectos institucionales con seguimiento tecnico y formalidad documental.</li>
                    <li><strong>Corporativo:</strong> Infraestructura TI y soporte para operaciones con necesidad de continuidad.</li>
                </ul>
            </div>

            <table class="contact-table" style="margin-top: 10px;">
                <tr>
                    <td>
                        <div class="card contact-box">
                            <div class="section-label">Contacto Comercial</div>
                            <div class="contact-name">{{ $directivo['nombre'] }}</div>
                            <div class="contact-role">{{ $directivo['puesto'] }}</div>
                            <div class="contact-line"><strong>Telefono:</strong> {{ $directivo['telefono'] }}</div>
                            <div class="contact-line"><strong>Email:</strong> {{ $directivo['email'] }}</div>
                            <div class="contact-line"><strong>Sitio web:</strong> {{ $empresa['sitio_web'] }}</div>
                            <div class="contact-line"><strong>Ubicacion:</strong> {{ $empresa['direccion'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer-note">Solicite levantamiento, diagnostico o propuesta tecnica personalizada</div>
            <div class="page-footer">
                <table class="page-footer-table">
                    <tr>
                        <td class="page-footer-left">{{ $empresa['sitio_web'] }}</td>
                        <td class="page-footer-center">Referencias y Contacto</td>
                        <td class="page-footer-right">05</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
