<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Crédito Comercial - {{ $cliente->nombre_razon_social }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo {
            max-height: 60px;
        }

        .title-container {
            text-align: right;
        }

        h1 {
            font-size: 20px;
            font-weight: 900;
            margin: 0;
            color: #111827;
            text-transform: uppercase;
        }

        .date {
            font-size: 10px;
            font-weight: 600;
            color: #6b7280;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #f3f4f6;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 12px;
            border-left: 4px solid #111827;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .col {
            flex: 1;
            min-width: 200px;
            margin-bottom: 10px;
            padding-right: 15px;
        }

        .label {
            font-weight: 700;
            color: #4b5563;
            font-size: 9px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }

        .value {
            font-size: 11px;
            font-weight: 500;
            color: #000;
            border-bottom: 1px solid #e5e7eb;
            min-height: 15px;
            padding-bottom: 2px;
        }

        .terms {
            font-size: 9px;
            color: #4b5563;
            text-align: justify;
            margin-top: 30px;
            line-height: 1.6;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 8px;
        }

        .signature-grid {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .signature-box {
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <div>
            @if($logo)
                <img src="{{ $logo }}" alt="Logo" class="logo">
            @else
                <h2 style="margin:0;">{{ $empresa->nombre_comercial ?? 'ASISTENCIA VIRCOM' }}</h2>
            @endif
        </div>
        <div class="title-container">
            <h1>Solicitud de Crédito Comercial</h1>
            <div class="date">Fecha de Solicitud: {{ $fecha }}</div>
        </div>
    </div>

    <!-- DATOS DEL CLIENTE -->
    <div class="section">
        <div class="section-title">I. Datos Generales del Solicitante</div>
        <div class="grid">
            <div class="col" style="flex: 2;">
                <span class="label">Nombre o Razón Social</span>
                <div class="value">{{ $cliente->nombre_razon_social }}</div>
            </div>
            <div class="col">
                <span class="label">RFC</span>
                <div class="value">{{ $cliente->rfc }}</div>
            </div>
        </div>
        <div class="grid">
            <div class="col" style="flex: 2;">
                <span class="label">Domicilio Fiscal</span>
                <div class="value">{{ $cliente->direccion }}</div>
            </div>
            <div class="col">
                <span class="label">Teléfono</span>
                <div class="value">{{ $cliente->telefono }}</div>
            </div>
        </div>
        <div class="grid">
            <div class="col">
                <span class="label">Correo Electrónico para Facturación</span>
                <div class="value">{{ $cliente->email }}</div>
            </div>
            <div class="col">
                <span class="label">CURP (Personas Físicas)</span>
                <div class="value">{{ $cliente->curp ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- DATOS DE CRÉDITO SOLICITADO -->
    <div class="section">
        <div class="section-title">II. Información de Crédito Solicitado</div>
        <div class="grid">
            <div class="col">
                <span class="label">Monto de Crédito Solicitado</span>
                <div class="value">${{ number_format($cliente->limite_credito ?? 0, 2) }}</div>
            </div>
            <div class="col">
                <span class="label">Días de Crédito Solicitados</span>
                <div class="value">{{ $cliente->dias_credito ?? 0 }} días</div>
            </div>
            <div class="col">
                <span class="label">Tipo de Persona</span>
                <div class="value">{{ ucfirst($cliente->tipo_persona ?? 'No especificado') }}</div>
            </div>
        </div>
    </div>

    <!-- DECLARACIONES -->
    <div class="section">
        <div class="section-title">III. Declaraciones y Aceptación de Términos</div>
        <div class="terms">
            1. El Solicitante declara que la información proporcionada en esta solicitud es verdadera y autoriza a
            <strong>{{ $empresa->nombre_comercial ?? 'La Empresa' }}</strong> a verificar la misma por los medios que
            considere convenientes.<br>
            2. El otorgamiento del crédito queda sujeto a la aprobación del Departamento de Crédito y Cobranza tras el
            análisis de la documentación entregada.<br>
            3. El Solicitante se compromete a liquidar sus facturas puntualmente dentro del plazo de
            {{ $cliente->dias_credito ?? 30 }} días pactado. En caso de mora, se aplicarán los intereses estipulados en
            el contrato maestro.<br>
            4. Se autoriza a compartir información sobre el comportamiento de pago con sociedades de información
            crediticia.<br>
            5. La falta de pago de una o más facturas facultará a {{ $empresa->nombre_comercial ?? 'La Empresa' }} a
            suspender el servicio o entrega de productos de manera inmediata.
        </div>
    </div>

    <!-- FIRMAS -->
    <div class="signature-grid">
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $cliente->nombre_razon_social }}</strong><br>
                Firma del Solicitante / Representante Legal
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>{{ $empresa->nombre_comercial ?? 'La Empresa' }}</strong><br>
                Autorización (Sello y Firma)
            </div>
        </div>
    </div>

    <div style="margin-top: 40px; font-size: 8px; color: #9ca3af; text-align: center;">
        Este documento es una solicitud formal y no garantiza la asignación del crédito hasta ser validado por el comité
        correspondiente.<br>
        Generado desde el Portal de Clientes {{ $empresa->nombre_comercial ?? 'Vircom' }} el {{ date('d/m/Y H:i') }}
    </div>

</body>

</html>