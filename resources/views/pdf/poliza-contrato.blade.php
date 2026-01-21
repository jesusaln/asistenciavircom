<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Adhesión {{ $poliza->folio }}</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #334155;
            line-height: 1.5;
            margin-top: 3cm;
            margin-bottom: 2cm;
            margin-left: 2cm;
            margin-right: 2cm;
            background: #fff;
        }

        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2.5cm;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.5cm 2cm;
        }

        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1.5cm;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 0.3cm 2cm;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }

        .pagenum:before {
            content: counter(page);
        }

        .pagecount:before {
            content: counter(pages);
        }

        .logo-text {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }

        .company-details {
            font-size: 9px;
            color: #64748b;
        }

        h1 {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 20px;
            color: #0f172a;
        }

        h2 {
            font-size: 11px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
        }

        h3 {
            font-size: 10px;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 5px;
            color: #334155;
        }

        p {
            margin-bottom: 10px;
            text-align: justify;
        }

        ul {
            margin: 8px 0 10px 18px;
            padding: 0;
        }

        li {
            margin: 3px 0;
        }

        .clause-title {
            font-weight: bold;
            color: #0f172a;
        }

        .info-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
            font-size: 9px;
            border: 1px solid #e2e8f0;
        }

        table {
            page-break-inside: avoid;
        }

        tr,
        td,
        th {
            page-break-inside: avoid;
        }

        .info-table th {
            background-color: #f1f5f9;
            text-align: left;
            padding: 6px 10px;
            font-weight: bold;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }

        .sig-block {
            display: inline-block;
            vertical-align: top;
            width: 48%;
            text-align: center;
        }

        .sig-image-container {
            height: 90px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin-bottom: 5px;
        }

        .sig-line {
            border-top: 1px solid #64748b;
            margin: 0 20px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 9px;
            color: #0f172a;
        }

        .sig-role {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .digital-certificate {
            margin-top: 30px;
            padding: 10px;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            font-family: 'Courier New', monospace;
            font-size: 8px;
            color: #475569;
            page-break-inside: avoid;
        }

        .hash-code {
            word-break: break-all;
            color: #0f172a;
            font-weight: bold;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 90px;
            color: rgba(203, 213, 225, 0.18);
            z-index: 0;
            white-space: nowrap;
            font-weight: bold;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>

<body>
    <header>
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    @if(isset($logo) && $logo)
                        <img src="{{ $logo }}" style="height: 35px; margin-bottom: 5px;">
                    @else
                        <div class="logo-text">{{ $empresa->nombre_empresa ?? 'VIRCOM' }}</div>
                    @endif
                    <div class="company-details">
                        {{ $empresa->direccion_completa ?? '' }}<br>
                        RFC: {{ $empresa->rfc ?? '' }} | Tel: {{ $empresa->telefono ?? '' }}
                    </div>
                </td>
                <td style="width: 40%; text-align: right;">
                    <div style="font-size: 10px; font-weight: bold;">CONTRATO DE SERVICIOS</div>
                    <div style="font-size: 12px; color: #dc2626;">FOLIO: {{ $poliza->folio }}</div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left; width: 33%;">
                    Página <span class="pagenum"></span> de <span class="pagecount"></span>
                </td>
                <td style="text-align: center; width: 34%;">
                    {{ $empresa->website ?? 'www.vircom.mx' }}
                </td>
                <td style="text-align: right; width: 33%;">
                    Legalidad: Código de Comercio (Arts. 89 y 93)
                </td>
            </tr>
        </table>
    </footer>

    <!-- Watermark for draft/unsigned -->
    @if(!$poliza->firmado_at)
        <div class="watermark">BORRADOR</div>
    @endif

    <div class="content">
        <h1>CONTRATO DE PRESTACIÓN DE SERVICIOS DE SOPORTE TÉCNICO Y MANTENIMIENTO</h1>

        <p>
            CONTRATO DE PRESTACIÓN DE SERVICIOS QUE CELEBRAN POR UNA PARTE,
            <b>{{ $empresa->nombre_empresa ?? 'EL PRESTADOR' }}</b>
            (EN ADELANTE "EL PRESTADOR"), Y POR LA OTRA PARTE, <b>{{ $poliza->cliente->nombre_razon_social }}</b> (EN
            ADELANTE
            "EL CLIENTE"),
            AL TENOR DE LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS:
        </p>

        <h2>DECLARACIONES</h2>

        <p><span class="clause-title">I. DECLARA "EL PRESTADOR":</span></p>
        <p>
            a) Ser una entidad legalmente constituida conforme a las leyes mexicanas, con la capacidad técnica, humana y
            material para prestar los servicios objeto de este contrato.<br>
            b) Que su Registro Federal de Contribuyentes es <b>{{ $empresa->rfc }}</b> y tiene su domicilio fiscal en
            {{ $empresa->direccion_completa ?? 'el indicado en el encabezado' }}.
        </p>

        <p><span class="clause-title">II. DECLARA "EL CLIENTE":</span></p>
        <p>
            a) Ser una persona @if($poliza->cliente->tipo_persona == 'moral') moral @else física @endif con plena
            capacidad
            legal para obligarse en los términos del presente contrato.<br>
            b) Que su Registro Federal de Contribuyentes es <b>{{ $poliza->cliente->rfc }}</b>.<br>
            c) Que requiere los servicios de soporte técnico profesional descritos en la Póliza de Servicio con folio
            <b>{{ $poliza->folio }}</b> para sus equipos e infraestructura TI.
        </p>

        <h2>CLÁUSULAS</h2>

        <p><span class="clause-title">PRIMERA. OBJETO.</span> "EL PRESTADOR" se obliga a suministrar a "EL CLIENTE" los
            servicios de soporte técnico, preventivo y correctivo para la infraestructura tecnológica detallada en el
            "ANEXO A" de este contrato, conforme a los niveles de servicio (SLA) estipulados en el mismo.</p>

        <p><span class="clause-title">SEGUNDA. VIGENCIA.</span> El presente contrato tendrá una vigencia del
            <b>{{ $poliza->fecha_inicio->format('d/m/Y') }}</b> al
            <b>{{ $poliza->fecha_fin ? $poliza->fecha_fin->format('d/m/Y') : 'INDEFINIDO' }}</b>.
            {{ $poliza->renovacion_automatica ? 'Este contrato se renovará automáticamente por periodos de igual duración salvo notificación en contrario por cualquiera de las partes con 30 días de anticipación.' : '' }}
        </p>

        <p><span class="clause-title">TERCERA. CONTRAPRESTACIÓN Y FORMA DE PAGO.</span> "EL CLIENTE" pagará la cantidad
            mensual de <b>${{ number_format($poliza->monto_mensual, 2) }} MXN</b> más IVA. El pago deberá realizarse
            dentro de los primeros <b>{{ $poliza->dia_cobro }}</b> días naturales de cada periodo mensual facturado. En
            caso de falta de pago, "EL PRESTADOR" se reserva el derecho de suspender el servicio hasta la regularización
            del adeudo.</p>

        <p><span class="clause-title">CUARTA. NIVELES DE SERVICIO (SLA).</span> "EL PRESTADOR" garantiza un tiempo
            máximo de primera respuesta de <b>{{ $poliza->sla_horas_respuesta ?? 24 }} horas laborables</b> para
            incidentes reportados a través del Portal de Clientes o canales oficiales. Los tiempos de resolución
            dependerán de la complejidad y disponibilidad de refacciones.</p>

        <p><span class="clause-title">QUINTA. SERVICIOS INCLUIDOS Y EXCEDENTES.</span> El plan contratado incluye los
            recursos descritos en el "ANEXO A". Cualquier servicio adicional (horas extra, visitas físicas no incluidas,
            o refacciones) será facturado por separado previa autorización de "EL CLIENTE", conforme a las siguientes
            tarifas preferenciales (más IVA):</p>

        <ul>
            <li>Hora de soporte/ingeniería adicional:
                ${{ number_format($poliza->costo_hora_excedente ?? $poliza->planPoliza?->costo_hora_extra ?? 0, 2) }}
                MXN</li>
            <li>Visita en sitio adicional:
                ${{ number_format($poliza->costo_visita_sitio_extra ?? $poliza->planPoliza?->costo_visita_extra ?? 0, 2) }}
                MXN</li>
            <li>Ticket adicional:
                ${{ number_format($poliza->costo_ticket_extra ?? $poliza->planPoliza?->costo_ticket_extra ?? 0, 2) }}
                MXN</li>
        </ul>

        <p><span class="clause-title">SEXTA. CONFIDENCIALIDAD Y PROTECCIÓN DE DATOS.</span> Ambas partes se obligan a
            tratar la información técnica y comercial como confidencial de manera indefinida. El tratamiento de datos
            personales de las partes y sus empleados se sujetará estrictamente a la Ley Federal de Protección de Datos
            Personales en Posesión de los Particulares y al Aviso de Privacidad de "EL PRESTADOR".</p>

        <p><span class="clause-title">SÉPTIMA. CONSENTIMIENTO Y EVIDENCIA DIGITAL (CÓDIGO DE COMERCIO).</span>
            De conformidad con los artículos 89 al 114 del Código de Comercio Federal, las partes reconocen la validez
            de los mensajes de datos y las firmas electrónicas aquí plasmadas. El Prestador conserva evidencia técnica
            de integridad y trazabilidad del documento y, cuando sea requerido, podrá complementarse con constancia de
            conservación conforme a la NOM-151-SCFI-2016 emitida por un Proveedor de Servicios de Certificación.</p>

        <p><span class="clause-title">OCTAVA. LIMITACIÓN DE RESPONSABILIDAD.</span> "EL PRESTADOR" no será responsable
            por daños indirectos, lucro cesante o pérdida de datos derivados de fallas de terceros (hardware, software o
            servicios de internet). La responsabilidad máxima de "EL PRESTADOR" se limita al monto equivalente a una
            mensualidad del servicio contratado.</p>

        <p><span class="clause-title">NOVENA. ANTICORRUPCIÓN Y LEGALIDAD.</span> Ambas partes declaran bajo protesta de
            decir verdad que los recursos utilizados para el cumplimiento de este contrato provienen de fuentes lícitas
            y se comprometen a no realizar actos de corrupción o soborno conforme a la Ley General de Responsabilidades
            Administrativas.</p>

        <p><span class="clause-title">DÉCIMA. JURISDICCIÓN Y COMPETENCIA.</span> Para todo lo relativo a la
            interpretación, cumplimiento y ejecución del presente contrato, las partes podrán acudir a la competencia de
            la
            Procuraduría Federal del Consumidor (PROFECO) en el ámbito administrativo; y para la vía judicial, a las
            leyes y Tribunales Competentes de la ciudad de <b>Hermosillo, Sonora</b>, renunciando expresamente a
            cualquier otro fuero que pudiera corresponderles por sus domicilios presentes o futuros.</p>

        <h2>ANEXO A: RESUMEN DE LA PÓLIZA</h2>

        <table class="info-table">
            <tr>
                <th colspan="2">DETALLES DEL PLAN CONTRATADO</th>
            </tr>
            <tr>
                <td width="30%"><b>Plan:</b></td>
                <td>{{ $poliza->nombre }} ({{ $poliza->planPoliza->nombre ?? 'Plan Personalizado' }})</td>
            </tr>
            <tr>
                <td><b>Descripción:</b></td>
                <td>{{ $poliza->descripcion }}</td>
            </tr>
            <tr>
                <td><b>Horas Incluidas:</b></td>
                <td>{{ $poliza->horas_incluidas_mensual > 0 ? $poliza->horas_incluidas_mensual . ' Hrs/Mes' : 'Ilimitadas / Según requerimiento' }}
                </td>
            </tr>
            <tr>
                <td><b>Tickets Incluidos:</b></td>
                <td>{{ $poliza->limite_mensual_tickets > 0 ? $poliza->limite_mensual_tickets . ' Tickets/Mes' : 'Ilimitados' }}
                </td>
            </tr>
            <tr>
                <td><b>Visitas en Sitio:</b></td>
                <td>{{ $poliza->visitas_sitio_mensuales > 0 ? $poliza->visitas_sitio_mensuales . ' Visitas/Mes' : 'Bajo cotización previa' }}
                </td>
            </tr>
        </table>

        @if($poliza->equipos->count() > 0)
            <h3>EQUIPOS PROTEGIDOS</h3>
            <table class="info-table">
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Serie / ID</th>
                        <th>Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($poliza->equipos as $equipo)
                        <tr>
                            <td>{{ $equipo->nombre }}</td>
                            <td>{{ $equipo->serie ?? 'N/A' }}</td>
                            <td>{{ $equipo->ubicacion ?? 'Oficina Principal' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-size: 9px; font-style: italic; background-color: #f8fafc; padding: 5px;">* Los equipos cubiertos
                se detallan en el inventario inicial anexo o en el Portal de Clientes. La cobertura aplica para la
                infraestructura operativa ubicada en el domicilio del cliente.</p>
        @endif

        <div class="signatures">
            <div class="sig-block">
                <div class="sig-image-container">
                    @if($poliza->firma_empresa)
                        <img src="{{ $poliza->firma_empresa }}" style="max-height: 80px;">
                    @else
                        <div style="height: 60px;"></div>
                    @endif
                </div>
                <div class="sig-line">
                    {{ $empresa->nombre_empresa ?? 'EL PRESTADOR' }}<br>
                    <span class="sig-role">REPRESENTANTE LEGAL</span>
                </div>
            </div>

            <div class="sig-block">
                <div class="sig-image-container">
                    @if($poliza->firma_cliente)
                        <img src="{{ $poliza->firma_cliente }}" style="max-height: 80px;">
                    @else
                        <div
                            style="height: 60px; color: #cbd5e1; display: flex; align-items: flex-end; justify-content: center; font-size: 10px;">
                            (Espacio para Firma)</div>
                    @endif
                </div>
                <div class="sig-line">
                    {{ $poliza->firmado_nombre ?? $poliza->cliente->nombre_razon_social }}<br>
                    <span class="sig-role">EL CLIENTE</span>
                </div>
            </div>
        </div>

        @if($poliza->firmado_at)
            <div class="digital-certificate">
                <div style="margin-bottom: 5px; font-weight: bold; border-bottom: 1px dashed #cbd5e1; padding-bottom: 2px;">
                    CONSTANCIA DE CONSERVACIÓN DE MENSAJES DE DATOS
                </div>
                <table style="width: 100%; border: none;">
                    <tr>
                        <td width="20%" style="border: none; color: #64748b;">Algoritmo:</td>
                        <td style="border: none;">SHA-256</td>
                    </tr>
                    <tr>
                        <td style="border: none; color: #64748b;">Fecha/Hora Firma:</td>
                        <td style="border: none;">{{ $poliza->firmado_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; color: #64748b;">IP Origen:</td>
                        <td style="border: none;">{{ $poliza->firmado_ip }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; color: #64748b;">Hash del Documento:</td>
                        <td style="border: none;" class="hash-code">{{ $poliza->firma_hash ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="border: none; color: #64748b;">Identificador del Documento:</td>
                        <td style="border: none;">
                            {{ $poliza->folio }}-{{ md5($poliza->id . $poliza->created_at->timestamp) }}
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 5px; font-size: 7px; color: #94a3b8; text-align: justify;">
                    El presente documento ha sido firmado electrónicamente conforme al artículo 89 del Código de Comercio.
                    El Prestador conserva el mensaje de datos y la evidencia electrónica necesaria para acreditar la
                    integridad, autoría y fecha cierta del documento, en términos de la NOM-151-SCFI-2016, pudiendo
                    complementarse con constancia emitida por un Proveedor de Servicios de Certificación cuando así se
                    requiera.
                </div>
            </div>
        @endif

    </div>
</body>

</html>