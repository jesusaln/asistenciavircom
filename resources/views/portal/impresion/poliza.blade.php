<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Póliza de Servicio #{{ $poliza->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            line-height: 1.5;
            font-size: 12px;
            margin: 0;
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }

        .logo {
            max-height: 50px;
        }

        .title {
            text-align: right;
        }

        h1 {
            font-size: 24px;
            font-weight: 900;
            margin: 0;
            color: #111827;
            text-transform: uppercase;
        }

        .subtitle {
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #374151;
        }

        .row {
            display: flex;
            margin-bottom: 10px;
        }

        .col {
            flex: 1;
        }

        .label {
            font-weight: 700;
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .value {
            font-size: 12px;
            font-weight: 500;
            color: #111827;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .table th {
            text-align: left;
            padding: 8px;
            background-color: #f9fafb;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }

        .table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            background-color: #dbeafe;
            color: #1e40af;
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
                <h2 style="margin:0;">{{ $empresa->nombre_comercial ?? 'EMPRESA' }}</h2>
            @endif
        </div>
        <div class="title">
            <div class="subtitle">Contrato de Servicio</div>
            <h1>Póliza #{{ $poliza->id }}</h1>
            <div class="status-badge" style="margin-top:5px;">{{ $poliza->estado }}</div>
        </div>
    </div>

    <!-- Info General -->
    <div class="section">
        <div class="row">
            <div class="col">
                <div class="label">Cliente</div>
                <div class="value">{{ $cliente->nombre_razon_social }}</div>
                <div class="value">{{ $cliente->direccion }}</div>
                <div class="value">{{ $cliente->rfc }}</div>
            </div>
            <div class="col" style="text-align:right;">
                <div class="label">Proveedor</div>
                <div class="value">{{ $empresa->nombre_comercial }}</div>
                <div class="value">{{ $empresa->email }}</div>
                <div class="value">{{ $empresa->telefono }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalles de la Cobertura</div>
        <div class="row">
            <div class="col">
                <div class="label">Plan Contratado</div>
                <div class="value" style="font-size: 14px; font-weight: 700;">{{ $poliza->nombre }}</div>
                <div class="value" style="color:#6b7280;">{{ $poliza->descripcion }}</div>
            </div>
            <div class="col">
                <div class="label">Vigencia</div>
                <div class="value">
                    Del {{ \Carbon\Carbon::parse($poliza->fecha_inicio)->format('d/m/Y') }}
                    al {{ \Carbon\Carbon::parse($poliza->fecha_fin)->format('d/m/Y') }}
                </div>
            </div>
            <div class="col">
                <div class="label">Renovación</div>
                <div class="value">{{ $poliza->renovacion_automatica ? 'Automática' : 'Manual / Única' }}</div>
            </div>
        </div>
    </div>

    <!-- Limites -->
    <div class="section">
        <div class="section-title">Alcance del Servicio</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Límite / Detalle</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tickets de Soporte Incluidos</td>
                    <td>{{ $poliza->limite_mensual_tickets > 900 ? 'Ilimitados' : $poliza->limite_mensual_tickets }}
                    </td>
                </tr>
                <tr>
                    <td>Horas Presenciales</td>
                    <td>{{ $poliza->horas_incluidas_mensual ?? 0 }} Horas</td>
                </tr>
                <tr>
                    <td>Costo Hora Adicional</td>
                    <td>${{ number_format($poliza->costo_hora_excedente, 2) }}</td>
                </tr>
                <tr>
                    <td>Tiempo de Respuesta (SLA)</td>
                    <td>{{ $poliza->sla_horas_respuesta }} Horas</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Equipos -->
    <div class="section">
        <div class="section-title">Equipos Cubiertos</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Serie</th>
                    <th>Marca / Modelo</th>
                    <th>Ubicación</th>
                </tr>
            </thead>
            <tbody>
                @forelse($poliza->equipos as $equipo)
                    <tr>
                        <td>{{ $equipo->numero_serie }}</td>
                        <td>{{ $equipo->marca }} {{ $equipo->modelo }}</td>
                        <td>{{ $equipo->ubicacion_fisica }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color:#9ca3af;">Sin equipos registrados específicamente.
                            Cobertura global.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Este documento es un comprobante digital de la contratación de servicios tecnológicos.<br>
        Generado el {{ date('d/m/Y H:i') }}
    </div>

</body>

</html>