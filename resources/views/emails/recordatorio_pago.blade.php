<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Pago - {{ $configuracion->nombre_empresa }}</title>
    <style>
        /* ... styles unchanged ... */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #f59e0b;
            margin: 0;
            font-size: 28px;
        }

        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .warning h3 {
            color: #d97706;
            margin: 0 0 10px 0;
        }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .info-block {
            display: table-cell;
            width: 50%;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 0 10px;
        }

        .info-block h3 {
            color: #f59e0b;
            margin-top: 0;
        }

        .amount {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #dc2626;
            background-color: #fee2e2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #dc2626;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #666;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #f59e0b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }

        .button:hover {
            background-color: #d97706;
        }

        .urgent {
            background-color: #fee2e2;
            border: 1px solid #dc2626;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .urgent-text {
            color: #dc2626;
            font-weight: bold;
        }

        .days-overdue {
            background-color: #dc2626;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ $configuracion->nombre_empresa }}</h1>
            <p>🔔 {{ isset($esRenta) && $esRenta ? 'Recordatorio de Renta Vencida' : 'Recordatorio de Pago Pendiente' }}</p>
        </div>

        <div class="warning">
            <h3>⚠️ {{ isset($esRenta) && $esRenta ? 'Pago de Renta Requerido' : 'Recordatorio de Pago Pendiente' }}</h3>
            @if(isset($esRenta) && $esRenta)
                <p>Estimado cliente, le informamos que el pago de su renta se encuentra vencido. 
                Le solicitamos realizar el pago a la brevedad para evitar suspensiones del servicio o recargos.</p>
            @else
                <p>Estimado cliente, le recordamos amablemente que aún tenemos pendiente de recibir el pago correspondiente
                a la venta realizada.</p>
            @endif
        </div>

        @if ($cuenta)
            @php
                $diasVencidos = now()->diffInDays($cuenta->fecha_vencimiento, false);
                $tipoRecordatorio = $recordatorio->tipo_recordatorio ?? 'vencimiento';
            @endphp

            @if ($diasVencidos > 0)
                <div class="urgent">
                    <p class="urgent-text">
                        📅 Esta cuenta está vencida desde hace <span
                            class="days-overdue">{{ intval(abs($diasVencidos)) }} días</span>
                    </p>
                </div>
            @endif
        @endif

        @if ($cuenta)
            <div class="info-section">
                <div class="info-block">
                    <h3>Información de la Cuenta</h3>
                    @if(isset($esRenta) && $esRenta)
                        <p><strong>Renta:</strong> #{{ $renta->numero_contrato }}</p>
                        <p><strong>Periodo:</strong> {{ $cuenta->notas ?? 'N/A' }}</p>
                    @else
                        <p><strong>Venta:</strong> #{{ $cuenta->cobrable->numero_venta ?? $cuenta->venta->numero_venta }}</p>
                        <p><strong>Fecha de Venta:</strong>
                            @if(isset($cuenta->cobrable->fecha))
                                {{ $cuenta->cobrable->fecha->format('d/m/Y') }}
                            @elseif(isset($cuenta->venta->fecha))
                                {{ $cuenta->venta->fecha->format('d/m/Y') }}
                            @else
                                {{ $cuenta->created_at->format('d/m/Y') }}
                            @endif
                        </p>
                    @endif
                    
                    <p><strong>Fecha de Vencimiento:</strong> {{ $cuenta->fecha_vencimiento ? $cuenta->fecha_vencimiento->format('d/m/Y') : 'N/A' }}</p>
                    <p><strong>Estado:</strong> {{ ucfirst($cuenta->estado) }}</p>
                </div>

                <div class="info-block">
                    <h3>Cliente</h3>
                    <p><strong>Nombre:</strong> {{ $cliente->nombre_razon_social }}</p>
                    @if ($cliente->email)
                        <p><strong>Email:</strong> {{ $cliente->email }}</p>
                    @endif
                    @if ($cliente->telefono)
                        <p><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
                    @endif
                </div>
            </div>

            <div class="amount">
                💰 Monto Pendiente: ${{ number_format($cuenta->monto_pendiente, 2) }}
            </div>

            @if(isset($esRenta) && $esRenta)
                <!-- Información adicional de Renta si es necesario -->
            @else
                @php
                    $notas = $cuenta->cobrable->notas ?? $cuenta->venta->notas ?? '';
                @endphp
                @if ($notas && !str_contains(strtolower($notas), 'generado desde'))
                    <div
                        style="background-color: #e0f2fe; border: 1px solid #0ea5e9; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h4 style="margin: 0 0 10px 0; color: #0ea5e9;">📝 Notas de la Venta</h4>
                        <p style="margin: 0; color: #0ea5e9;">{{ $notas }}</p>
                    </div>
                @endif
            @endif
        @else
            <!-- Fallback para cuando $cuenta no existe (legacy flow, aunque raro con el nuevo comando) -->
             <div class="info-section">
                <!-- ... legacy content support if needed, but likely not reached ... -->
             </div>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <h3>Formas de Pago Aceptadas:</h3>
            <p>✅ Efectivo</p>
            <p>✅ Transferencia Bancaria</p>
            <p>✅ Tarjeta de Crédito/Débito</p>
        </div>

        <div
            style="background-color: #f0fdf4; border: 1px solid #22c55e; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3 style="color: #16a34a; margin: 0 0 15px 0;">💳 Información Bancaria</h3>
            @if ($configuracion->banco)
                <p><strong>Banco:</strong> {{ $configuracion->banco }}</p>
            @endif
            @if ($configuracion->cuenta)
                <p><strong>Cuenta:</strong> {{ $configuracion->cuenta }}</p>
            @endif
            @if ($configuracion->clabe)
                <p><strong>CLABE:</strong> {{ $configuracion->clabe }}</p>
            @endif
            @if ($configuracion->titular)
                <p><strong>Titular:</strong> {{ $configuracion->titular }}</p>
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <p class="urgent-text">⏰ Le agradecemos realizar el pago a la brevedad posible.</p>
            <p>Para cualquier duda o aclaración respecto al pago de esta factura, no dude en contactarnos. Estamos a sus
                órdenes para brindarle el apoyo necesario.</p>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático generado por {{ $configuracion->nombre_empresa }}</p>
            <p>Por favor, no responda a este correo.</p>
            @if ($configuracion->telefono)
                <p><strong>Teléfono:</strong> {{ $configuracion->telefono }}</p>
            @endif
            @if ($configuracion->email)
                <p><strong>Email:</strong> {{ $configuracion->email }}</p>
            @endif
        </div>
    </div>
</body>

</html>
