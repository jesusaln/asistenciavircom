{{-- resources/views/orden_compra_pdf.blade.php --}}
@php
    use Illuminate\Support\Str;

    // Color principal saneado
    $COLOR = ltrim($configuracion->color_principal ?? '#3B82F6', '#');
    $HEX = "#{$COLOR}";
    $money = fn($n) => number_format((float) $n, 2);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Orden de Compra #{{ $ordenCompra->numero_orden }}</title>
    <style>
        @page {
            margin: 14mm 12mm 12mm 12mm;
            size: auto;
        }

        html,
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #222;
            font-size: 10px;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .muted {
            color: #666;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .avoid-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        tr,
        td,
        th {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Encabezado */
        .header {
            border-bottom: 2px solid {{ $HEX }};
            padding: 2mm 0 3mm;
            margin-bottom: 4mm;
        }

        .empresa {
            font-size: 15px;
            font-weight: 700;
            color: {{ $HEX }};
            text-align: center;
        }

        .orden-bar {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 6px 0 10px;
            font-size: 11px;
        }

        .orden-bar>div {
            display: table-cell;
            vertical-align: middle;
            white-space: nowrap;
        }

        .orden-left {
            text-align: left;
        }

        .orden-center {
            text-align: center;
        }

        .orden-right {
            text-align: right;
        }

        .orden-bar b {
            color: #333;
        }

        /* Info boxes (Empresa, Proveedor) */
        .info {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin: 4mm 0;
        }

        .info .col {
            display: table-cell;
            vertical-align: top;
            padding: 3mm;
            background: #f5f7fa;
            border: 1px solid #e6e9ef;
            border-radius: 4px;
        }

        .info .col+.col {
            padding-left: 3mm;
        }

        .info h3 {
            margin: 0 0 2mm;
            font-size: 11px;
            color: {{ $HEX }};
        }

        .kv {
            width: 100%;
            border-collapse: collapse;
        }

        .kv td {
            padding: .5mm 0;
            vertical-align: top;
        }

        .kv .k {
            width: 22mm;
            color: #666;
            font-weight: 600;
        }

        .kv .v {
            word-wrap: break-word;
        }

        /* Tabla de productos */
        .tbl {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2mm;
        }

        .tbl th {
            background: {{ $HEX }};
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 2.2mm 1.6mm;
            text-align: left;
        }

        .tbl td {
            font-size: 9px;
            padding: 2mm 1.6mm;
            border-bottom: 1px solid #e8ecf2;
        }

        .tbl .c {
            text-align: center;
        }

        .tbl .r {
            text-align: right;
        }

        .tbl tbody tr:nth-child(even) {
            background: #fafbfc;
        }

        .item-name {
            font-weight: 700;
        }

        .item-code {
            font-size: 8px;
            color: #555;
        }

        /* Totales */
        .totals {
            margin-top: 4mm;
            display: table;
            width: 100%;
        }

        .totals .left {
            display: table-cell;
            vertical-align: top;
            width: 55%;
            padding-right: 3mm;
        }

        .totals .right {
            display: table-cell;
            vertical-align: top;
            width: 45%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            background: #f5f7fa;
            border: 1px solid #e6e9ef;
            border-radius: 4px;
        }

        .totals-table td {
            padding: 1.6mm 2mm;
            font-size: 9.5px;
        }

        .totals-table .k {
            text-align: right;
            font-weight: 700;
            color: #444;
        }

        .totals-table .v {
            text-align: right;
            width: 30mm;
        }

        .grand {
            margin-top: 2mm;
            padding: 2.2mm 2mm;
            background: #e7f5ea;
            color: #127a2a;
            font-weight: 800;
            font-size: 12px;
            text-align: right;
            border-radius: 3px;
        }

        /* Footer fijo con notas y condiciones */
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 12mm;
            right: 12mm;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 4mm;
            font-size: 7px;
            line-height: 1.2;
            max-height: 30mm;
            overflow: hidden;
        }

        .footer-grid {
            display: table;
            width: 100%;
        }

        .footer-col {
            display: table-cell;
            vertical-align: top;
            padding: 0 2mm;
        }

        .footer-col:first-child {
            width: 50%;
        }

        .footer-col:last-child {
            width: 50%;
        }

        .footer-title {
            font-weight: bold;
            color: {{ $HEX }};
            font-size: 7px;
            margin-bottom: 1mm;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5mm;
        }

        .footer-text {
            font-size: 6px;
            color: #555;
            margin: 0;
            line-height: 1.1;
        }

        /* Ajustes finos */
        .tbl td,
        .tbl th {
            line-height: 1.15;
        }
    </style>
</head>

<body>
    {{-- ENCABEZADO --}}
    <div class="header avoid-break">
        <div class="empresa">{{ $configuracion->nombre_empresa }}</div>

        {{-- Barra UNA SOLA FILA (izq-centrado-der) --}}
        <div class="orden-bar">
            <div class="orden-left">
                <b>Orden de Compra:</b> #{{ $ordenCompra->numero_orden }}
            </div>
            <div class="orden-center">
                <b>Fecha:</b> {{ $ordenCompra->fecha_orden->format('d/m/Y') }}
            </div>
            <div class="orden-right">
                <b>Prioridad:</b> {{ ucfirst($ordenCompra->prioridad) }}
            </div>
        </div>
    </div>

    {{-- BLOQUES INFO (Empresa, Proveedor) --}}
    <div class="info avoid-break">
        <div class="col">
            <h3>Empresa</h3>
            <table class="kv">
                <tr>
                    <td class="k">Razón Social:</td>
                    <td class="v"><strong>{{ $configuracion->nombre_empresa }}</strong></td>
                </tr>
                @if ($configuracion->rfc)
                    <tr>
                        <td class="k">RFC:</td>
                        <td class="v">{{ $configuracion->rfc }}</td>
                    </tr>
                @endif
                @if ($configuracion->direccion_completa)
                    <tr>
                        <td class="k">Dirección:</td>
                        <td class="v">{{ $configuracion->direccion_completa }}</td>
                    </tr>
                @endif
                @if ($configuracion->municipio && $configuracion->estado && $configuracion->codigo_postal)
                    <tr>
                        <td class="k">Municipio:</td>
                        <td class="v">{{ $configuracion->municipio }}, {{ $configuracion->estado }},
                            {{ $configuracion->codigo_postal }}</td>
                    </tr>
                @endif
                @if ($configuracion->telefono)
                    <tr>
                        <td class="k">Teléfono:</td>
                        <td class="v">{{ $configuracion->telefono }}</td>
                    </tr>
                @endif
                @if ($configuracion->email)
                    <tr>
                        <td class="k">Email:</td>
                        <td class="v">{{ $configuracion->email }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="col">
            <h3>Proveedor</h3>
            <table class="kv">
                <tr>
                    <td class="k">Razón Social:</td>
                    <td class="v">{{ $ordenCompra->proveedor->nombre_razon_social }}</td>
                </tr>
                @if ($ordenCompra->proveedor->rfc)
                    <tr>
                        <td class="k">RFC:</td>
                        <td class="v">{{ $ordenCompra->proveedor->rfc }}</td>
                    </tr>
                @endif
                @if ($ordenCompra->proveedor->calle || $ordenCompra->proveedor->numero_exterior || $ordenCompra->proveedor->colonia)
                    <tr>
                        <td class="k">Dirección:</td>
                        <td class="v">
                            {{ trim(
                                implode(
                                    ' ',
                                    array_filter([
                                        $ordenCompra->proveedor->calle,
                                        $ordenCompra->proveedor->numero_exterior,
                                        $ordenCompra->proveedor->numero_interior ? 'Int. ' . $ordenCompra->proveedor->numero_interior : null,
                                        $ordenCompra->proveedor->colonia,
                                    ]),
                                ),
                            ) }}
                        </td>
                    </tr>
                @endif
                @if ($ordenCompra->proveedor->municipio || $ordenCompra->proveedor->estado || $ordenCompra->proveedor->codigo_postal)
                    <tr>
                        <td class="k">Municipio:</td>
                        <td class="v">
                            {{ trim(
                                implode(
                                    ', ',
                                    array_filter([
                                        $ordenCompra->proveedor->municipio,
                                        $ordenCompra->proveedor->estado,
                                        $ordenCompra->proveedor->codigo_postal,
                                    ]),
                                ),
                            ) }}
                        </td>
                    </tr>
                @endif
                @if ($ordenCompra->proveedor->telefono)
                    <tr>
                        <td class="k">Teléfono:</td>
                        <td class="v">{{ $ordenCompra->proveedor->telefono }}</td>
                    </tr>
                @endif
                @if ($ordenCompra->proveedor->email)
                    <tr>
                        <td class="k">Email:</td>
                        <td class="v">{{ $ordenCompra->proveedor->email }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    {{-- PRODUCTOS --}}
    <table class="tbl avoid-break">
        <thead>
            <tr>
                <th style="width:50%;">Producto</th>
                <th style="width:10%; text-align:center;">Cant.</th>
                <th style="width:15%; text-align:center;">Unidad</th>
                <th style="width:15%; text-align:right;">Precio</th>
                <th style="width:10%; text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ordenCompra->productos as $producto)
                @php
                    $nombre = $producto->nombre ?? '—';
                    $codigo = $producto->codigo ?? null;
                    $unidadMedida = $producto->pivot->unidad_medida ?? 'u';
                @endphp
                <tr>
                    <td>
                        <div class="item-name">{{ Str::limit($nombre, 65) }}</div>
                        @if ($codigo)
                            <div class="item-code">Cód: {{ Str::limit($codigo, 24) }}</div>
                        @endif
                    </td>
                    <td class="c">{{ $producto->pivot->cantidad }}</td>
                    <td class="c">{{ $unidadMedida }}</td>
                    <td class="r">${{ $money($producto->pivot->precio) }}</td>
                    <td class="r"><strong>${{ $money($producto->pivot->cantidad * $producto->pivot->precio) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALES --}}
    <div class="totals avoid-break">
        <div class="left">
            {{-- Información adicional de la orden --}}
            <table class="kv" style="background: #f8f9fa; padding: 2mm; border-radius: 3px;">
                @if ($ordenCompra->fecha_entrega_esperada)
                    <tr>
                        <td class="k">Entrega Esperada:</td>
                        <td class="v">{{ $ordenCompra->fecha_entrega_esperada->format('d/m/Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="k">Términos de Pago:</td>
                    <td class="v">{{ ucfirst(str_replace('_', ' ', $ordenCompra->terminos_pago)) }}</td>
                </tr>
                <tr>
                    <td class="k">Método de Pago:</td>
                    <td class="v">{{ ucfirst($ordenCompra->metodo_pago) }}</td>
                </tr>
                @if ($ordenCompra->direccion_entrega)
                    <tr>
                        <td class="k">Dirección Entrega:</td>
                        <td class="v">{{ $ordenCompra->direccion_entrega }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <div class="right">
            <table class="totals-table">
                <tr>
                    <td class="k">Subtotal:</td>
                    <td class="v">${{ $money($ordenCompra->subtotal) }}</td>
                </tr>
                @if (($ordenCompra->descuento_general ?? 0) > 0)
                    <tr>
                        <td class="k">Descuento:</td>
                        <td class="v">- ${{ $money($ordenCompra->descuento_general) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="k">IVA ({{ $configuracion->iva_porcentaje }}%):</td>
                    <td class="v">${{ $money($ordenCompra->iva) }}</td>
                </tr>
            </table>
            <div class="grand">TOTAL &nbsp;&nbsp; ${{ $money($ordenCompra->total) }}</div>
        </div>
    </div>

    {{-- FOOTER FIJO CON NOTAS Y CONDICIONES --}}
    <div class="fixed-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-title">📝 Observaciones de la Orden</div>
                @if ($ordenCompra->observaciones)
                    <p class="footer-text">{{ Str::limit($ordenCompra->observaciones, 300) }}</p>
                @else
                    <p class="footer-text" style="color: #999; font-style: italic;">Sin observaciones adicionales</p>
                @endif
            </div>
            <div class="footer-col">
                <div class="footer-title">📋 Condiciones Generales</div>
                <p class="footer-text">
                    • Esta orden está sujeta a las condiciones de compra establecidas.<br>
                    • Favor de confirmar recepción y fecha de entrega.<br>
                    • Cualquier cambio debe ser notificado inmediatamente.<br>
                    • Los precios incluyen IVA según la legislación vigente.
                </p>
            </div>
        </div>
    </div>

</body>

</html>
