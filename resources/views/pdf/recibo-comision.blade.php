<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Comisión #{{ $pago->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
        }
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #F59E0B;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info h1 {
            color: #F59E0B;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #666;
            font-size: 11px;
            line-height: 1.5;
        }
        .recibo-info {
            text-align: right;
        }
        .recibo-info h2 {
            color: #333;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .recibo-info .numero {
            font-size: 24px;
            font-weight: bold;
            color: #F59E0B;
        }
        .recibo-info .fecha {
            color: #666;
            margin-top: 5px;
        }
        /* Vendedor Info */
        .vendedor-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .vendedor-section h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 8px;
        }
        .vendedor-grid {
            display: table;
            width: 100%;
        }
        .vendedor-row {
            display: table-row;
        }
        .vendedor-label {
            display: table-cell;
            color: #666;
            padding: 5px 0;
            width: 150px;
        }
        .vendedor-value {
            display: table-cell;
            color: #333;
            font-weight: 500;
            padding: 5px 0;
        }
        /* Periodo Info */
        .periodo-section {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px 20px;
            margin-bottom: 25px;
        }
        .periodo-section h3 {
            color: #B45309;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .periodo-section .periodo {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        /* Detalles Table */
        .detalles-section {
            margin-bottom: 25px;
        }
        .detalles-section h3 {
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #333;
            color: #fff;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        th:last-child {
            text-align: right;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        td:last-child {
            text-align: right;
            font-weight: 500;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        /* Totales */
        .totales-section {
            text-align: right;
            margin-bottom: 30px;
        }
        .totales-grid {
            display: inline-block;
            text-align: left;
            min-width: 300px;
        }
        .totales-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .totales-row:last-child {
            border-bottom: none;
        }
        .totales-row.total {
            background: #F59E0B;
            color: #fff;
            padding: 12px 15px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
        }
        .totales-label {
            color: #666;
        }
        .totales-value {
            font-weight: 600;
            color: #333;
        }
        .totales-row.total .totales-label,
        .totales-row.total .totales-value {
            color: #fff;
        }
        /* Pago Info */
        .pago-section {
            background: #ECFDF5;
            border: 1px solid #10B981;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .pago-section h3 {
            color: #047857;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .pago-grid {
            display: table;
            width: 100%;
        }
        .pago-row {
            display: table-row;
        }
        .pago-label {
            display: table-cell;
            color: #666;
            padding: 5px 0;
            width: 150px;
        }
        .pago-value {
            display: table-cell;
            color: #333;
            font-weight: 500;
            padding: 5px 0;
        }
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
            color: #999;
            font-size: 10px;
        }
        /* Firmas */
        .firmas {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .firma {
            text-align: center;
            width: 200px;
        }
        .firma-linea {
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $empresa->nombre ?? 'CDD App' }}</h1>
                <p>
                    {{ $empresa->direccion ?? '' }}<br>
                    Tel: {{ $empresa->telefono ?? '' }}<br>
                    {{ $empresa->email ?? '' }}
                </p>
            </div>
            <div class="recibo-info">
                <h2>RECIBO DE COMISIÓN</h2>
                <div class="numero">#{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="fecha">Fecha: {{ $pago->fecha_pago?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
            </div>
        </div>

        <!-- Vendedor Info -->
        <div class="vendedor-section">
            <h3>Información del Beneficiario</h3>
            <div class="vendedor-grid">
                <div class="vendedor-row">
                    <span class="vendedor-label">Nombre:</span>
                    <span class="vendedor-value">{{ $pago->vendedor?->name ?? 'N/A' }}</span>
                </div>
                <div class="vendedor-row">
                    <span class="vendedor-label">Tipo:</span>
                    <span class="vendedor-value">{{ $pago->vendedor_type === 'App\\Models\\User' ? 'Vendedor' : 'Técnico' }}</span>
                </div>
                @if($pago->vendedor?->email)
                <div class="vendedor-row">
                    <span class="vendedor-label">Email:</span>
                    <span class="vendedor-value">{{ $pago->vendedor->email }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Periodo -->
        <div class="periodo-section">
            <h3>Periodo de Comisión</h3>
            <div class="periodo">
                {{ \Carbon\Carbon::parse($pago->periodo_inicio)->format('d M Y') }} - {{ \Carbon\Carbon::parse($pago->periodo_fin)->format('d M Y') }}
            </div>
        </div>

        <!-- Detalles de Ventas -->
        @if(!empty($pago->detalles_ventas) && count($pago->detalles_ventas) > 0)
        <div class="detalles-section">
            <h3>Detalle de Ventas ({{ $pago->num_ventas }} ventas)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Venta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Total Venta</th>
                        <th>Comisión</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pago->detalles_ventas as $detalle)
                    <tr>
                        <td>{{ $detalle['numero_venta'] ?? '-' }}</td>
                        <td>{{ isset($detalle['fecha']) ? \Carbon\Carbon::parse($detalle['fecha'])->format('d/m/Y') : '-' }}</td>
                        <td>{{ $detalle['cliente'] ?? '-' }}</td>
                        <td>${{ number_format($detalle['total_venta'] ?? 0, 2) }}</td>
                        <td>${{ number_format($detalle['comision_total'] ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Totales -->
        <div class="totales-section">
            <div class="totales-grid">
                <div class="totales-row">
                    <span class="totales-label">Total Ventas:</span>
                    <span class="totales-value">${{ number_format($pago->total_ventas, 2) }}</span>
                </div>
                <div class="totales-row">
                    <span class="totales-label">Comisión Calculada:</span>
                    <span class="totales-value">${{ number_format($pago->monto_comision, 2) }}</span>
                </div>
                <div class="totales-row total">
                    <span class="totales-label">MONTO PAGADO:</span>
                    <span class="totales-value">${{ number_format($pago->monto_pagado, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Info de Pago -->
        <div class="pago-section">
            <h3>Información del Pago</h3>
            <div class="pago-grid">
                <div class="pago-row">
                    <span class="pago-label">Estado:</span>
                    <span class="pago-value">{{ ucfirst($pago->estado) }}</span>
                </div>
                <div class="pago-row">
                    <span class="pago-label">Método de Pago:</span>
                    <span class="pago-value">{{ ucfirst($pago->metodo_pago ?? 'N/A') }}</span>
                </div>
                @if($pago->referencia_pago)
                <div class="pago-row">
                    <span class="pago-label">Referencia:</span>
                    <span class="pago-value">{{ $pago->referencia_pago }}</span>
                </div>
                @endif
                @if($pago->cuentaBancaria)
                <div class="pago-row">
                    <span class="pago-label">Cuenta:</span>
                    <span class="pago-value">{{ $pago->cuentaBancaria->banco }} - {{ $pago->cuentaBancaria->nombre }}</span>
                </div>
                @endif
                <div class="pago-row">
                    <span class="pago-label">Pagado por:</span>
                    <span class="pago-value">{{ $pago->pagadoPorUser?->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        @if($pago->notas)
        <div style="margin-bottom: 25px;">
            <strong>Notas:</strong>
            <p style="color: #666; margin-top: 5px;">{{ $pago->notas }}</p>
        </div>
        @endif

        <!-- Firmas -->
        <div class="firmas">
            <div class="firma">
                <div class="firma-linea">
                    Recibí Conforme
                </div>
            </div>
            <div class="firma">
                <div class="firma-linea">
                    Autorizado por
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>{{ $empresa->nombre ?? 'CDD App' }} - Sistema de Gestión</p>
        </div>
    </div>
</body>
</html>
