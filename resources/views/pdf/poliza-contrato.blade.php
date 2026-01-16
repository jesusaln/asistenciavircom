<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Contrato de Servicio {{ $poliza->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            line-height: 1.5;
        }

        .container {
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        h2 {
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #000;
            padding-bottom: 20px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
        }

        .clause {
            margin-bottom: 10px;
        }

        .clause-title {
            font-weight: bold;
            display: block;
        }

        .signatures {
            margin-top: 60px;
            display: table;
            width: 100%;
            page-break-inside: avoid;
        }

        .sig-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }

        .sig-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <b>{{ $empresa->nombre ?? 'LA EMPRESA' }}</b><br>
            {{ $empresa->direccion ?? '' }}<br>
            RFC: {{ $empresa->rfc ?? 'N/A' }}
        </div>

        <h1>CONTRATO DE PRESTACIÓN DE SERVICIOS DE MANTENIMIENTO Y SOPORTE TÉCNICO</h1>

        <p>
            En la ciudad de {{ $empresa->municipio ?? 'Hermosillo' }}, a {{ now()->day }} de
            {{ now()->locale('es')->monthName }} del {{ now()->year }}, comparecen por una parte
            <b>{{ $empresa->nombre ?? 'EL PROVEEDOR' }}</b>, y por la otra parte
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
                    {{ $poliza->fecha_fin ? $poliza->fecha_fin->format('d/m/Y') : 'Indefinido' }}</td>
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
            mes.
        </p>

        @if($poliza->costo_hora_excedente)
            <p>
                En caso de exceder las horas incluidas, se cobrará una tarifa de
                ${{ number_format($poliza->costo_hora_excedente, 2) }} MXN más IVA por hora adicional.
            </p>
        @endif

        <h2>4. EQUIPOS CUBIERTOS</h2>
        <p>
            Este contrato cubre exclusivamente los siguientes equipos registrados:
            @if($poliza->equipos->count() > 0)
                {{ $poliza->equipos->pluck('nombre')->implode(', ') }}.
            @else
                Los equipos especificados en el anexo técnico.
            @endif
        </p>

        <div class="signatures">
            <div class="sig-block">
                <div class="sig-line">
                    POR EL PROVEEDOR<br>
                    {{ $empresa->nombre ?? 'GERENTE GENERAL' }}
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
            Página 1 de 1 - Contrato Folio {{ $poliza->folio }}
        </div>
    </div>
</body>

</html>