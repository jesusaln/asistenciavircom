@php
    $folio = 'REC-' . str_pad((string) $historial->id, 6, '0', STR_PAD_LEFT);
    $fechaOp = $historial->fecha_pago ? \Carbon\Carbon::parse($historial->fecha_pago)->locale('es')->translatedFormat('d \d\e F \d\e Y') : '—';
    $metodo = ucfirst(str_replace('_', ' ', $historial->metodo_pago ?? 'otro'));
    $mxn = fn ($n) => '$' . number_format((float) $n, 2, '.', ',');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $folio }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 9px; color: #1e293b; margin: 14px 16px; line-height: 1.35; }
        h1 { font-size: 14px; text-align: center; margin: 0 0 2px; text-transform: uppercase; letter-spacing: 0.04em; }
        .sub { text-align: center; font-size: 8px; color: #64748b; margin-bottom: 8px; }
        .folio { text-align: right; font-size: 8px; color: #64748b; margin-bottom: 6px; }
        .folio strong { color: #0f172a; font-size: 11px; }
        .section { margin-bottom: 8px; }
        .section-title { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.label { width: 30%; color: #64748b; font-size: 8px; text-transform: uppercase; }
        td.val { font-weight: 600; font-size: 9px; }
        .amount { text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; margin: 8px 0; }
        .amount .num { font-size: 18px; font-weight: bold; color: #0f172a; }
        .amount .words { font-size: 7px; color: #64748b; margin-top: 4px; text-transform: uppercase; line-height: 1.25; }
        .footer { font-size: 6px; color: #94a3b8; text-align: center; margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 6px; line-height: 1.35; }
        .sig-block { margin-top: 8px; text-align: center; }
        .sig-block img { max-height: 44px; max-width: 200px; display: block; margin: 0 auto 0; }
        .sig-line { border-top: 1px solid #94a3b8; width: 200px; margin: 0 auto; padding-top: 4px; font-size: 7px; font-weight: bold; color: #475569; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="folio">Folio de recepción <strong>{{ $folio }}</strong></div>

    <h1>Comprobante de abono</h1>
    <div class="sub">{{ $empresa['razon_social'] ?? $empresa['nombre'] ?? 'Empresa' }} @if(!empty($empresa['rfc'])) | RFC: {{ $empresa['rfc'] }} @endif</div>

    <div class="section">
        <div class="section-title">Datos del cliente</div>
        <table>
            <tr><td class="label">Nombre</td><td class="val">{{ $cliente->nombre_razon_social ?? '—' }}</td></tr>
            <tr><td class="label">RFC</td><td class="val">{{ $cliente->rfc ?? '—' }}</td></tr>
            @if(!empty($cliente->direccion_completa))
            <tr><td class="label">Domicilio</td><td class="val">{{ $cliente->direccion_completa }}</td></tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle del movimiento</div>
        <table>
            <tr><td class="label">Préstamo</td><td class="val">#{{ $prestamo->folio ?? $prestamo->id }}</td></tr>
            <tr><td class="label">Cuota</td><td class="val">Abono a la cuota #{{ $pago->numero_pago }} de {{ $prestamo->numero_pagos ?? '—' }}</td></tr>
            <tr><td class="label">Fecha de operación</td><td class="val">{{ $fechaOp }}</td></tr>
            <tr><td class="label">Método</td><td class="val">{{ $metodo }}</td></tr>
            @if(!empty($historial->referencia))
            <tr><td class="label">Referencia</td><td class="val">{{ $historial->referencia }}</td></tr>
            @endif
        </table>
    </div>

    <div class="amount">
        <div class="num">{{ $mxn($historial->monto_pagado) }}</div>
        <div class="words">{{ $monto_letras }}</div>
    </div>

    <div class="section">
        <div class="section-title">Estado del crédito</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 33%; text-align: center; vertical-align: top; padding: 2px 4px;">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase;">Amortizado</div>
                    <div style="font-weight: 600; font-size: 9px; color: #059669;">{{ $mxn($prestamo->monto_pagado ?? 0) }}</div>
                </td>
                <td style="width: 34%; text-align: center; vertical-align: top; padding: 2px 4px;">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase;">Saldo pendiente</div>
                    <div style="font-weight: 600; font-size: 9px; color: #b91c1c;">{{ $mxn($prestamo->monto_pendiente ?? 0) }}</div>
                </td>
                <td style="width: 33%; text-align: center; vertical-align: top; padding: 2px 4px;">
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase;">Progreso</div>
                    <div style="font-weight: 600; font-size: 9px;">{{ number_format($prestamo->progreso ?? 0, 2) }}%</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $firma = $empresa['firma_base64'] ?? $empresa['firma_digital_url'] ?? null;
    @endphp

    @if($firma)
    <div class="sig-block">
        <img src="{{ $firma }}" alt="Firma" />
        <div class="sig-line">Firma autorizada — {{ $empresa['razon_social'] ?? $empresa['nombre'] ?? 'Empresa' }}</div>
    </div>
    @endif

    <div class="footer">
        Acuse de recibo de fondos; no constituye liberación total del crédito hasta su liquidación.
        @if(!empty($empresa['direccion']))<br>{{ $empresa['direccion'] }}@endif
        @if(!empty($empresa['telefono'])) | Tel: {{ $empresa['telefono'] }}@endif
        <br>Generado: {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
    </div>
</body>
</html>
