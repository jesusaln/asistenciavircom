<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Contrato de Servicio {{ $poliza->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            line-height: 1.6;
        }

        .container {
            padding: 50px;
        }

        h1 {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 30px;
            color: #0f172a;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        h2 {
            font-size: 12px;
            font-weight: bold;
            margin-top: 25px;
            margin-bottom: 12px;
            text-transform: uppercase;
            color: #0f172a;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px;
            vertical-align: top;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-label {
            font-weight: bold;
            width: 180px;
            color: #64748b;
        }

        .signatures {
            margin-top: 80px;
            width: 100%;
            page-break-inside: avoid;
        }

        .sig-block {
            display: inline-block;
            width: 45%;
            text-align: center;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 8px;
            font-weight: bold;
            font-size: 10px;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <b style="font-size: 16px; color: #0f172a;">{{ $empresa->nombre_empresa ?? 'LA EMPRESA' }}</b><br>
            <span style="color: #64748b;">
                {{ $empresa->direccion_completa ?? '' }}<br>
                RFC: {{ $empresa->rfc ?? 'N/A' }}
            </span>
        </div>

        <h1>CONTRATO DE PRESTACIÓN DE SERVICIOS DE MANTENIMIENTO Y SOPORTE TÉCNICO</h1>

        <p>
            En la ciudad de {{ $empresa->ciudad ?? 'Hermosillo' }}, {{ $empresa->estado ?? 'Sonora' }}, comparecen por
            una parte
            <b>{{ $empresa->nombre_empresa ?? 'EL PROVEEDOR' }}</b>, y por la otra parte
            <b>{{ $poliza->cliente->nombre_razon_social }}</b> (en adelante "EL CLIENTE"), quienes acuerdan celebrar el
            presente contrato al tenor de las siguientes cláusulas.
        </p>

        <h2>1. OBJETO DEL CONTRATO</h2>
        <p>
            EL PROVEEDOR se obliga a prestar a EL CLIENTE los servicios de mantenimiento y soporte técnico bajo la
            modalidad de PÓLIZA DE SERVICIO, identificada con el folio <b>{{ $poliza->folio }}</b>.
        </p>

        <h2>2. ALCANCE DEL SERVICIO</h2>
        <table class="info-table">
            <tr>
                <td class="info-label">Plan Contratado:</td>
                <td>{{ $poliza->nombre }}</td>
            </tr>
            <tr>
                <td class="info-label">Descripción:</td>
                <td>{{ $poliza->descripcion }}</td>
            </tr>
            <tr>
                <td class="info-label">Vigencia:</td>
                <td>Del {{ $poliza->fecha_inicio->format('d/m/Y') }} al
                    {{ $poliza->fecha_fin ? $poliza->fecha_fin->format('d/m/Y') : 'Indefinido' }}
                </td>
            </tr>
            <tr>
                <td class="info-label">SLA Respuesta:</td>
                <td>{{ $poliza->sla_horas_respuesta ?? 'N/A' }} horas laborables</td>
            </tr>
            @if($poliza->horas_incluidas_mensual)
                <tr>
                    <td class="info-label">Horas Incluidas:</td>
                    <td>{{ $poliza->horas_incluidas_mensual }} horas mensuales</td>
                </tr>
            @endif
        </table>

        <h2>3. CONTRAPRESTACIÓN</h2>
        <p>
            EL CLIENTE pagará a EL PROVEEDOR la cantidad mensual de <b>${{ number_format($poliza->monto_mensual, 2) }}
                MXN</b> más IVA. El pago deberá realizarse dentro de los primeros {{ $poliza->dia_cobro }} días de cada
            mes calendario.
        </p>

        @if($poliza->costo_hora_excedente)
            <p>
                En caso de requerir servicios adicionales que excedan la cobertura mensual, se aplicará una tarifa
                preferencial de
                <b>${{ number_format($poliza->costo_hora_excedente, 2) }} MXN</b> más IVA por hora adicional.
            </p>
        @endif

        <h2>4. EQUIPOS CUBIERTOS</h2>
        <p>
            Este contrato cubre exclusivamente los equipos e infraestructura descritos a continuación:
            @if($poliza->equipos->count() > 0)
                <b>{{ $poliza->equipos->pluck('nombre')->implode(', ') }}</b>.
            @else
                Los equipos especificados en el portal de gestión y anexo técnico.
            @endif
        </p>

        <div class="signatures">
            <div class="sig-block" style="margin-right: 50px;">
                @if($poliza->firma_empresa)
                    <img src="{{ $poliza->firma_empresa }}"
                        style="max-width: 200px; max-height: 80px; margin-bottom: 10px;">
                @endif
                <div class="sig-line">
                    POR EL PROVEEDOR<br>
                    {{ $empresa->nombre_empresa ?? 'REPRESENTANTE LEGAL' }}
                </div>
            </div>
            <div class="sig-block">
                @if($poliza->firma_cliente)
                    <img src="{{ $poliza->firma_cliente }}"
                        style="max-width: 200px; max-height: 80px; margin-bottom: 10px;">
                    <div style="font-size: 9px; color: #64748b; margin-bottom: 5px;">
                        Firmado digitalmente el {{ $poliza->firmado_at->format('d/m/Y H:i') }}
                    </div>
                @endif
                <div class="sig-line">
                    POR EL CLIENTE<br>
                    {{ $poliza->firmado_nombre ?? $poliza->cliente->nombre_razon_social }}
                </div>
            </div>
        </div>

        @if($poliza->firmado_at)
            <div
                style="margin-top: 30px; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 9px;">
                <div style="font-weight: bold; color: #0f172a; margin-bottom: 5px;">CERTIFICADO DE FIRMA DIGITAL</div>
                <table style="width: 100%; font-size: 9px;">
                    <tr>
                        <td style="width: 120px; color: #64748b;">Firmante:</td>
                        <td>{{ $poliza->firmado_nombre }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Fecha y Hora:</td>
                        <td>{{ $poliza->firmado_at->format('d/m/Y H:i:s') }} (Hora de Mexico)</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">Hash de Verificacion:</td>
                        <td style="font-family: monospace; font-size: 8px;">{{ $poliza->firma_hash }}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;">IP Origen:</td>
                        <td>{{ $poliza->firmado_ip }}</td>
                    </tr>
                </table>
                <div style="margin-top: 8px; color: #22c55e; font-weight: bold;">
                    Este documento ha sido firmado electronicamente y tiene validez legal conforme al articulo 89 del Codigo
                    de Comercio.
                </div>
            </div>
        @else
            <div
                style="margin-top: 30px; padding: 15px; background: #fefce8; border: 1px solid #fef08a; border-radius: 8px; font-size: 9px; color: #a16207;">
                Este documento aun no ha sido firmado por el cliente. La firma puede realizarse desde el Portal de Clientes.
            </div>
        @endif

        <div class="footer">
            Documento generado electronicamente - Folio {{ $poliza->folio }} - Pagina 1 de 1
        </div>
    </div>
</body>

</html>