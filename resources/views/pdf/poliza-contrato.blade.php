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
                <div class="sig-line">
                    POR EL PROVEEDOR<br>
                    {{ $empresa->nombre_empresa ?? 'REPRESENTANTE LEGAL' }}
                </div>
            </div>
            <div class="sig-block">
                <div class="sig-line">
                    POR EL CLIENTE<br>
                    {{ $poliza->cliente->nombre_razon_social }}
                </div>
            </div>
        </div>

        <div class="footer">
            Documento generado electrónicamente - Folio {{ $poliza->folio }} - Página 1 de 1
        </div>
    </div>
</body>

</html>