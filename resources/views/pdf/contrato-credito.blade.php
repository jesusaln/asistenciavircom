<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de Crédito - {{ $cliente->nombre_razon_social }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid {{ $empresa->color_principal ?? '#3182ce' }};
            padding-bottom: 15px;
        }
        .logo {
            max-width: 140px;
            max-height: 70px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: {{ $empresa->color_principal ?? '#3182ce' }};
            text-transform: uppercase;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 12px;
            padding-bottom: 3px;
        }
        .field {
            margin-bottom: 3px;
        }
        .label {
            font-weight: bold;
            width: 130px;
            display: inline-block;
        }
        .clauses {
            font-size: 9px;
            text-align: justify;
            color: #444;
        }
        .signatures {
            margin-top: 60px;
            width: 100%;
        }
        .signature-box {
            width: 45%;
            display: inline-block;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin: 0 2%;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($infoEmpresa['logo_base64']))
            <img src="{{ $infoEmpresa['logo_base64'] }}" class="logo">
        @endif
        <div class="title">Contrato de Apertura de Crédito Comercial</div>
        <div>Folio de Registro: <strong>CR-{{ str_pad($cliente->id, 5, '0', STR_PAD_LEFT) }}</strong></div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Otorgante</div>
        <div class="field"><span class="label">Empresa:</span> {{ $infoEmpresa['nombre'] }}</div>
        <div class="field"><span class="label">RFC:</span> {{ $infoEmpresa['rfc'] }}</div>
        <div class="field"><span class="label">Domicilio:</span> {{ $infoEmpresa['direccion'] }}</div>
    </div>

    <div class="section">
        <div class="section-title">Datos del Cliente (Acreditado)</div>
        <div class="field"><span class="label">Nombre/Razón Social:</span> {{ $cliente->nombre_razon_social }}</div>
        <div class="field"><span class="label">RFC:</span> {{ $cliente->rfc }}</div>
        <div class="field"><span class="label">Domicilio:</span> {{ $cliente->direccion_completa }}</div>
        <div class="field"><span class="label">Teléfono:</span> {{ $cliente->telefono }}</div>
        <div class="field"><span class="label">Email de Contacto:</span> {{ $cliente->email }}</div>
    </div>

    <div class="section">
        <div class="section-title">Parámetros de la Línea de Crédito</div>
        <div class="field"><span class="label">Monto Autorizado:</span> <strong>${{ number_format($cliente->limite_credito, 2) }} MXN</strong></div>
        <div class="field"><span class="label">Plazo de Pago:</span> <strong>{{ $cliente->dias_credito }} días naturales</strong></div>
        <div class="field"><span class="label">Fecha de Apertura:</span> {{ $fecha }}</div>
        <div class="field"><span class="label">Estado Inicial:</span> {{ strtoupper($cliente->estado_credito) }}</div>
    </div>

    <div class="section">
        <div class="section-title">Cláusulas de Operación</div>
        <div class="clauses">
            <p><strong>PRIMERA. OBJETO:</strong> El Otorgante pone a disposición del Acreditado una línea de crédito comercial para la adquisición exclusiva de bienes y/o servicios comercializados por el Otorgante.</p>
            <p><strong>SEGUNDA. DISPOSICIÓN:</strong> El Acreditado podrá disponer del crédito mediante órdenes de compra o pedidos, siempre que el saldo insoluto no exceda el monto autorizado.</p>
            <p><strong>TERCERA. PLAZOS DE PAGO:</strong> Cada disposición deberá liquidarse íntegramente en un plazo máximo de {{ $cliente->dias_credito }} días naturales contados a partir de la fecha de facturación.</p>
            <p><strong>CUARTA. MORA:</strong> La falta de pago oportuno facultará al Otorgante para suspender la línea de crédito y aplicar una tasa de interés moratorio del 5% mensual sobre saldos vencidos.</p>
            <p><strong>QUINTA. VIGENCIA:</strong> El presente contrato es por tiempo indefinido, pudiendo ser cancelado por cualquiera de las partes con aviso previo de 30 días, siempre que no existan saldos pendientes.</p>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            Por el Otorgante<br>
            <strong>{{ $infoEmpresa['nombre'] }}</strong>
        </div>
        <div class="signature-box">
            Por el Acreditado<br>
            <strong>{{ $cliente->nombre_razon_social }}</strong>
        </div>
    </div>

    <div class="footer">
        Este documento es un comprobante interno de apertura de crédito. | Tel: {{ $infoEmpresa['telefono'] }} | Web: {{ $infoEmpresa['sitio_web'] }}
    </div>
</body>
</html>